<?php
/**
 * Invoice Model
 */

require_once __DIR__ . '/BaseModel.php';

class Invoice extends BaseModel
{
    protected $table = 'invoices';
    protected $fillable = [
        'tenant_id', 'subscription_id', 'invoice_number', 'amount', 'currency',
        'status', 'due_date', 'paid_at', 'description'
    ];

    /**
     * Get invoices by tenant
     */
    public function getByTenant($tenantId)
    {
        return $this->where(['tenant_id' => $tenantId], 'id', 'DESC');
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber()
    {
        do {
            $number = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($this->first(['invoice_number' => $number]));

        return $number;
    }

    /**
     * Mark invoice as paid
     */
    public function getAllWithTenant($status = null)
    {
        $sql = "SELECT i.*, t.name AS tenant_name
                FROM {$this->table} i
                JOIN tenants t ON i.tenant_id = t.id";
        $params = [];
        if ($status) {
            $sql .= " WHERE i.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY i.created_at DESC, i.id DESC";
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Total revenue from paid invoices
     */
    public function totalPaid()
    {
        return (float) $this->query("SELECT COALESCE(SUM(amount), 0) AS total FROM {$this->table} WHERE status = 'paid'")->fetch()['total'];
    }

    public function markAsPaid($invoiceId)
    {
        $sql = "UPDATE {$this->table} SET status = 'paid', paid_at = NOW() WHERE id = ?";
        $this->query($sql, [$invoiceId]);
    }
}
