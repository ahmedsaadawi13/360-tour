<?php
$pageTitle = 'Team';
$maxUsers = $subscription['max_users'] ?? 0;
ob_start();
?>

<div class="flex-between mb-3">
    <h2>Team <span class="muted">(<?php echo count($users); ?> / <?php echo e(format_limit($maxUsers)); ?> users)</span></h2>
    <a href="<?php echo base_url('?route=team/create'); ?>" class="btn btn-primary">+ Invite Member</a>
</div>

<div class="card table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $member): ?>
                <tr>
                    <td><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?>
                        <?php if ((int) $member['id'] === (int) session_get('user_id')): ?><span class="muted">(you)</span><?php endif; ?></td>
                    <td><?php echo e($member['email']); ?></td>
                    <td><span class="badge badge-info"><?php echo $member['role'] === 'tenant_admin' ? 'Admin' : 'Agent'; ?></span></td>
                    <td><span class="badge badge-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e($member['status']); ?></span></td>
                    <td><?php echo $member['last_login'] ? e(format_date($member['last_login'], 'M j, Y')) : '<span class="muted">Invitation pending</span>'; ?></td>
                    <td class="flex gap-1">
                        <a href="<?php echo base_url('?route=team/edit&id=' . $member['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <?php if ((int) $member['id'] !== (int) session_get('user_id')): ?>
                            <?php echo post_button(base_url('?route=team/delete'), 'Remove', 'btn btn-sm btn-danger', 'Remove this team member?', ['id' => $member['id']]); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Roles</h3>
    <p><strong>Admin</strong> — everything, including team, branding, subscription and billing.<br>
       <strong>Agent</strong> — manages properties, tours, scenes and leads.</p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
