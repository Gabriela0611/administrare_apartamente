<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT fotografie FROM cereri_mentenanta WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cerere = mysqli_fetch_assoc($result);

    $stmt = mysqli_prepare($conn, "DELETE FROM cereri_mentenanta WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_flash('success', 'Sesizarea a fost stearsa.');
    } else {
        set_flash('error', 'Eroare: sesizarea nu a fost gasita.');
    }

    if ($cerere && !empty($cerere['fotografie']) && file_exists($cerere['fotografie'])) {
        unlink($cerere['fotografie']);
    }
} else {
    set_flash('error', 'Eroare: sesizarea nu a fost gasita.');
}

header("Location: mentenanta.php");
exit;
