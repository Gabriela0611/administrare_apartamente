<?php
include_once "flash.php";

$flashMessages = get_flash_messages();
?>

<?php foreach ($flashMessages as $type => $messages) { ?>
    <div class="alert <?php echo $type === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <?php foreach ($messages as $message) { ?>
            <p><?php echo htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
    </div>
<?php } ?>
