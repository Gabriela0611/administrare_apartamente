<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$confirmOcupat = ($_GET['confirm_ocupat'] ?? '') === '1';

if ($id) {
    $stmtFind = mysqli_prepare($conn, "SELECT status FROM apartamente WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtFind, "i", $id);
    mysqli_stmt_execute($stmtFind);
    $apartament = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtFind));

    if (!$apartament) {
        set_flash('error', 'Eroare: apartamentul nu a fost gasit.');
        header("Location: index.php");
        exit;
    }

    if (($apartament['status'] ?? '') === 'ocupat' && !$confirmOcupat) {
        set_flash('error', 'Apartamentul este ocupat si nu poate fi sters fara confirmare.');
        header("Location: index.php");
        exit;
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM apartamente WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_flash('success', 'Apartamentul a fost sters.');
    } else {
        set_flash('error', 'Eroare: apartamentul nu a fost gasit.');
    }
} else {
    set_flash('error', 'Eroare: apartamentul nu a fost gasit.');
}

header("Location: index.php");
exit;
