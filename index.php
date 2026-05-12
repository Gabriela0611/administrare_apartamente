<?php
include "config/db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$cautare = trim($_GET['cautare'] ?? '');

if ($cautare !== '') {
    $termen = '%' . $cautare . '%';
    $stmt = mysqli_prepare($conn, "SELECT * FROM apartamente WHERE adresa LIKE ? OR status LIKE ? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "ss", $termen, $termen);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT * FROM apartamente ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare apartamente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Administrare</p>
                <h1>Lista apartamentelor</h1>
            </div>

            <a class="button button-primary" href="adauga_apartament.php">Adaug&#259; apartament</a>
        </section>

        <form class="search-form" method="GET">
            <label>
                <span>Caut&#259; dup&#259; adres&#259; sau status</span>
                <input type="search" name="cautare" value="<?php echo e($cautare); ?>" placeholder="Ex: Strada Libertatii, liber">
            </label>
            <button class="button button-secondary" type="submit">Caut&#259;</button>
            <?php if ($cautare !== '') { ?>
                <a class="button button-secondary" href="index.php">Reseteaz&#259;</a>
            <?php } ?>
        </form>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Adres&#259;</th>
                            <th>Num&#259;r camere</th>
                            <th>Suprafa&#539;&#259;</th>
                            <th>Chirie</th>
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
                                    <a class="button button-danger" href="sterge_apartament.php?id=<?php echo e($row['id']); ?>" onclick="return confirm('Sigur stergi acest apartament?');">&#536;terge</a>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="7">Nu exist&#259; apartamente de afi&#537;at.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
