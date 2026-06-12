<?php
// Send unauthenticated visitors to the landing page instead of the login form.
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';
    session_save_path($sessionDir);
    session_start();
}
if (empty($_SESSION['user_logged_in'])) {
    header("Location: landing.php");
    exit;
}

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
$statusFilter = trim($_GET['status'] ?? '');
$numarCamereFilter = trim($_GET['numar_camere'] ?? '');
$etajFilter = trim($_GET['etaj'] ?? '');
$suprafataMin = trim($_GET['suprafata_min'] ?? '');
$suprafataMax = trim($_GET['suprafata_max'] ?? '');
$chirieMin = trim($_GET['chirie_min'] ?? '');
$chirieMax = trim($_GET['chirie_max'] ?? '');

if (!in_array($statusFilter, ['', 'liber', 'ocupat'], true)) {
    $statusFilter = '';
}

$totalApartamenteResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM apartamente");
$totalChiriasiResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM chiriasi");
$totalRestanteResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM facturi WHERE status = 'neplatita' AND scadenta < CURDATE()");
$totalProblemeResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cereri_mentenanta WHERE status = 'deschisa'");

$totalApartamente = mysqli_fetch_assoc($totalApartamenteResult);
$totalChiriasi = mysqli_fetch_assoc($totalChiriasiResult);
$totalRestante = mysqli_fetch_assoc($totalRestanteResult);
$totalProbleme = mysqli_fetch_assoc($totalProblemeResult);

$conditions = [];
$params = [];
$types = '';

if ($cautare !== '') {
    $termen = '%' . $cautare . '%';
    $conditions[] = "(adresa LIKE ? OR numar_apartament LIKE ? OR CAST(etaj AS CHAR) LIKE ? OR observatii LIKE ? OR status LIKE ?)";
    array_push($params, $termen, $termen, $termen, $termen, $termen);
    $types .= 'sssss';
}

if ($statusFilter !== '') {
    $conditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($numarCamereFilter !== '' && ctype_digit($numarCamereFilter)) {
    $conditions[] = "numar_camere = ?";
    $params[] = (int)$numarCamereFilter;
    $types .= 'i';
}

if ($etajFilter !== '' && is_numeric($etajFilter)) {
    $conditions[] = "etaj = ?";
    $params[] = (int)$etajFilter;
    $types .= 'i';
}

if ($suprafataMin !== '' && is_numeric(str_replace(',', '.', $suprafataMin))) {
    $conditions[] = "suprafata >= ?";
    $params[] = (float)str_replace(',', '.', $suprafataMin);
    $types .= 'd';
}

if ($suprafataMax !== '' && is_numeric(str_replace(',', '.', $suprafataMax))) {
    $conditions[] = "suprafata <= ?";
    $params[] = (float)str_replace(',', '.', $suprafataMax);
    $types .= 'd';
}

if ($chirieMin !== '' && is_numeric(str_replace(',', '.', $chirieMin))) {
    $conditions[] = "chirie >= ?";
    $params[] = (float)str_replace(',', '.', $chirieMin);
    $types .= 'd';
}

if ($chirieMax !== '' && is_numeric(str_replace(',', '.', $chirieMax))) {
    $conditions[] = "chirie <= ?";
    $params[] = (float)str_replace(',', '.', $chirieMax);
    $types .= 'd';
}

$sql = "SELECT * FROM apartamente";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);
bind_dynamic_params($stmt, $types, $params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$hasFilters = $cautare !== ''
    || $statusFilter !== ''
    || $numarCamereFilter !== ''
    || $etajFilter !== ''
    || $suprafataMin !== ''
    || $suprafataMax !== ''
    || $chirieMin !== ''
    || $chirieMax !== '';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare apartamente</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Administrare</p>
                <h1>Lista apartamentelor</h1>
            </div>

            <div class="header-actions">
                <?php if (is_admin()) { ?>
                    <a class="button button-primary" href="adauga_apartament.php">Adaug&#259; apartament</a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <section class="dashboard-grid">
            <div class="summary-card">
                <span>Num&#259;r apartamente</span>
                <strong><?php echo e($totalApartamente['total'] ?? 0); ?></strong>
            </div>
            <div class="summary-card">
                <span>Chiria&#537;i activi</span>
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

        <form class="apartment-filter-form" method="GET">
            <label>
                <span>Caut&#259;</span>
                <input type="search" name="cautare" value="<?php echo e($cautare); ?>" placeholder="Ex: 12, Strada Libertatii, liber">
            </label>

            <label>
                <span>Status</span>
                <select name="status">
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>Toate</option>
                    <option value="liber" <?php echo $statusFilter === 'liber' ? 'selected' : ''; ?>>Liber</option>
                    <option value="ocupat" <?php echo $statusFilter === 'ocupat' ? 'selected' : ''; ?>>Ocupat</option>
                </select>
            </label>

            <label>
                <span>Camere</span>
                <input type="number" min="1" name="numar_camere" value="<?php echo e($numarCamereFilter); ?>">
            </label>

            <label>
                <span>Etaj</span>
                <input type="number" name="etaj" value="<?php echo e($etajFilter); ?>">
            </label>

            <label>
                <span>Suprafa&#539;&#259; minim&#259;</span>
                <input type="number" step="0.01" min="0" name="suprafata_min" value="<?php echo e($suprafataMin); ?>">
            </label>

            <label>
                <span>Suprafa&#539;&#259; maxim&#259;</span>
                <input type="number" step="0.01" min="0" name="suprafata_max" value="<?php echo e($suprafataMax); ?>">
            </label>

            <label>
                <span>Chirie minim&#259;</span>
                <input type="number" step="0.01" min="0" name="chirie_min" value="<?php echo e($chirieMin); ?>">
            </label>

            <label>
                <span>Chirie maxim&#259;</span>
                <input type="number" step="0.01" min="0" name="chirie_max" value="<?php echo e($chirieMax); ?>">
            </label>

            <div class="filter-actions">
                <button class="button button-secondary" type="submit">Filtreaz&#259;</button>
            <?php if ($hasFilters) { ?>
                <a class="button button-secondary" href="index.php">Reseteaz&#259;</a>
            <?php } ?>
            </div>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Num&#259;r apartament</th>
                            <th>Etaj</th>
                            <th>Adres&#259;</th>
                            <th>Num&#259;r camere</th>
                            <th>Suprafa&#539;&#259;</th>
                            <th>Chirie lunar&#259;</th>
                            <th>Status</th>
                            <th>Ac&#539;iuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) {
                                $statusClass = $row['status'] === 'ocupat' ? 'status-occupied' : 'status-free';
                            ?>
                            <tr>
                                <td><?php echo e($row['id']); ?></td>
                                <td><?php echo e($row['numar_apartament'] ?? '-'); ?></td>
                                <td><?php echo e($row['etaj'] ?? '-'); ?></td>
                                <td><?php echo e($row['adresa']); ?></td>
                                <td><?php echo e($row['numar_camere']); ?></td>
                                <td><?php echo e($row['suprafata']); ?> m&sup2;</td>
                                <td><?php echo e($row['chirie']); ?> lei</td>
                                <td>
                                    <span class="status-pill <?php echo e($statusClass); ?>">
                                        <?php echo e(ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (is_admin()) { ?>
                                        <div class="row-actions">
                                            <a class="button button-secondary button-small" href="editeaza_apartament.php?id=<?php echo e($row['id']); ?>">Editeaz&#259;</a>
                                            <a class="button button-danger button-small" href="sterge_apartament.php?id=<?php echo e($row['id']); ?><?php echo $row['status'] === 'ocupat' ? '&confirm_ocupat=1' : ''; ?>" onclick="return confirm('<?php echo $row['status'] === 'ocupat' ? 'Apartamentul este ocupat. Sigur vrei sa il stergi?' : 'Sigur stergi acest apartament?'; ?>');">&#536;terge</a>
                                        </div>
                                    <?php } else { ?>
                                        <span class="muted-text">Vizualizare</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="9">Nu exist&#259; apartamente de afi&#537;at.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
