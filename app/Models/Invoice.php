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
        return $this->where(['tenant_id' => $tenantId], 'created_at', 'DESC');
    }

    /**
     * Generate invoice number
     */
    public function generateInvoiceNumber()
    {
        $prefix = 'INV-';
        $date = date('Ymd');
        $random = rand(1000, 9999);

        return $prefix . $date . '-' . $random;
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid($invoiceId)
    {
        $sql = "UPDATE {$this->table} SET status = 'paid', paid_at = NOW() WHERE id = ?";
        $this->query($sql, [$invoiceId]);
    }
}
