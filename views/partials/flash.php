<?php
$success = flash('success');
$error = flash('error');
$warning = flash('warning');
$info = flash('info');
?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($warning): ?>
    <div class="alert alert-warning"><?php echo $warning; ?></div>
<?php endif; ?>

<?php if ($info): ?>
    <div class="alert alert-info"><?php echo $info; ?></div>
<?php endif; ?>
