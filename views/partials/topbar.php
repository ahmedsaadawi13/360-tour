<?php $user = current_user(); ?>

<div class="top-bar">
    <div>
        <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
    </div>

    <div class="user-menu">
        <?php if ($user): ?>
            <span>Welcome, <?php echo e($user['first_name']); ?>!</span>
            <a href="<?php echo base_url('?route=auth/logout'); ?>" class="btn btn-sm btn-danger">Logout</a>
        <?php endif; ?>
    </div>
</div>
