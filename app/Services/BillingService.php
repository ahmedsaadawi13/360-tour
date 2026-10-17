<?php
/**
 * Billing Service
 *
 * Subscription lifecycle: plan changes with invoicing and payment, renewals,
 * expiry of trials and canceled subscriptions.
 */

class BillingService
{
    const PAST_DUE_GRACE_DAYS = 7;

    private $subscriptionModel;
    private $invoiceModel;
    private $paymentModel;
    private $tenantModel;
    private $gateway;

    public function __construct(PaymentGateway $gateway = null)
    {
        $this->subscriptionModel = new TenantSubscription();
        $this->invoiceModel = new Invoice();
        $this->paymentModel = new Payment();
        $this->tenantModel = new Tenant();
        $this->gateway = $gateway ?: PaymentGatewayFactory::make();
    }

    /**
     * Return the list of quota violations if the tenant moved to $plan
     */
    public function downgradeViolations($tenantId, array $plan)
    {
        $usage = $this->tenantModel->getUsageStats($tenantId);
        $checks = [
            'properties' => ['max_properties', 'properties'],
            'active_tours' => ['max_active_tours', 'published tours'],
            'scenes' => ['max_scenes', 'scenes'],
            'users' => ['max_users', 'users'],
        ];

        $violations = [];
        foreach ($checks as $usageKey => list($limitKey, $label)) {
            $limit = (int) $plan[$limitKey];
            if ($limit < 9999 && $usage[$usageKey] > $limit) {
                $violations[] = "You have {$usage[$usageKey]} {$label}; the {$plan['name']} plan allows {$limit}.";
            }
        }
        return $violations;
    }

    /**
     * Switch a tenant to a plan: invoice + charge, then activate a new period.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePlan($tenantId, array $plan, $billingCycle)
    {
        $billingCycle = enum_value($billingCycle, TenantSubscription::BILLING_CYCLES, 'monthly');
        $tenant = $this->tenantModel->find($tenantId);
        $current = $this->subscriptionModel->getActiveSubscription($tenantId);

        $violations = $this->downgradeViolations($tenantId, $plan);
        if ($violations) {
            return ['success' => false, 'message' => "Cannot switch plan:\n" . implode("\n", $violations)];
        }

        $amount = (float) ($billingCycle === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly']);
        $periodEnd = date('Y-m-d H:i:s', strtotime($billingCycle === 'yearly' ? '+1 year' : '+1 month'));

        $subscriptionData = [
            'plan_id' => $plan['id'],
            'billing_cycle' => $billingCycle,
            'status' => 'active',
            'trial_end' => null,
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => $periodEnd,
            'canceled_at' => null,
            'cancel_at_period_end' => 0,
        ];

        if ($current) {
            $subscriptionId = $current['id'];
        } else {
            $subscriptionId = $this->subscriptionModel->create(
                ['tenant_id' => $tenantId, 'status' => 'expired'] + $subscriptionData
            );
        }

        if ($amount > 0) {
            $charge = $this->invoiceAndCharge(
                $tenant,
                $subscriptionId,
                $amount,
                "{$plan['name']} plan ({$billingCycle})"
            );
            if (!$charge['success']) {
                return ['success' => false, 'message' => 'Payment failed: ' . $charge['message']];
            }
        }

        $this->subscriptionModel->update($subscriptionId, $subscriptionData);

        return ['success' => true, 'message' => "You are now on the {$plan['name']} plan ({$billingCycle})."];
    }

    /**
     * Create an invoice, charge it through the gateway and record the payment
     */
    public function invoiceAndCharge(array $tenant, $subscriptionId, $amount, $description)
    {
        $invoiceId = $this->invoiceModel->create([
            'tenant_id' => $tenant['id'],
            'subscription_id' => $subscriptionId,
            'invoice_number' => $this->invoiceModel->generateInvoiceNumber(),
            'amount' => $amount,
            'currency' => config('default_currency', 'USD'),
            'status' => 'pending',
            'due_date' => date('Y-m-d'),
            'description' => $description,
        ]);
        $invoice = $this->invoiceModel->find($invoiceId);

        $result = $this->gateway->charge($tenant, $invoice);

        $this->paymentModel->create([
            'tenant_id' => $tenant['id'],
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'currency' => $invoice['currency'],
            'method' => $this->gateway->name(),
            'transaction_id' => $result['transaction_id'] ?? null,
            'status' => $result['success'] ? 'completed' : 'failed',
            'notes' => $result['message'] ?? null,
        ]);

        if ($result['success']) {
            $this->invoiceModel->markAsPaid($invoiceId);
            $this->notifyInvoicePaid($tenant, $invoice);
        }

        return $result + ['invoice_id' => $invoiceId];
    }

    /**
     * Process subscriptions whose period ended (run from bin/cron.php)
     *
     * @return array summary counters
     */
    public function processRenewals()
    {
        $summary = ['renewed' => 0, 'expired' => 0, 'canceled' => 0, 'past_due' => 0];
        $planModel = new Plan();

        foreach ($this->subscriptionModel->getDueForRenewal() as $sub) {
            if ($sub['status'] === 'trialing') {
                // No payment method is collected during trial: the trial simply ends
                $this->subscriptionModel->update($sub['id'], ['status' => 'expired']);
                $summary['expired']++;
                continue;
            }

            if ($sub['cancel_at_period_end']) {
                $this->subscriptionModel->update($sub['id'], ['status' => 'canceled']);
                $summary['canceled']++;
                continue;
            }

            if ($sub['status'] === 'past_due'
                && strtotime($sub['current_period_end']) < strtotime('-' . self::PAST_DUE_GRACE_DAYS . ' days')) {
                $this->subscriptionModel->update($sub['id'], ['status' => 'expired']);
                $summary['expired']++;
                continue;
            }

            $plan = $planModel->find($sub['plan_id']);
            $tenant = $this->tenantModel->find($sub['tenant_id']);
            $amount = (float) ($sub['billing_cycle'] === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly']);

            $result = ['success' => true];
            if ($amount > 0) {
                $result = $this->invoiceAndCharge($tenant, $sub['id'], $amount, "{$plan['name']} plan renewal ({$sub['billing_cycle']})");
            }

            if ($result['success']) {
                $start = $sub['current_period_end'];
                $end = date('Y-m-d H:i:s', strtotime($start . ($sub['billing_cycle'] === 'yearly' ? ' +1 year' : ' +1 month')));
                $this->subscriptionModel->update($sub['id'], [
                    'status' => 'active',
                    'current_period_start' => $start,
                    'current_period_end' => $end,
                ]);
                $summary['renewed']++;
            } elseif ($sub['status'] !== 'past_due') {
                $this->subscriptionModel->update($sub['id'], ['status' => 'past_due']);
                $summary['past_due']++;
            }
        }

        return $summary;
    }

    private function notifyInvoicePaid(array $tenant, array $invoice)
    {
        Mailer::send(
            $tenant['email'],
            "Payment received - {$invoice['invoice_number']}",
            "Hello {$tenant['name']},\n\n"
            . "We received your payment of " . html_entity_decode(format_currency($invoice['amount'], $invoice['currency'])) . " for invoice {$invoice['invoice_number']}.\n"
            . "Description: {$invoice['description']}\n\n"
            . "View your billing history: " . base_url('?route=subscription/billing') . "\n\n"
            . config('name')
        );
    }
}
