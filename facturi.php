<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_bani($value) {
    return number_format((float)$value, 2, '.', '') . ' lei';
}

function tip_factura_label($tip) {
    return ucfirst(str_replace('_', ' ', (string)$tip));
}

function dashboard_total($conn, $sql, $luna) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $luna);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row ? (float)$row['total'] : 0;
}

$status = trim($_GET['status'] ?? '');
$apartament_id = (int)($_GET['apartament_id'] ?? 0);
$chirias_id = (int)($_GET['chirias_id'] ?? 0);
$luna = trim($_GET['luna'] ?? '');
$lunaDashboard = $luna !== '' ? $luna : date('Y-m');
$chiriasApartamentId = 0;
$canManageFacturi = is_admin() || is_proprietar();

if (!in_array($status, ['', 'platita', 'neplatita'], true)) {
    $status = '';
}

if (is_chirias()) {
    $chiriasId = (int)($_SESSION['user_chirias_id'] ?? 0);
    $stmtChirias = mysqli_prepare($conn, "SELECT apartament_id FROM chiriasi WHERE id = ?");
    mysqli_stmt_bind_param($stmtChirias, "i", $chiriasId);
    mysqli_stmt_execute($stmtChirias);
    $chiriasResult = mysqli_stmt_get_result($stmtChirias);
    $chiriasRow = mysqli_fetch_assoc($chiriasResult);
    $chiriasApartamentId = $chiriasRow ? (int)$chiriasRow['apartament_id'] : -1;
    $apartamente = mysqli_query($conn, "SELECT id, adresa FROM apartamente WHERE id = " . (int)$chiriasApartamentId);
} else {
    $apartamente = mysqli_query($conn, "SELECT id, adresa FROM apartamente ORDER BY adresa ASC");
    $chiriasi = mysqli_query($conn, "SELECT id, nume, prenume FROM chiriasi ORDER BY nume ASC, prenume ASC");
}

$totalIncasari = dashboard_total(
    $conn,
    "SELECT COALESCE(SUM(valoare_totala), 0) AS total FROM facturi WHERE status = 'platita' AND DATE_FORMAT(data_platii, '%Y-%m') = ?",
    $lunaDashboard
);

$venitChirie = dashboard_total(
    $conn,
    "SELECT COALESCE(SUM(valoare_chirie), 0) AS total FROM facturi WHERE status = 'platita' AND DATE_FORMAT(data_platii, '%Y-%m') = ?",
    $lunaDashboard
);

$cheltuieli = dashboard_total(
    $conn,
    "SELECT COALESCE(SUM(cost_utilitati + cost_mentenanta), 0) AS total FROM facturi WHERE status = 'platita' AND DATE_FORMAT(data_platii, '%Y-%m') = ?",
    $lunaDashboard
);

$restanteResult = mysqli_query($conn, "SELECT COALESCE(SUM(valoare_totala), 0) AS total, COUNT(*) AS numar FROM facturi WHERE status = 'neplatita' AND scadenta < CURDATE()");
$restante = mysqli_fetch_assoc($restanteResult);
$profitLunar = $venitChirie - $cheltuieli;

if (is_chirias()) {
    $stmt = mysqli_prepare($conn, "SELECT f.*, DATE_FORMAT(f.scadenta, '%Y-%m') AS luna_aferenta, a.adresa AS adresa_apartament, c.nume, c.prenume
                                   FROM facturi f
                                   LEFT JOIN apartamente a ON f.apartament_id = a.id
                                   LEFT JOIN chiriasi c ON f.chirias_id = c.id
                                   WHERE f.apartament_id = ?
                                   AND (? = '' OR f.status = ?)
                                   AND (? = '' OR DATE_FORMAT(f.scadenta, '%Y-%m') = ?)
                                   ORDER BY f.scadenta ASC, f.id DESC");
    mysqli_stmt_bind_param($stmt, "issss", $chiriasApartamentId, $status, $status, $luna, $luna);
} else {
    $stmt = mysqli_prepare($conn, "SELECT f.*, DATE_FORMAT(f.scadenta, '%Y-%m') AS luna_aferenta, a.adresa AS adresa_apartament, c.nume, c.prenume
                                   FROM facturi f
                                   LEFT JOIN apartamente a ON f.apartament_id = a.id
                                   LEFT JOIN chiriasi c ON f.chirias_id = c.id
                                   WHERE (? = '' OR f.status = ?)
                                   AND (? = 0 OR f.apartament_id = ?)
                                   AND (? = 0 OR f.chirias_id = ?)
                                   AND (? = '' OR DATE_FORMAT(f.scadenta, '%Y-%m') = ?)
                                   ORDER BY f.scadenta ASC, f.id DESC");
    mysqli_stmt_bind_param($stmt, "ssiiiiss", $status, $status, $apartament_id, $apartament_id, $chirias_id, $chirias_id, $luna, $luna);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare facturi</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Facturi</p>
                <h1>Administrare facturi</h1>
            </div>

            <div class="header-actions">
                <?php if ($canManageFacturi) { ?>
                    <a class="button button-primary" href="adauga_factura.php">Genereaz&#259; factur&#259;</a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <section class="dashboard-grid">
            <div class="summary-card">
                <span>Total &#238;ncas&#259;ri <?php echo e($lunaDashboard); ?></span>
                <strong><?php echo e(format_bani($totalIncasari)); ?></strong>
            </div>
            <div class="summary-card">
                <span>Facturi restante</span>
                <strong><?php echo e(format_bani($restante['total'] ?? 0)); ?></strong>
                <small><?php echo e($restante['numar'] ?? 0); ?> facturi</small>
            </div>
            <div class="summary-card">
                <span>Profit lunar <?php echo e($lunaDashboard); ?></span>
                <strong><?php echo e(format_bani($profitLunar)); ?></strong>
            </div>
        </section>

        <form class="filter-form" method="GET">
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>Toate</option>
                    <option value="neplatita" <?php echo $status === 'neplatita' ? 'selected' : ''; ?>>Facturi neachitate</option>
                    <option value="platita" <?php echo $status === 'platita' ? 'selected' : ''; ?>>Facturi pl&#259;tite</option>
                </select>
            </label>

            <?php if (!is_chirias()) { ?>
                <label>
                    <span>Apartament</span>
                    <select name="apartament_id">
                        <option value="0">Toate apartamentele</option>
                        <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                            <?php while($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                                <option value="<?php echo e($apartament['id']); ?>" <?php echo $apartament_id === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($apartament['adresa']); ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </label>
            <?php } ?>

            <?php if (!is_chirias()) { ?>
                <label>
                    <span>Chiria&#537;</span>
                    <select name="chirias_id">
                        <option value="0">To&#539;i chiria&#537;ii</option>
                        <?php if ($chiriasi && mysqli_num_rows($chiriasi) > 0) { ?>
                            <?php while($chirias = mysqli_fetch_assoc($chiriasi)) { ?>
                                <option value="<?php echo e($chirias['id']); ?>" <?php echo $chirias_id === (int)$chirias['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($chirias['nume'] . ' ' . $chirias['prenume']); ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </label>
            <?php } ?>

            <label>
                <span>Lun&#259;</span>
                <input type="month" name="luna" value="<?php echo e($luna); ?>">
            </label>

            <button class="button button-secondary" type="submit">Filtreaz&#259;</button>
            <?php if ($status !== '' || $apartament_id > 0 || $chirias_id > 0 || $luna !== '') { ?>
                <a class="button button-secondary" href="facturi.php">Reseteaz&#259;</a>
            <?php } ?>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Apartament</th>
                            <th>Tip factur&#259;</th>
                            <th>Chiria&#537;</th>
                            <th>Luna aferent&#259;</th>
                            <th>Chirie</th>
                            <th>Utilit&#259;&#539;i</th>
                            <th>Mentenan&#539;&#259;</th>
                            <th>Total</th>
                            <th>Emitere</th>
                            <th>Scaden&#539;&#259;</th>
                            <th>Status</th>
                            <th>Data pl&#259;&#539;ii</th>
                            <th>Ac&#539;iuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) {
                                $statusClass = $row['status'] === 'platita' ? 'status-free' : 'status-occupied';
                            ?>
                            <tr>
                                <td><?php echo e($row['adresa_apartament'] ?? 'Nesetat'); ?></td>
                                <td><?php echo e(tip_factura_label($row['tip_factura'] ?? '-')); ?></td>
                                <td><?php echo e(trim(($row['nume'] ?? '') . ' ' . ($row['prenume'] ?? '')) ?: 'Nesetat'); ?></td>
                                <td><?php echo e($row['luna_aferenta']); ?></td>
                                <td><?php echo e(format_bani($row['valoare_chirie'])); ?></td>
                                <td><?php echo e(format_bani($row['cost_utilitati'])); ?></td>
                                <td><?php echo e(format_bani($row['cost_mentenanta'])); ?></td>
                                <td><?php echo e(format_bani($row['valoare_totala'])); ?></td>
                                <td><?php echo e($row['data_emitere'] ?: '-'); ?></td>
                                <td><?php echo e($row['scadenta']); ?></td>
                                <td>
                                    <span class="status-pill <?php echo e($statusClass); ?>">
                                        <?php echo e(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo e($row['data_platii'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($canManageFacturi) { ?>
                                        <div class="row-actions">
                                            <a class="button button-secondary button-small" href="factura_detail.php?id=<?php echo e($row['id']); ?>">Vezi</a>
                                            <a class="button button-secondary button-small" href="editeaza_factura.php?id=<?php echo e($row['id']); ?>">Editeaz&#259;</a>
                                            <?php if ($row['status'] === 'neplatita') { ?>
                                                <a class="button button-secondary button-small" href="schimba_status_factura.php?id=<?php echo e($row['id']); ?>&status=platita">Pl&#259;te&#537;te</a>
                                            <?php } else { ?>
                                                <a class="button button-secondary button-small" href="schimba_status_factura.php?id=<?php echo e($row['id']); ?>&status=neplatita">Nepl&#259;tit&#259;</a>
                                            <?php } ?>
                                            <a class="button button-danger button-small" href="sterge_factura.php?id=<?php echo e($row['id']); ?>" onclick="return confirm('Sigur stergi aceasta factura?');">&#536;terge</a>
                                        </div>
                                    <?php } else { ?>
                                        <span class="muted-text">Vizualizare</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="13">Nu exist&#259; facturi pentru filtrele alese.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
