<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bind_dynamic_params($stmt, $types, $params) {
    if ($types === '') {
        return;
    }

    $refs = [$stmt, $types];

    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }

    call_user_func_array('mysqli_stmt_bind_param', $refs);
}

$cautare = trim($_GET['cautare'] ?? '');
$apartamentId = (int)($_GET['apartament_id'] ?? 0);
$documente = trim($_GET['documente'] ?? '');
$contract = trim($_GET['contract'] ?? '');
$mutareDeLa = trim($_GET['mutare_de_la'] ?? '');
$mutarePanaLa = trim($_GET['mutare_pana_la'] ?? '');

$canManageChiriasi = is_admin() || is_proprietar();

$selectSql = "SELECT c.*, a.adresa AS adresa_apartament, a.numar_apartament, a.etaj
              FROM chiriasi c
              LEFT JOIN apartamente a ON c.apartament_id = a.id";

if (!in_array($documente, ['', 'complete', 'incomplete'], true)) {
    $documente = '';
}

if (!in_array($contract, ['', 'activ', 'expirat'], true)) {
    $contract = '';
}

$apartamente = mysqli_query($conn, "SELECT id, adresa, numar_apartament FROM apartamente ORDER BY adresa ASC, numar_apartament ASC");
$conditions = [];
$params = [];
$types = '';

if ($cautare !== '') {
    $termen = '%' . $cautare . '%';
    $conditions[] = "(c.nume LIKE ? OR c.prenume LIKE ? OR c.telefon LIKE ? OR c.email LIKE ? OR c.numar_contract LIKE ? OR a.adresa LIKE ? OR a.numar_apartament LIKE ?)";
    array_push($params, $termen, $termen, $termen, $termen, $termen, $termen, $termen);
    $types .= 'sssssss';
}

if ($apartamentId > 0) {
    $conditions[] = "c.apartament_id = ?";
    $params[] = $apartamentId;
    $types .= 'i';
}

if ($documente === 'complete') {
    $conditions[] = "(c.document_contract = 1 AND c.document_copie_ci = 1 AND c.document_proces_verbal = 1 AND c.document_garantie = 1)";
} elseif ($documente === 'incomplete') {
    $conditions[] = "(c.document_contract = 0 OR c.document_copie_ci = 0 OR c.document_proces_verbal = 0 OR c.document_garantie = 0)";
}

if ($contract === 'activ') {
    $conditions[] = "c.data_inceput <= CURDATE() AND c.data_sfarsit >= CURDATE()";
} elseif ($contract === 'expirat') {
    $conditions[] = "c.data_sfarsit < CURDATE()";
}

if ($mutareDeLa !== '') {
    $conditions[] = "c.data_mutarii >= ?";
    $params[] = $mutareDeLa;
    $types .= 's';
}

if ($mutarePanaLa !== '') {
    $conditions[] = "c.data_mutarii <= ?";
    $params[] = $mutarePanaLa;
    $types .= 's';
}

$sql = $selectSql;

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY c.id DESC";
$stmt = mysqli_prepare($conn, $sql);
bind_dynamic_params($stmt, $types, $params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$hasFilters = $cautare !== ''
    || $apartamentId > 0
    || $documente !== ''
    || $contract !== ''
    || $mutareDeLa !== ''
    || $mutarePanaLa !== '';

function documente_complete($row) {
    return (int)$row['document_contract'] === 1
        && (int)$row['document_copie_ci'] === 1
        && (int)$row['document_proces_verbal'] === 1
        && (int)$row['document_garantie'] === 1;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare chiria&#537;i</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Documente</p>
                <h1>Lista chiria&#537;ilor</h1>
            </div>

            <div class="header-actions">
                <?php if ($canManageChiriasi) { ?>
                    <a class="button button-primary" href="adauga_chirias.php">Adaug&#259; chiria&#537;</a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <form class="tenant-filter-form" method="GET">
            <label>
                <span>Caut&#259;</span>
                <input type="search" name="cautare" value="<?php echo e($cautare); ?>" placeholder="Ex: Popescu, 07..., CTR-01">
            </label>

            <label>
                <span>Apartament</span>
                <select name="apartament_id">
                    <option value="0">Toate apartamentele</option>
                    <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                        <?php while ($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                            <option value="<?php echo e($apartament['id']); ?>" <?php echo $apartamentId === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                <?php echo e(($apartament['numar_apartament'] ? 'Ap. ' . $apartament['numar_apartament'] . ' - ' : '') . $apartament['adresa']); ?>
                            </option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </label>

            <label>
                <span>Documente</span>
                <select name="documente">
                    <option value="" <?php echo $documente === '' ? 'selected' : ''; ?>>Toate</option>
                    <option value="complete" <?php echo $documente === 'complete' ? 'selected' : ''; ?>>Complete</option>
                    <option value="incomplete" <?php echo $documente === 'incomplete' ? 'selected' : ''; ?>>Incomplete</option>
                </select>
            </label>

            <label>
                <span>Contract</span>
                <select name="contract">
                    <option value="" <?php echo $contract === '' ? 'selected' : ''; ?>>Toate</option>
                    <option value="activ" <?php echo $contract === 'activ' ? 'selected' : ''; ?>>Activ</option>
                    <option value="expirat" <?php echo $contract === 'expirat' ? 'selected' : ''; ?>>Expirat</option>
                </select>
            </label>

            <label>
                <span>Mutare de la</span>
                <input type="date" name="mutare_de_la" value="<?php echo e($mutareDeLa); ?>">
            </label>

            <label>
                <span>Mutare p&#226;n&#259; la</span>
                <input type="date" name="mutare_pana_la" value="<?php echo e($mutarePanaLa); ?>">
            </label>

            <div class="filter-actions">
                <button class="button button-secondary" type="submit">Filtreaz&#259;</button>
                <?php if ($hasFilters) { ?>
                    <a class="button button-secondary" href="chiriasi.php">Reseteaz&#259;</a>
                <?php } ?>
            </div>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>Contact</th>
                            <th>Apartament</th>
                            <th>Data mut&#259;rii</th>
                            <th>Contract</th>
                            <th>Documente</th>
                            <th>Ac&#539;iuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) {
                                $documentClass = documente_complete($row) ? 'status-free' : 'status-occupied';
                                $documentText = documente_complete($row) ? 'Complete' : 'Incomplete';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($row['nume'] . ' ' . $row['prenume']); ?></strong><br>
                                    <span class="muted-text">CNP: <?php echo e($row['cnp']); ?></span>
                                </td>
                                <td>
                                    <?php echo e($row['telefon']); ?><br>
                                    <span class="muted-text"><?php echo e($row['email']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['adresa_apartament'])) { ?>
                                        <?php echo e(($row['numar_apartament'] ? 'Ap. ' . $row['numar_apartament'] . ' - ' : '') . $row['adresa_apartament']); ?><br>
                                        <span class="muted-text">Etaj: <?php echo e($row['etaj'] ?? '-'); ?></span>
                                    <?php } else { ?>
                                        Nesetat
                                    <?php } ?>
                                </td>
                                <td><?php echo e($row['data_mutarii']); ?></td>
                                <td><?php echo e($row['numar_contract']); ?></td>
                                <td>
                                    <span class="status-pill <?php echo e($documentClass); ?>">
                                        <?php echo e($documentText); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="button button-secondary button-small" href="contract_chirias.php?id=<?php echo e($row['id']); ?>">Contract</a>
                                        <?php if ($canManageChiriasi) { ?>
                                            <a class="button button-secondary button-small" href="editeaza_chirias.php?id=<?php echo e($row['id']); ?>">Editeaz&#259;</a>
                                            <a class="button button-danger button-small" href="sterge_chirias.php?id=<?php echo e($row['id']); ?>" onclick="return confirm('Sigur stergi acest chirias?');">&#536;terge</a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="7">Nu exist&#259; chiria&#537;i de afi&#537;at.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
