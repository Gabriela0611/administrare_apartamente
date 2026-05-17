<?php
if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        $_SESSION['flash_messages'][$type][] = $message;
    }
}

if (!function_exists('get_flash_messages')) {
    function get_flash_messages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);

        return $messages;
    }
}
