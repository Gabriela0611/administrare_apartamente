<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (is_chirias()) {
    $chiriasId = (int)($_SESSION['user_chirias_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "SELECT f.*, DATE_FORMAT(f.scadenta, '%Y-%m') AS luna_aferenta, a.adresa AS adresa_apartament
                                   FROM facturi f
                                   LEFT JOIN apartamente a ON f.apartament_id = a.id
                                   LEFT JOIN chiriasi c ON c.apartament_id = f.apartament_id
                                   WHERE f.status = 'platita' AND c.id = ?
                                   ORDER BY f.data_platii DESC, f.id DESC");
    mysqli_stmt_bind_param($stmt, "i", $chiriasId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, "SELECT f.*, DATE_FORMAT(f.scadenta, '%Y-%m') AS luna_aferenta, a.adresa AS adresa_apartament
                                   FROM facturi f
                                   LEFT JOIN apartamente a ON f.apartament_id = a.id
                                   WHERE f.status = 'platita'
                                   ORDER BY f.data_platii DESC, f.id DESC");
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plati</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Plati</p>
                <h1>Facturi platite</h1>
            </div>
        </section>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Apartament</th>
                            <th>Luna aferenta</th>
                            <th>Tip factura</th>
                            <th>Suma platita</th>
                            <th>Data platii</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($row['adresa_apartament'] ?? 'Nesetat'); ?></td>
                                <td><?php echo e($row['luna_aferenta']); ?></td>
                                <td><?php echo e(ucfirst($row['tip_factura'])); ?></td>
                                <td><?php echo e($row['suma']); ?> lei</td>
                                <td><?php echo e($row['data_platii']); ?></td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="5">Nu exista plati de afisat.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
