<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = trim($_GET['status'] ?? '');

if ($id && in_array($status, ['deschisa', 'in_lucru', 'rezolvata'], true)) {
    if ($status === 'rezolvata') {
        $dataRezolvare = date('Y-m-d');
        $stmt = mysqli_prepare($conn, "UPDATE cereri_mentenanta SET status = ?, data_rezolvare = COALESCE(data_rezolvare, ?) WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $dataRezolvare, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE cereri_mentenanta SET status = ?, data_rezolvare = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
    }
    mysqli_stmt_execute($stmt);

    set_flash('success', 'Statusul sesizarii a fost actualizat.');
} else {
    set_flash('error', 'Eroare: actiunea nu este valida.');
}

header("Location: mentenanta.php");
exit;
