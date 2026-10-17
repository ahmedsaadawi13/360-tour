<?php
/**
 * Lead Controller
 *
 * Public lead capture from tours, and lead management for tenants
 */

class LeadController
{
    const MAX_LEADS_PER_IP_PER_HOUR = 5;

    private $leadModel;

    public function __construct()
    {
        $this->leadModel = new Lead();
    }

    /**
     * Public endpoint: contact form submitted from a tour (JSON response).
     *
     * Not CSRF-protected on purpose: it is used inside third-party iframes where
     * session cookies may be blocked. Spam is limited by a honeypot field and a
     * per-IP rate limit instead.
     */
    public function submit()
    {
        // Honeypot: bots fill every field; pretend success
        if (trim((string) post('website')) !== '') {
            json_response(['success' => true, 'message' => 'Thank you!']);
        }

        $tour = (new Tour())->findBySlug((string) post('tour'));
        $isLive = $tour
            && $tour['status'] === 'published'
            && $tour['is_public']
            && $tour['lead_form_enabled']
            && $tour['tenant_status'] === 'active'
            && (new TenantSubscription())->getActiveSubscription($tour['tenant_id']);

        if (!$isLive) {
            json_response(['success' => false, 'message' => 'This tour is not accepting enquiries.'], 404);
        }

        if ($this->leadModel->countRecentFromIp(client_ip()) >= self::MAX_LEADS_PER_IP_PER_HOUR) {
            json_response(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
        }

        $name = mb_substr(sanitize(post('name')), 0, 150);
        $email = strtolower(mb_substr(sanitize(post('email')), 0, 255));
        $phone = mb_substr(preg_replace('/[^0-9+()\-\s.]/', '', (string) post('phone')), 0, 50);
        $message = mb_substr(sanitize(post('message')), 0, 2000);

        $errors = [];
        if ($name === '') $errors[] = 'Please enter your name';
        if (!is_valid_email($email)) $errors[] = 'Please enter a valid email';

        if ($errors) {
            json_response(['success' => false, 'message' => implode('. ', $errors)], 422);
        }

        $leadId = $this->leadModel->create([
            'tenant_id' => $tour['tenant_id'],
            'tour_id' => $tour['id'],
            'property_id' => $tour['property_id'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'message' => $message ?: null,
            'status' => 'new',
            'source' => post('embed') ? 'embed' : 'tour',
            'ip_address' => client_ip(),
        ]);

        $this->notify($tour, $leadId, compact('name', 'email', 'phone', 'message'));

        json_response(['success' => true, 'message' => 'Thank you! The agent will contact you shortly.']);
    }

    /**
     * List leads
     */
    public function index()
    {
        TenantMiddleware::handle();
        $tenantId = current_tenant_id();

        $filters = $this->filters();

        view('lead.index', [
            'leads' => $this->leadModel->getByTenantFiltered($tenantId, $filters),
            'filters' => $filters,
            'counts' => $this->leadModel->countByStatus($tenantId),
            'tours' => (new Tour())->getByTenant($tenantId),
        ]);
    }

    /**
     * View lead, update status and notes
     */
    public function view()
    {
        TenantMiddleware::handle();
        $tenantId = current_tenant_id();
        $leadId = (int) get_param('id');

        $lead = $this->leadModel->findWithRelations($leadId, $tenantId);
        if (!$lead) {
            flash('error', 'Lead not found');
            redirect(base_url('?route=lead'));
        }

        if (is_post()) {
            if (!csrf_verify(post('csrf_token'))) {
                flash('error', 'Invalid request');
                redirect(base_url('?route=lead/view&id=' . $leadId));
            }

            $this->leadModel->update($leadId, [
                'status' => enum_value(post('status'), Lead::STATUSES, $lead['status']),
                'notes' => sanitize(post('notes')) ?: null,
            ]);

            flash('success', 'Lead updated');
            redirect(base_url('?route=lead/view&id=' . $leadId));
        }

        view('lead.view', ['lead' => $lead]);
    }

    /**
     * Export leads as CSV (respects current filters)
     */
    public function export()
    {
        TenantMiddleware::handle();
        $leads = $this->leadModel->getByTenantFiltered(current_tenant_id(), $this->filters());

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Status', 'Tour', 'Property', 'Message', 'Notes', 'Source']);

        foreach ($leads as $lead) {
            fputcsv($out, array_map([$this, 'csvSafe'], [
                $lead['id'], $lead['created_at'], $lead['name'], $lead['email'], $lead['phone'],
                $lead['status'], $lead['tour_title'], $lead['property_title'], $lead['message'],
                $lead['notes'], $lead['source'],
            ]));
        }

        fclose($out);
        exit;
    }

    /**
     * Delete lead
     */
    public function delete()
    {
        TenantMiddleware::handle();

        if (!csrf_verify(post('csrf_token'))) {
            flash('error', 'Invalid request');
            redirect(base_url('?route=lead'));
        }

        $lead = $this->leadModel->findForTenant((int) post('id'), current_tenant_id());
        if ($lead) {
            $this->leadModel->delete($lead['id']);
            flash('success', 'Lead deleted');
        }

        redirect(base_url('?route=lead'));
    }

    private function filters()
    {
        return [
            'status' => enum_value(get_param('status'), Lead::STATUSES),
            'tour_id' => (int) get_param('tour_id') ?: null,
            'search' => sanitize(get_param('search')),
        ];
    }

    /**
     * Neutralize spreadsheet formula injection (=, +, -, @ at cell start)
     */
    private function csvSafe($value)
    {
        $value = (string) $value;
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    /**
     * Email the agency and fire the tenant webhook
     */
    private function notify(array $tour, $leadId, array $lead)
    {
        $tenant = (new Tenant())->find($tour['tenant_id']);

        $recipients = [$tenant['email']];
        foreach ((new User())->getByTenant($tenant['id']) as $user) {
            if ($user['role'] === 'tenant_admin' && $user['status'] === 'active') {
                $recipients[] = $user['email'];
            }
        }

        $body = "New enquiry from your virtual tour \"{$tour['title']}\"\n\n"
            . "Name: {$lead['name']}\nEmail: {$lead['email']}\nPhone: {$lead['phone']}\n\n"
            . "Message:\n{$lead['message']}\n\n"
            . "Open lead: " . base_url('?route=lead/view&id=' . $leadId);

        foreach (array_unique(array_map('strtolower', $recipients)) as $to) {
            Mailer::send($to, "New lead: {$lead['name']} - {$tour['title']}", $body);
        }

        Webhook::dispatch($tenant, 'lead.created', [
            'lead_id' => (int) $leadId,
            'name' => $lead['name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
            'message' => $lead['message'],
            'tour' => ['id' => (int) $tour['id'], 'title' => $tour['title'], 'url' => tour_url($tour['slug'])],
            'property' => [
                'id' => (int) $tour['property_id'],
                'title' => $tour['property_title'],
                'city' => $tour['city'],
                'price' => $tour['price'] !== null ? (float) $tour['price'] : null,
                'currency' => $tour['currency'],
            ],
        ]);
    }
}
