<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = trim($_GET['status'] ?? '');

if ($id && in_array($status, ['platita', 'neplatita'], true)) {
    if ($status === 'platita') {
        $data_platii = date('Y-m-d');
        $stmt = mysqli_prepare($conn, "UPDATE facturi SET status = ?, data_platii = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $data_platii, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE facturi SET status = ?, data_platii = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
    }

    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) >= 0) {
        if ($status === 'platita') {
            set_flash('success', 'Factura a fost marcata ca platita.');
        } else {
            set_flash('success', 'Factura a fost marcata ca neplatita.');
        }
    }
} else {
    set_flash('error', 'Eroare: actiunea nu este valida.');
}

header("Location: facturi.php");
exit;
