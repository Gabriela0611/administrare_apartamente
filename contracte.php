<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$status = trim($_GET['status'] ?? '');
$canManageContracts = is_admin() || is_proprietar();

if (!in_array($status, ['', 'activ', 'expirat'], true)) {
    $status = '';
}

$selectSql = "SELECT c.*, a.adresa AS adresa_apartament, a.numar_apartament, a.etaj,
                     CASE WHEN c.data_inceput <= CURDATE() AND c.data_sfarsit >= CURDATE() THEN 'activ' ELSE 'expirat' END AS status_contract
              FROM chiriasi c
              LEFT JOIN apartamente a ON c.apartament_id = a.id";

if ($status !== '') {
    $stmt = mysqli_prepare($conn, $selectSql . " HAVING status_contract = ? ORDER BY c.data_inceput DESC");
    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $selectSql . " ORDER BY c.data_inceput DESC");
}

function contract_status_class($status) {
    return $status === 'activ' ? 'status-free' : 'status-occupied';
}

function apartament_label($row) {
    if (empty($row['adresa_apartament'])) {
        return 'Nesetat';
    }

    $prefix = !empty($row['numar_apartament']) ? 'Ap. ' . $row['numar_apartament'] . ' - ' : '';
    $suffix = $row['etaj'] !== null ? ' (etaj ' . $row['etaj'] . ')' : '';

    return $prefix . $row['adresa_apartament'] . $suffix;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracte</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Contracte</p>
                <h1>Contracte de inchiriere</h1>
            </div>

            <div class="header-actions">
                <?php if ($canManageContracts) { ?>
                    <a class="button button-primary" href="adauga_contract.php">Adauga contract</a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <form class="filter-form filter-form-small" method="GET">
            <label>
                <span>Status contract</span>
                <select name="status">
                    <option value="" <?php echo $status === '' ? 'selected' : ''; ?>>Toate</option>
                    <option value="activ" <?php echo $status === 'activ' ? 'selected' : ''; ?>>Active</option>
                    <option value="expirat" <?php echo $status === 'expirat' ? 'selected' : ''; ?>>Expirate</option>
                </select>
            </label>
            <button class="button button-secondary" type="submit">Filtreaza</button>
            <?php if ($status !== '') { ?>
                <a class="button button-secondary" href="contracte.php">Reseteaza</a>
            <?php } ?>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Numar contract</th>
                            <th>Chirias</th>
                            <th>Apartament</th>
                            <th>Inceput</th>
                            <th>Sfarsit</th>
                            <th>Chirie lunara</th>
                            <th>Garantie</th>
                            <th>Status</th>
                            <th>Actiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($row['numar_contract']); ?></td>
                                <td><?php echo e($row['nume'] . ' ' . $row['prenume']); ?></td>
                                <td><?php echo e(apartament_label($row)); ?></td>
                                <td><?php echo e($row['data_inceput']); ?></td>
                                <td><?php echo e($row['data_sfarsit']); ?></td>
                                <td><?php echo e($row['chirie_lunara']); ?> lei</td>
                                <td><?php echo e($row['garantie']); ?> lei</td>
                                <td>
                                    <span class="status-pill <?php echo e(contract_status_class($row['status_contract'])); ?>">
                                        <?php echo e(ucfirst($row['status_contract'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="button button-secondary button-small" href="contract_chirias.php?id=<?php echo e($row['id']); ?>">Vezi</a>
                                        <?php if ($canManageContracts) { ?>
                                            <a class="button button-secondary button-small" href="editeaza_contract.php?id=<?php echo e($row['id']); ?>">Editeaza</a>
                                            <a class="button button-danger button-small" href="sterge_contract.php?id=<?php echo e($row['id']); ?>" onclick="return confirm('Sigur dezactivezi acest contract?');">Sterge</a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="9">Nu exista contracte de afisat.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
