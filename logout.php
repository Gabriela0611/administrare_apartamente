<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';

    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0777, true);
    }

    session_save_path($sessionDir);
    session_start();
}

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
