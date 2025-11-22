<?php
$pageTitle = 'Create Property';
ob_start();
?>

<h2 class="mb-3">Create New Property</h2>

<div class="card">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <div class="form-group">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Reference Code</label>
                <input type="text" name="reference_code" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Type *</label>
                <select name="type" class="form-select" required>
                    <option value="apartment">Apartment</option>
                    <option value="villa">Villa</option>
                    <option value="office">Office</option>
                    <option value="land">Land</option>
                    <option value="penthouse">Penthouse</option>
                    <option value="townhouse">Townhouse</option>
                    <option value="warehouse">Warehouse</option>
                    <option value="shop">Shop</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="for_sale">For Sale</option>
                    <option value="for_rent">For Rent</option>
                    <option value="sold">Sold</option>
                    <option value="rented">Rented</option>
                    <option value="off_market">Off Market</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="price" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Currency</label>
                <input type="text" name="currency" class="form-control" value="USD">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Area</label>
                <input type="text" name="area" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Bedrooms</label>
                <input type="number" name="bedrooms" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Bathrooms</label>
                <input type="number" name="bathrooms" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Size (m²)</label>
                <input type="number" step="0.01" name="size" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Main Image</label>
            <input type="file" name="main_image" class="form-control" accept="image/*">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Create Property</button>
            <a href="<?php echo base_url('?route=property'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
