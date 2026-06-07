<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$_SESSION['user_logged_in'] = true;
$_SESSION['user_role'] = 'admin';
$_SERVER['PHP_SELF'] = '/administrare_apartamente/dashboard.php';

require_once __DIR__ . '/../auth.php';

function with_role(?string $role): void
{
    if ($role === null) {
        unset($_SESSION['user_role']);
        return;
    }
    $_SESSION['user_role'] = $role;
}

test('current_user_role returns the role stored in session', function (): void {
    with_role('proprietar');
    assert_same('proprietar', current_user_role());
});

test('current_user_role defaults to chirias when no role is set', function (): void {
    with_role(null);
    assert_same('chirias', current_user_role());
});

test('is_admin is true only for the admin role', function (): void {
    with_role('admin');
    assert_true(is_admin());

    with_role('proprietar');
    assert_false(is_admin());
});

test('is_proprietar is true only for the proprietar role', function (): void {
    with_role('proprietar');
    assert_true(is_proprietar());

    with_role('chirias');
    assert_false(is_proprietar());
});

test('is_chirias is true only for the chirias role', function (): void {
    with_role('chirias');
    assert_true(is_chirias());

    with_role('admin');
    assert_false(is_chirias());
});

test('admin can access any page including admin-only and unknown pages', function (): void {
    with_role('admin');
    assert_true(user_can_access_page('utilizatori.php'));
    assert_true(user_can_access_page('pagina_inexistenta.php'));
});

test('proprietar can access allowed pages but not the admin-only users page', function (): void {
    with_role('proprietar');
    assert_true(user_can_access_page('contracte.php'));
    assert_true(user_can_access_page('rapoarte.php'));
    assert_false(user_can_access_page('utilizatori.php'));
});

test('chirias can access own pages but not proprietar-only pages', function (): void {
    with_role('chirias');
    assert_true(user_can_access_page('facturi.php'));
    assert_true(user_can_access_page('adauga_mentenanta.php'));
    assert_false(user_can_access_page('chiriasi.php'));
    assert_false(user_can_access_page('utilizatori.php'));
});

test('unknown role is treated as chirias (least privilege)', function (): void {
    with_role('intrus');
    assert_true(user_can_access_page('facturi.php'));
    assert_false(user_can_access_page('contracte.php'));
});

test('page matching is exact and case-sensitive (no bypass)', function (): void {
    with_role('chirias');
    assert_false(user_can_access_page('Facturi.php'));
    assert_false(user_can_access_page('facturi'));
    assert_false(user_can_access_page(' facturi.php'));
});
