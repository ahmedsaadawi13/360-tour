<?php
// Flash messages are plain text: escaped here, newlines become line breaks
foreach (['success', 'error', 'warning', 'info'] as $flashType):
    $flashMessage = flash($flashType);
    if ($flashMessage):
?>
    <div class="alert alert-<?php echo $flashType; ?>" role="<?php echo $flashType === 'error' ? 'alert' : 'status'; ?>"><?php echo nl2br(e($flashMessage)); ?></div>
<?php
    endif;
endforeach;
?>
