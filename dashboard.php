<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dashboard_value($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    return $row ? (float)($row['total'] ?? 0) : 0;
}

function dashboard_rows($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    $rows = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function format_bani($value) {
    return number_format((float)$value, 2, '.', '') . ' lei';
}

function format_data($value) {
    if (empty($value)) {
        return '-';
    }

    return date('d.m.Y', strtotime($value));
}

$isTenant = is_chirias();
$chiriasId = (int)($_SESSION['user_chirias_id'] ?? 0);
$chiriasApartamentId = 0;

if ($isTenant) {
    $stmtChirias = mysqli_prepare($conn, "SELECT apartament_id FROM chiriasi WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmtChirias, "i", $chiriasId);
    mysqli_stmt_execute($stmtChirias);
    $chiriasRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtChirias));
    $chiriasApartamentId = $chiriasRow ? (int)$chiriasRow['apartament_id'] : 0;
}

if ($isTenant) {
    $totalApartamente = $chiriasApartamentId > 0 ? 1 : 0;
    $apartamenteOcupate = $chiriasApartamentId > 0 ? 1 : 0;
    $apartamenteLibere = 0;
    $totalChiriasi = 1;
    $contracteActive = dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi WHERE id = {$chiriasId} AND data_inceput <= CURDATE() AND data_sfarsit >= CURDATE()");
    $contracteExpirate = dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi WHERE id = {$chiriasId} AND data_sfarsit < CURDATE()");
    $platiRestante = dashboard_value($conn, "SELECT COUNT(*) AS total FROM facturi WHERE apartament_id = {$chiriasApartamentId} AND status = 'neplatita' AND scadenta < CURDATE()");
    $valoareRestante = dashboard_value($conn, "SELECT COALESCE(SUM(valoare_totala), 0) AS total FROM facturi WHERE apartament_id = {$chiriasApartamentId} AND status = 'neplatita' AND scadenta < CURDATE()");
    $mentenanteDeschise = dashboard_value($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE chirias_id = {$chiriasId} AND status = 'deschisa'");
    $mentenanteInLucru = dashboard_value($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE chirias_id = {$chiriasId} AND status = 'in_lucru'");
    $utilizatoriActivi = 1;
} else {
    $totalApartamente = dashboard_value($conn, "SELECT COUNT(*) AS total FROM apartamente");
    $apartamenteOcupate = dashboard_value($conn, "SELECT COUNT(*) AS total FROM apartamente WHERE status = 'ocupat'");
    $apartamenteLibere = dashboard_value($conn, "SELECT COUNT(*) AS total FROM apartamente WHERE status = 'liber'");
    $totalChiriasi = dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi");
    $contracteActive = dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi WHERE data_inceput <= CURDATE() AND data_sfarsit >= CURDATE()");
    $contracteExpirate = dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi WHERE data_sfarsit < CURDATE()");
    $platiRestante = dashboard_value($conn, "SELECT COUNT(*) AS total FROM facturi WHERE status = 'neplatita' AND scadenta < CURDATE()");
    $valoareRestante = dashboard_value($conn, "SELECT COALESCE(SUM(valoare_totala), 0) AS total FROM facturi WHERE status = 'neplatita' AND scadenta < CURDATE()");
    $mentenanteDeschise = dashboard_value($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'deschisa'");
    $mentenanteInLucru = dashboard_value($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'in_lucru'");
    $utilizatoriActivi = dashboard_value($conn, "SELECT COUNT(*) AS total FROM users");
}

$contracteExpiraCurand = $isTenant
    ? dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi WHERE id = {$chiriasId} AND data_sfarsit BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")
    : dashboard_value($conn, "SELECT COUNT(*) AS total FROM chiriasi WHERE data_sfarsit BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");

$activities = [];

if (!$isTenant) {
    $activities = array_merge($activities, dashboard_rows($conn, "SELECT 'Factura' AS tip, creat_la AS data_eveniment, CONCAT('Factura ', COALESCE(numar_factura, CONCAT('F-', id)), ' - ', FORMAT(valoare_totala, 2), ' lei') AS descriere, status FROM facturi ORDER BY creat_la DESC LIMIT 5"));
    $activities = array_merge($activities, dashboard_rows($conn, "SELECT 'Mentenanta' AS tip, creat_la AS data_eveniment, CONCAT(problema, ' - ', COALESCE(descriere, '')) AS descriere, status FROM cereri_mentenanta ORDER BY creat_la DESC LIMIT 5"));
    $activities = array_merge($activities, dashboard_rows($conn, "SELECT 'Chirias' AS tip, creat_la AS data_eveniment, CONCAT(nume, ' ', prenume, ' - contract ', numar_contract) AS descriere, CASE WHEN data_inceput <= CURDATE() AND data_sfarsit >= CURDATE() THEN 'activ' ELSE 'expirat' END AS status FROM chiriasi ORDER BY creat_la DESC LIMIT 5"));
} else {
    $activities = array_merge($activities, dashboard_rows($conn, "SELECT 'Factura' AS tip, creat_la AS data_eveniment, CONCAT('Factura ', COALESCE(numar_factura, CONCAT('F-', id)), ' - ', FORMAT(valoare_totala, 2), ' lei') AS descriere, status FROM facturi WHERE apartament_id = {$chiriasApartamentId} ORDER BY creat_la DESC LIMIT 5"));
    $activities = array_merge($activities, dashboard_rows($conn, "SELECT 'Mentenanta' AS tip, creat_la AS data_eveniment, CONCAT(problema, ' - ', COALESCE(descriere, '')) AS descriere, status FROM cereri_mentenanta WHERE chirias_id = {$chiriasId} ORDER BY creat_la DESC LIMIT 5"));
}

usort($activities, function ($a, $b) {
    return strtotime($b['data_eveniment'] ?? '1970-01-01') <=> strtotime($a['data_eveniment'] ?? '1970-01-01');
});
$activities = array_slice($activities, 0, 8);

$notifications = [];

if ($platiRestante > 0) {
    $notifications[] = [
        'titlu' => 'Plati restante',
        'text' => (int)$platiRestante . ' facturi restante in valoare de ' . format_bani($valoareRestante) . '.',
        'link' => 'facturi.php?status=neplatita'
    ];
}

if ($mentenanteDeschise > 0 || $mentenanteInLucru > 0) {
    $notifications[] = [
        'titlu' => 'Mentenanta activa',
        'text' => (int)$mentenanteDeschise . ' cereri noi si ' . (int)$mentenanteInLucru . ' in lucru.',
        'link' => 'mentenanta.php'
    ];
}

if ($contracteExpiraCurand > 0) {
    $notifications[] = [
        'titlu' => 'Contracte aproape de expirare',
        'text' => (int)$contracteExpiraCurand . ' contracte expira in urmatoarele 30 de zile.',
        'link' => 'contracte.php?status=activ'
    ];
}

if (!$isTenant && $apartamenteLibere > 0) {
    $notifications[] = [
        'titlu' => 'Apartamente libere',
        'text' => (int)$apartamenteLibere . ' apartamente sunt disponibile pentru inchiriere.',
        'link' => 'index.php?search=liber'
    ];
}

$quickLinks = $isTenant
    ? [
        ['label' => 'Facturi', 'href' => 'facturi.php'],
        ['label' => 'Plati', 'href' => 'plati.php'],
        ['label' => 'Mentenanta', 'href' => 'mentenanta.php'],
    ]
    : [
        ['label' => 'Apartamente', 'href' => 'index.php'],
        ['label' => 'Chiriasi', 'href' => 'chiriasi.php'],
        ['label' => 'Contracte', 'href' => 'contracte.php'],
        ['label' => 'Facturi', 'href' => 'facturi.php'],
        ['label' => 'Mentenanta', 'href' => 'mentenanta.php'],
    ];
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Dashboard</p>
                <h1>Privire generala</h1>
            </div>

            <div class="header-actions">
                <?php foreach ($quickLinks as $link) { ?>
                    <a class="button button-secondary" href="<?php echo e($link['href']); ?>"><?php echo e($link['label']); ?></a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <section class="dashboard-grid">
            <div class="summary-card">
                <span>Total apartamente</span>
                <strong><?php echo e((int)$totalApartamente); ?></strong>
            </div>
            <div class="summary-card">
                <span>Apartamente ocupate</span>
                <strong><?php echo e((int)$apartamenteOcupate); ?></strong>
            </div>
            <div class="summary-card">
                <span>Apartamente libere</span>
                <strong><?php echo e((int)$apartamenteLibere); ?></strong>
            </div>
            <div class="summary-card">
                <span>Total chiriasi</span>
                <strong><?php echo e((int)$totalChiriasi); ?></strong>
            </div>
            <div class="summary-card">
                <span>Contracte active</span>
                <strong><?php echo e((int)$contracteActive); ?></strong>
            </div>
            <div class="summary-card">
                <span>Contracte expirate</span>
                <strong><?php echo e((int)$contracteExpirate); ?></strong>
            </div>
            <div class="summary-card">
                <span>Plati restante</span>
                <strong><?php echo e((int)$platiRestante); ?></strong>
                <small><?php echo e(format_bani($valoareRestante)); ?></small>
            </div>
            <div class="summary-card">
                <span>Cereri mentenanta deschise</span>
                <strong><?php echo e((int)$mentenanteDeschise); ?></strong>
            </div>
            <div class="summary-card">
                <span>Mentenante in lucru</span>
                <strong><?php echo e((int)$mentenanteInLucru); ?></strong>
            </div>
            <div class="summary-card">
                <span>Utilizatori activi</span>
                <strong><?php echo e((int)$utilizatoriActivi); ?></strong>
            </div>
        </section>

        <section class="dashboard-layout">
            <div class="table-card">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Activitate</p>
                        <h2>Ultimele activitati</h2>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tip</th>
                                <th>Descriere</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($activities)) { ?>
                                <?php foreach ($activities as $activity) { ?>
                                    <tr>
                                        <td><?php echo e(format_data($activity['data_eveniment'] ?? '')); ?></td>
                                        <td><?php echo e($activity['tip'] ?? '-'); ?></td>
                                        <td><?php echo e($activity['descriere'] ?? '-'); ?></td>
                                        <td>
                                            <span class="status-pill status-info">
                                                <?php echo e($activity['status'] ?? '-'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td class="empty-state" colspan="4">Nu exista activitati recente.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="dashboard-side">
                <section class="info-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">Notificari</p>
                            <h2>Informatii importante</h2>
                        </div>
                    </div>

                    <div class="notification-list">
                        <?php if (!empty($notifications)) { ?>
                            <?php foreach ($notifications as $notification) { ?>
                                <a class="notification-item" href="<?php echo e($notification['link']); ?>">
                                    <strong><?php echo e($notification['titlu']); ?></strong>
                                    <span><?php echo e($notification['text']); ?></span>
                                </a>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="empty-state">Nu exista notificari importante.</div>
                        <?php } ?>
                    </div>
                </section>

                <section class="info-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">Navigare</p>
                            <h2>Module rapide</h2>
                        </div>
                    </div>

                    <div class="quick-link-grid">
                        <?php foreach ($quickLinks as $link) { ?>
                            <a class="quick-link" href="<?php echo e($link['href']); ?>"><?php echo e($link['label']); ?></a>
                        <?php } ?>
                    </div>
                </section>
            </aside>
        </section>
    </main>
</body>
</html>
