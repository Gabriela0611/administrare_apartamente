<?php

declare(strict_types=1);

require_once __DIR__ . '/../flash.php';

function reset_session(): void
{
    $_SESSION = [];
}

test('set_flash stores a message under its type', function (): void {
    reset_session();

    set_flash('success', 'Salvat cu succes');

    assert_same(
        ['success' => ['Salvat cu succes']],
        $_SESSION['flash_messages']
    );
});

test('set_flash appends multiple messages of the same type in order', function (): void {
    reset_session();

    set_flash('error', 'Prima eroare');
    set_flash('error', 'A doua eroare');

    assert_same(
        ['Prima eroare', 'A doua eroare'],
        $_SESSION['flash_messages']['error']
    );
});

test('set_flash keeps different types separate', function (): void {
    reset_session();

    set_flash('success', 'ok');
    set_flash('error', 'nope');

    assert_same(
        ['success' => ['ok'], 'error' => ['nope']],
        $_SESSION['flash_messages']
    );
});

test('get_flash_messages returns stored messages', function (): void {
    reset_session();
    set_flash('info', 'Detaliu');

    $messages = get_flash_messages();

    assert_same(['info' => ['Detaliu']], $messages);
});

test('get_flash_messages clears messages after reading (one-shot)', function (): void {
    reset_session();
    set_flash('info', 'Detaliu');

    get_flash_messages();

    assert_false(isset($_SESSION['flash_messages']), 'flash_messages should be unset after read');
});

test('get_flash_messages returns empty array when nothing is set', function (): void {
    reset_session();

    assert_same([], get_flash_messages());
});
