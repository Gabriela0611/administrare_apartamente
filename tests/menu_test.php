<?php

declare(strict_types=1);

$_SERVER['PHP_SELF'] = '/administrare_apartamente/dashboard.php';

ob_start();
require_once __DIR__ . '/../menu.php';
ob_end_clean();

test('menu_active returns "active" when the single page matches the current page', function (): void {
    assert_same('active', menu_active('dashboard.php', 'dashboard.php'));
});

test('menu_active returns empty string when the single page does not match', function (): void {
    assert_same('', menu_active('dashboard.php', 'facturi.php'));
});

test('menu_active matches when the current page is in the page group array', function (): void {
    assert_same('active', menu_active(['facturi.php', 'adauga_factura.php'], 'adauga_factura.php'));
});

test('menu_active returns empty string when current page is not in the group', function (): void {
    assert_same('', menu_active(['facturi.php', 'adauga_factura.php'], 'plati.php'));
});

test('menu_active uses strict, case-sensitive, exact matching', function (): void {
    assert_same('', menu_active('dashboard.php', 'Dashboard.php'));
    assert_same('', menu_active('dashboard.php', 'dashboard'));
    assert_same('', menu_active('dashboard.php', ' dashboard.php'));
});

test('menu_active returns empty string for an empty page set', function (): void {
    assert_same('', menu_active([], 'dashboard.php'));
});
