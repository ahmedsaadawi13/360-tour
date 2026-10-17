<?php
$pageTitle = 'Leads';
$query = function (array $override) use ($filters) {
    $params = array_filter(array_merge(['route' => 'lead'], $filters, $override));
    return base_url('?' . http_build_query($params));
};
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Leads</h2>
    <a href="<?php echo e($query(['route' => 'lead/export'])); ?>" class="btn btn-secondary">Export CSV</a>
</div>

<div class="status-tabs">
    <a href="<?php echo e($query(['status' => null])); ?>" class="<?php echo !$filters['status'] ? 'active' : ''; ?>">All (<?php echo array_sum($counts); ?>)</a>
    <?php foreach (Lead::STATUSES as $status): ?>
        <a href="<?php echo e($query(['status' => $status])); ?>" class="<?php echo $filters['status'] === $status ? 'active' : ''; ?>"><?php echo e(humanize($status)); ?> (<?php echo (int) $counts[$status]; ?>)</a>
    <?php endforeach; ?>
</div>

<form method="GET" action="<?php echo base_url(); ?>" class="card filters">
    <input type="hidden" name="route" value="lead">
    <?php if ($filters['status']): ?>
        <input type="hidden" name="status" value="<?php echo e($filters['status']); ?>">
    <?php endif; ?>
    <div class="form-group">
        <label class="form-label" for="search">Search</label>
        <input type="search" id="search" name="search" class="form-control" placeholder="Name, email or phone" value="<?php echo e($filters['search']); ?>">
    </div>
    <div class="form-group">
        <label class="form-label" for="tour_id">Tour</label>
        <select id="tour_id" name="tour_id" class="form-select">
            <option value="">All tours</option>
            <?php foreach ($tours as $tour): ?>
                <option value="<?php echo (int) $tour['id']; ?>" <?php echo $filters['tour_id'] == $tour['id'] ? 'selected' : ''; ?>><?php echo e($tour['title']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>

<div class="card table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Tour</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($leads): ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?php echo e(format_date($lead['created_at'], 'M j, Y H:i')); ?></td>
                        <td><a href="<?php echo base_url('?route=lead/view&id=' . $lead['id']); ?>"><?php echo e($lead['name']); ?></a></td>
                        <td><?php echo e($lead['email']); ?><br><span class="muted"><?php echo e($lead['phone']); ?></span></td>
                        <td><?php echo e($lead['tour_title'] ?: '—'); ?></td>
                        <td><span class="badge badge-<?php echo $lead['status'] === 'new' ? 'warning' : ($lead['status'] === 'lost' ? 'danger' : 'info'); ?>"><?php echo e(humanize($lead['status'])); ?></span></td>
                        <td><a href="<?php echo base_url('?route=lead/view&id=' . $lead['id']); ?>" class="btn btn-sm btn-secondary">Open</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No leads yet. Leads appear here when visitors use "Contact agent" in your published tours.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
