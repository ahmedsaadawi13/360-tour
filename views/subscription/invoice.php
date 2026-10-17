<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo e($invoice['invoice_number']); ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="invoice-sheet">
        <div class="flex-between mb-3">
            <div>
                <h1><?php echo e(config('name')); ?></h1>
                <div class="muted"><?php echo e(config('support_email')); ?></div>
            </div>
            <div class="text-right">
                <h2>INVOICE</h2>
                <div><?php echo e($invoice['invoice_number']); ?></div>
                <span class="badge badge-<?php echo $invoice['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo e(strtoupper($invoice['status'])); ?></span>
            </div>
        </div>

        <div class="grid-2 mb-3">
            <div>
                <strong>Billed to</strong><br>
                <?php echo e($tenant['name']); ?><br>
                <?php echo nl2br(e($tenant['address'])); ?><br>
                <?php echo e($tenant['email']); ?>
            </div>
            <div class="text-right">
                <strong>Issued:</strong> <?php echo e(format_date($invoice['created_at'], 'M j, Y')); ?><br>
                <strong>Due:</strong> <?php echo e(format_date($invoice['due_date'], 'M j, Y')); ?><br>
                <?php if ($invoice['paid_at']): ?><strong>Paid:</strong> <?php echo e(format_date($invoice['paid_at'], 'M j, Y')); ?><?php endif; ?>
            </div>
        </div>

        <table class="table">
            <thead><tr><th>Description</th><th class="text-right">Amount</th></tr></thead>
            <tbody>
                <tr><td><?php echo e($invoice['description']); ?></td><td class="text-right"><?php echo format_currency($invoice['amount'], $invoice['currency']); ?></td></tr>
                <tr><td class="text-right"><strong>Total</strong></td><td class="text-right"><strong><?php echo format_currency($invoice['amount'], $invoice['currency']); ?></strong></td></tr>
            </tbody>
        </table>

        <div class="mt-3 no-print flex gap-1">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
            <a href="<?php echo base_url('?route=subscription/billing'); ?>" class="btn btn-secondary">Back to billing</a>
        </div>
    </div>
</body>
</html>
