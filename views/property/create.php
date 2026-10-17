<?php
$pageTitle = 'Create Property';
$fields = ['title', 'reference_code', 'type', 'status', 'price', 'currency', 'city', 'area', 'address', 'bedrooms', 'bathrooms', 'size', 'description'];
$property = [];
foreach ($fields as $field) {
    $property[$field] = old($field, null);
}
$submitLabel = 'Create Property';
ob_start();
?>

<h2 class="mb-3">Create New Property</h2>

<div class="card">
    <?php require __DIR__ . '/_form.php'; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
