<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (is_chirias()) {
    $chiriasId = (int)($_SESSION['user_chirias_id'] ?? 0);
    $stmtRestante = mysqli_prepare($conn, "SELECT COUNT(*) AS total
                                           FROM facturi f
                                           LEFT JOIN chiriasi c ON c.apartament_id = f.apartament_id
                                           WHERE c.id = ? AND f.status = 'neplatita'");
    mysqli_stmt_bind_param($stmtRestante, "i", $chiriasId);
    mysqli_stmt_execute($stmtRestante);
    $totalRestante = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtRestante));

    $stmtProbleme = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE chirias_id = ? AND status <> 'rezolvata'");
    mysqli_stmt_bind_param($stmtProbleme, "i", $chiriasId);
    mysqli_stmt_execute($stmtProbleme);
    $totalProbleme = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtProbleme));
    $totalApartamente = ['total' => 1];
    $totalChiriasi = ['total' => 1];
} else {
    $totalApartamenteResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM apartamente");
    $totalChiriasiResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM chiriasi");
    $totalRestanteResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM facturi WHERE status = 'neplatita' AND scadenta < CURDATE()");
    $totalProblemeResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'deschisa'");

    $totalApartamente = mysqli_fetch_assoc($totalApartamenteResult);
    $totalChiriasi = mysqli_fetch_assoc($totalChiriasiResult);
    $totalRestante = mysqli_fetch_assoc($totalRestanteResult);
    $totalProbleme = mysqli_fetch_assoc($totalProblemeResult);
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Dashboard</p>
                <h1>Privire generală</h1>
            </div>
        </section>

        <section class="dashboard-grid">
            <div class="summary-card">
                <span>Număr apartamente</span>
                <strong><?php echo e($totalApartamente['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Chiriași activi</span>
                <strong><?php echo e($totalChiriasi['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Facturi restante</span>
                <strong><?php echo e($totalRestante['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Probleme deschise</span>
                <strong><?php echo e($totalProbleme['total'] ?? 0); ?></strong>
            </div>
        </section>
    </main>
</body>
</html>
