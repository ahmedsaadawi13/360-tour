<?php
$pageTitle = 'Edit Plan';
$submitLabel = 'Update Plan';

ob_start();
?>

<h2 class="mb-3">Edit Plan</h2>

<div class="card">
    <?php require __DIR__ . '/_plan_form.php'; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
