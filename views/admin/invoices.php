<?php
$pageTitle = 'Invoices';
ob_start();
?>

<h2 class="mb-3">Invoices</h2>

<div class="status-tabs">
    <a href="<?php echo base_url('?route=admin/invoices'); ?>" class="<?php echo !$status ? 'active' : ''; ?>">All</a>
    <?php foreach (['pending', 'paid', 'overdue', 'canceled'] as $s): ?>
        <a href="<?php echo base_url('?route=admin/invoices&status=' . $s); ?>" class="<?php echo $status === $s ? 'active' : ''; ?>"><?php echo e(ucfirst($s)); ?></a>
    <?php endforeach; ?>
</div>

<div class="card table-responsive">
    <table class="table">
        <thead>
            <tr><th>Invoice #</th><th>Tenant</th><th>Description</th><th>Amount</th><th>Status</th><th>Created</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?php echo e($invoice['invoice_number']); ?></td>
                    <td><a href="<?php echo base_url('?route=admin/tenant-view&id=' . $invoice['tenant_id']); ?>"><?php echo e($invoice['tenant_name']); ?></a></td>
                    <td><?php echo e($invoice['description']); ?></td>
                    <td><?php echo format_currency($invoice['amount'], $invoice['currency']); ?></td>
                    <td><span class="badge badge-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo e($invoice['status']); ?></span></td>
                    <td><?php echo e(format_date($invoice['created_at'], 'Y-m-d')); ?></td>
                    <td>
                        <?php if ($invoice['status'] !== 'paid'): ?>
                            <?php echo post_button(base_url('?route=admin/invoice-mark-paid'), 'Mark paid', 'btn btn-sm btn-success', 'Record an offline payment for this invoice?', ['id' => $invoice['id']]); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$invoices): ?>
                <tr><td colspan="7" class="text-center">No invoices</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
