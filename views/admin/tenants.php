<?php
$pageTitle = 'Manage Tenants';
ob_start();
?>

<h2 class="mb-3">Tenants</h2>

<div class="card table-responsive">
    <?php require __DIR__ . '/_tenant_table.php'; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
