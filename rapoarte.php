<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$luna = date('Y-m');
$incasariResult = mysqli_query($conn, "SELECT COALESCE(SUM(suma), 0) AS total FROM facturi WHERE status = 'platita' AND DATE_FORMAT(data_platii, '%Y-%m') = '$luna'");
$restanteResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM facturi WHERE status = 'neplatita' AND scadenta < CURDATE()");
$mentenantaResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status <> 'rezolvata'");
$ocupateResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM apartamente WHERE status = 'ocupat'");

$incasari = mysqli_fetch_assoc($incasariResult);
$restante = mysqli_fetch_assoc($restanteResult);
$mentenanta = mysqli_fetch_assoc($mentenantaResult);
$ocupate = mysqli_fetch_assoc($ocupateResult);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapoarte</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Rapoarte</p>
                <h1>Situație generală</h1>
            </div>
        </section>

        <section class="dashboard-grid">
            <div class="summary-card">
                <span>Încasări luna curentă</span>
                <strong><?php echo e($incasari['total'] ?? 0); ?> lei</strong>
            </div>
            <div class="summary-card">
                <span>Facturi restante</span>
                <strong><?php echo e($restante['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Mentenanță activă</span>
                <strong><?php echo e($mentenanta['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Apartamente ocupate</span>
                <strong><?php echo e($ocupate['total'] ?? 0); ?></strong>
            </div>
        </section>
    </main>
</body>
</html>
