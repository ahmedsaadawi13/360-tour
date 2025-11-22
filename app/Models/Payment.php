<?php
/**
 * Payment Model
 */

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel
{
    protected $table = 'payments';
    protected $fillable = [
        'tenant_id', 'invoice_id', 'amount', 'currency', 'method',
        'transaction_id', 'status', 'paid_at', 'notes'
    ];

    /**
     * Get payments by tenant
     */
    public function getByTenant($tenantId)
    {
        $sql = "SELECT p.*, i.invoice_number
                FROM {$this->table} p
                LEFT JOIN invoices i ON p.invoice_id = i.id
                WHERE p.tenant_id = ?
                ORDER BY p.paid_at DESC";

        $stmt = $this->query($sql, [$tenantId]);
        return $stmt->fetchAll();
    }
}
