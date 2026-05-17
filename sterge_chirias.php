<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $apartament_id = null;

    $stmt = mysqli_prepare($conn, "SELECT apartament_id FROM chiriasi WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $chirias = mysqli_fetch_assoc($result);

    if ($chirias) {
        $apartament_id = (int)$chirias['apartament_id'];
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM chiriasi WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        set_flash('success', 'Chiriasul a fost sters.');
    } else {
        set_flash('error', 'Eroare: chiriasul nu a fost gasit.');
    }

    if ($apartament_id) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM chiriasi WHERE apartament_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $apartament_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!mysqli_fetch_assoc($result)) {
            $stmt = mysqli_prepare($conn, "UPDATE apartamente SET status = 'liber' WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $apartament_id);
            mysqli_stmt_execute($stmt);
        }
    }
} else {
    set_flash('error', 'Eroare: chiriasul nu a fost gasit.');
}

header("Location: chiriasi.php");
exit;
