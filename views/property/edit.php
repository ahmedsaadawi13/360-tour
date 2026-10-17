<?php
$pageTitle = 'Edit Property';
$submitLabel = 'Update Property';
ob_start();
?>

<h2 class="mb-3">Edit Property</h2>

<div class="card">
    <?php require __DIR__ . '/_form.php'; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
