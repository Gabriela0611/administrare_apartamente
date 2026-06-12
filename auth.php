<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';

    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0777, true);
    }

    session_save_path($sessionDir);
    session_start();
}

if (empty($_SESSION['user_logged_in']) && !empty($_SESSION['admin_logged_in'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_email'] = $_SESSION['admin_email'] ?? 'admin@test.com';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_chirias_id'] = null;
}

if (empty($_SESSION['user_logged_in'])) {
    header("Location: login.php");
    exit;
}

if (!function_exists('current_user_role')) {
    function current_user_role() {
        return $_SESSION['user_role'] ?? 'chirias';
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return current_user_role() === 'admin';
    }
}

if (!function_exists('is_proprietar')) {
    function is_proprietar() {
        return current_user_role() === 'proprietar';
    }
}

if (!function_exists('is_chirias')) {
    function is_chirias() {
        return current_user_role() === 'chirias';
    }
}

if (!function_exists('user_can_access_page')) {
    function user_can_access_page($page) {
        if (is_admin()) {
            return true;
        }

        $proprietarPages = [
            'dashboard.php',
            'index.php',
            'adauga_apartament.php',
            'editeaza_apartament.php',
            'chiriasi.php',
            'adauga_chirias.php',
            'editeaza_chirias.php',
            'documente.php',
            'contracte.php',
            'adauga_contract.php',
            'editeaza_contract.php',
            'sterge_contract.php',
            'contract_chirias.php',
            'facturi.php',
            'adauga_factura.php',
            'editeaza_factura.php',
            'factura_detail.php',
            'schimba_status_factura.php',
            'sterge_factura.php',
            'plati.php',
            'mentenanta.php',
            'adauga_mentenanta.php',
            'editeaza_mentenanta.php',
            'rapoarte.php',
            'logout.php'
        ];

        $chiriasPages = [
            'dashboard.php',
            'facturi.php',
            'plati.php',
            'mentenanta.php',
            'adauga_mentenanta.php',
            'editeaza_mentenanta.php',
            'logout.php'
        ];

        if (is_proprietar()) {
            return in_array($page, $proprietarPages, true);
        }

        return in_array($page, $chiriasPages, true);
    }
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

if (!user_can_access_page($currentPage)) {
    header("Location: dashboard.php");
    exit;
}
