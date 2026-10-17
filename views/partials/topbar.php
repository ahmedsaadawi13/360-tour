<?php $user = current_user(); ?>

<div class="top-bar">
    <div class="flex" style="align-items: center;">
        <button type="button" class="sidebar-toggle" aria-label="Toggle menu"
                onclick="document.querySelector('.sidebar').classList.toggle('open')">&#9776;</button>
        <h1><?php echo e($pageTitle ?? 'Dashboard'); ?></h1>
    </div>

    <div class="user-menu">
        <?php if ($user): ?>
            <span>Welcome, <a href="<?php echo base_url('?route=settings/profile'); ?>"><?php echo e($user['first_name']); ?></a>!</span>
            <form method="POST" action="<?php echo base_url('?route=auth/logout'); ?>" class="inline-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-sm btn-danger">Logout</button>
            </form>
        <?php endif; ?>
    </div>
</div>
