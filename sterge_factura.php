<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM facturi WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_flash('success', 'Factura a fost stearsa.');
    } else {
        set_flash('error', 'Eroare: factura nu a fost gasita.');
    }
} else {
    set_flash('error', 'Eroare: factura nu a fost gasita.');
}

header("Location: facturi.php");
exit;
