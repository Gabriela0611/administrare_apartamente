<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function status_label($status) {
    $labels = [
        'deschisa' => 'Nou&#259;',
        'in_lucru' => '&#206;n lucru',
        'rezolvata' => 'Finalizat&#259;'
    ];

    return $labels[$status] ?? $status;
}

function prioritate_label($prioritate) {
    $labels = [
        'scazuta' => 'Sc&#259;zut&#259;',
        'medie' => 'Medie',
        'ridicata' => 'Ridicat&#259;',
        'urgenta' => 'Urgent&#259;'
    ];

    return $labels[$prioritate] ?? $prioritate;
}

function status_class($status) {
    if ($status === 'rezolvata') {
        return 'status-free';
    }

    if ($status === 'in_lucru') {
        return 'status-info';
    }

    return 'status-occupied';
}

$status = trim($_GET['status'] ?? '');
$apartament_id = (int)($_GET['apartament_id'] ?? 0);

if (!in_array($status, ['', 'deschisa', 'in_lucru', 'rezolvata'], true)) {
    $status = '';
}

$apartamente = mysqli_query($conn, "SELECT id, adresa, numar_apartament FROM apartamente ORDER BY adresa ASC, numar_apartament ASC");

$totalDeschiseResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'deschisa'");
$totalInLucruResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'in_lucru'");
$totalRezolvateResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'rezolvata'");
$totalUrgenteResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE prioritate = 'urgenta' AND status <> 'rezolvata'");

$totalDeschise = mysqli_fetch_assoc($totalDeschiseResult);
$totalInLucru = mysqli_fetch_assoc($totalInLucruResult);
$totalRezolvate = mysqli_fetch_assoc($totalRezolvateResult);
$totalUrgente = mysqli_fetch_assoc($totalUrgenteResult);

if (is_chirias()) {
    $chiriasId = (int)($_SESSION['user_chirias_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "SELECT m.*, a.adresa AS adresa_apartament, c.nume, c.prenume
                                   FROM cereri_mentenanta m
                                   LEFT JOIN apartamente a ON m.apartament_id = a.id
                                   LEFT JOIN chiriasi c ON m.chirias_id = c.id
                                   WHERE m.chirias_id = ?
                                   AND (? = '' OR m.status = ?)
                                   AND (? = 0 OR m.apartament_id = ?)
                                   ORDER BY m.data_raportare DESC, m.id DESC");
    mysqli_stmt_bind_param($stmt, "issii", $chiriasId, $status, $status, $apartament_id, $apartament_id);
} else {
    $stmt = mysqli_prepare($conn, "SELECT m.*, a.adresa AS adresa_apartament, c.nume, c.prenume
                                   FROM cereri_mentenanta m
                                   LEFT JOIN apartamente a ON m.apartament_id = a.id
                                   LEFT JOIN chiriasi c ON m.chirias_id = c.id
                                   WHERE (? = '' OR m.status = ?)
                                   AND (? = 0 OR m.apartament_id = ?)
                                   ORDER BY m.data_raportare DESC, m.id DESC");
    mysqli_stmt_bind_param($stmt, "ssii", $status, $status, $apartament_id, $apartament_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentenan&#539;&#259;</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">&#206;ntre&#539;inere</p>
                <h1>Cereri de mentenan&#539;&#259;</h1>
            </div>

            <div class="header-actions">
                <?php if (is_admin() || is_chirias()) { ?>
                    <a class="button button-primary" href="adauga_mentenanta.php">Adaug&#259; sesizare</a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <section class="dashboard-grid">
            <div class="summary-card">
                <span>Probleme deschise</span>
                <strong><?php echo e($totalDeschise['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>&#206;n lucru</span>
                <strong><?php echo e($totalInLucru['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Rezolvate</span>
                <strong><?php echo e($totalRezolvate['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Urgente active</span>
                <strong><?php echo e($totalUrgente['total'] ?? 0); ?></strong>
            </div>
        </section>

        <form class="filter-form filter-form-small" method="GET">
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>Toate</option>
                    <option value="deschisa" <?php echo $status === 'deschisa' ? 'selected' : ''; ?>>Noi</option>
                    <option value="in_lucru" <?php echo $status === 'in_lucru' ? 'selected' : ''; ?>>&#206;n lucru</option>
                    <option value="rezolvata" <?php echo $status === 'rezolvata' ? 'selected' : ''; ?>>Finalizate</option>
                </select>
            </label>

            <label>
                <span>Apartament</span>
                <select name="apartament_id">
                    <option value="0">Toate apartamentele</option>
                    <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                        <?php while($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                            <option value="<?php echo e($apartament['id']); ?>" <?php echo $apartament_id === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                <?php echo e(($apartament['numar_apartament'] ? 'Ap. ' . $apartament['numar_apartament'] . ' - ' : '') . $apartament['adresa']); ?>
                            </option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </label>

            <button class="button button-secondary" type="submit">Filtreaz&#259;</button>
            <?php if ($status !== '' || $apartament_id > 0) { ?>
                <a class="button button-secondary" href="mentenanta.php">Reseteaz&#259;</a>
            <?php } ?>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Apartament</th>
                            <th>Chiria&#537;</th>
                            <th>Problem&#259;</th>
                            <th>Prioritate</th>
                            <th>Status</th>
                            <th>Data raport&#259;rii</th>
                            <th>Data rezolv&#259;rii</th>
                            <th>Foto</th>
                            <th>Ac&#539;iuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($row['adresa_apartament'] ?? 'Nesetat'); ?></td>
                                <td><?php echo e(trim(($row['nume'] ?? '') . ' ' . ($row['prenume'] ?? '')) ?: 'Nesetat'); ?></td>
                                <td>
                                    <strong><?php echo e($row['problema']); ?></strong><br>
                                    <span class="muted-text"><?php echo e($row['descriere']); ?></span>
                                </td>
                                <td><?php echo prioritate_label($row['prioritate']); ?></td>
                                <td>
                                    <span class="status-pill <?php echo e(status_class($row['status'])); ?>">
                                        <?php echo status_label($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo e($row['data_raportare']); ?></td>
                                <td><?php echo e($row['data_rezolvare'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($row['fotografie']) { ?>
                                        <a class="photo-link" href="<?php echo e($row['fotografie']); ?>" target="_blank">Vezi foto</a>
                                    <?php } else { ?>
                                        <span class="muted-text">F&#259;r&#259;</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (is_admin()) { ?>
                                        <div class="row-actions">
                                            <?php if ($row['status'] !== 'rezolvata') { ?>
                                                <a class="button button-secondary button-small" href="editeaza_mentenanta.php?id=<?php echo e($row['id']); ?>">Editeaz&#259;</a>
                                            <?php } ?>
                                            <?php if ($row['status'] !== 'in_lucru') { ?>
                                                <a class="button button-secondary button-small" href="schimba_status_mentenanta.php?id=<?php echo e($row['id']); ?>&status=in_lucru">&#206;n lucru</a>
                                            <?php } ?>
                                            <?php if ($row['status'] !== 'rezolvata') { ?>
                                                <a class="button button-secondary button-small" href="schimba_status_mentenanta.php?id=<?php echo e($row['id']); ?>&status=rezolvata">Rezolvat&#259;</a>
                                            <?php } ?>
                                            <a class="button button-danger button-small" href="sterge_mentenanta.php?id=<?php echo e($row['id']); ?>" onclick="return confirm('Sigur stergi aceasta sesizare?');">&#536;terge</a>
                                        </div>
                                    <?php } else { ?>
                                        <span class="muted-text">Vizualizare</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="9">Nu exist&#259; cereri de mentenan&#539;&#259; pentru filtrul ales.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
