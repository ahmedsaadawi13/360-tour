<?php
$pageTitle = 'Billing History';
ob_start();
?>

<h2 class="mb-3">Billing History</h2>

<div class="card mb-3">
    <h3>Invoices</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Paid Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($invoices)): ?>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo e($invoice['invoice_number']); ?></td>
                        <td><?php echo format_currency($invoice['amount'], $invoice['currency']); ?></td>
                        <td><span class="badge badge-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo e($invoice['status']); ?></span></td>
                        <td><?php echo format_date($invoice['due_date'], 'Y-m-d'); ?></td>
                        <td><?php echo $invoice['paid_at'] ? format_date($invoice['paid_at'], 'Y-m-d') : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No invoices yet</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Payment History</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo format_date($payment['paid_at'], 'Y-m-d'); ?></td>
                        <td><?php echo e($payment['invoice_number'] ?? 'N/A'); ?></td>
                        <td><?php echo format_currency($payment['amount'], $payment['currency']); ?></td>
                        <td><?php echo e($payment['method']); ?></td>
                        <td><span class="badge badge-success"><?php echo e($payment['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No payments yet</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
