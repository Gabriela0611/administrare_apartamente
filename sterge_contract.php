<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('error', 'Eroare: contractul nu a fost gasit.');
    header("Location: contracte.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT apartament_id FROM chiriasi WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$contract = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$contract) {
    set_flash('error', 'Eroare: contractul nu a fost gasit.');
    header("Location: contracte.php");
    exit;
}

$apartamentId = (int)($contract['apartament_id'] ?? 0);
$ieri = date('Y-m-d', strtotime('-1 day'));

$stmtUpdate = mysqli_prepare($conn, "UPDATE chiriasi SET data_sfarsit = ? WHERE id = ?");
mysqli_stmt_bind_param($stmtUpdate, "si", $ieri, $id);
mysqli_stmt_execute($stmtUpdate);

if (mysqli_stmt_affected_rows($stmtUpdate) >= 0) {
    if ($apartamentId) {
        mysqli_query($conn, "UPDATE apartamente a
                             SET status = 'liber'
                             WHERE id = " . (int)$apartamentId . "
                               AND NOT EXISTS (
                                   SELECT 1 FROM chiriasi c
                                   WHERE c.apartament_id = a.id
                                     AND c.data_inceput <= CURDATE()
                                     AND c.data_sfarsit >= CURDATE()
                               )");
    }

    set_flash('success', 'Contractul a fost dezactivat.');
} else {
    set_flash('error', 'Eroare: contractul nu a putut fi dezactivat.');
}

header("Location: contracte.php");
exit;
