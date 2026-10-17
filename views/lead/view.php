<?php
$pageTitle = 'Lead';
ob_start();
?>

<div class="flex-between mb-3">
    <h2><?php echo e($lead['name']); ?></h2>
    <a href="<?php echo base_url('?route=lead'); ?>" class="btn btn-secondary">← All leads</a>
</div>

<div class="grid-2-1">
    <div class="card">
        <table class="table">
            <tr><td><strong>Email</strong></td><td><a href="mailto:<?php echo e($lead['email']); ?>"><?php echo e($lead['email']); ?></a></td></tr>
            <tr><td><strong>Phone</strong></td><td>
                <?php if ($lead['phone']): ?>
                    <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $lead['phone'])); ?>"><?php echo e($lead['phone']); ?></a>
                    · <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $lead['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a>
                <?php else: ?>—<?php endif; ?>
            </td></tr>
            <tr><td><strong>Received</strong></td><td><?php echo e(format_date($lead['created_at'], 'M j, Y H:i')); ?> (via <?php echo e($lead['source']); ?>)</td></tr>
            <tr><td><strong>Tour</strong></td><td>
                <?php if ($lead['tour_id']): ?>
                    <a href="<?php echo base_url('?route=tour/view&id=' . $lead['tour_id']); ?>"><?php echo e($lead['tour_title']); ?></a>
                <?php else: ?>—<?php endif; ?>
            </td></tr>
            <tr><td><strong>Property</strong></td><td>
                <?php if ($lead['property_id']): ?>
                    <a href="<?php echo base_url('?route=property/view&id=' . $lead['property_id']); ?>"><?php echo e($lead['property_title']); ?></a>
                <?php else: ?>—<?php endif; ?>
            </td></tr>
        </table>
        <h3 class="mt-2">Message</h3>
        <p><?php echo $lead['message'] ? nl2br(e($lead['message'])) : '<span class="muted">No message</span>'; ?></p>
    </div>

    <div>
        <div class="card">
            <h3 class="mb-2">Follow-up</h3>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <?php foreach (Lead::STATUSES as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $lead['status'] === $status ? 'selected' : ''; ?>><?php echo e(humanize($status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="notes">Internal notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="5"><?php echo e($lead['notes']); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Save</button>
            </form>
        </div>
        <div class="card">
            <?php echo post_button(base_url('?route=lead/delete'), 'Delete lead', 'btn btn-danger', 'Delete this lead permanently?', ['id' => $lead['id']]); ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
