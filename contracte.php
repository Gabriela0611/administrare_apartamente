<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$result = mysqli_query($conn, "SELECT c.*, a.adresa AS adresa_apartament
                               FROM chiriasi c
                               LEFT JOIN apartamente a ON c.apartament_id = a.id
                               ORDER BY c.data_inceput DESC");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contracte</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Contracte</p>
                <h1>Contracte de închiriere</h1>
            </div>
        </section>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Număr contract</th>
                            <th>Chiriaș</th>
                            <th>Apartament</th>
                            <th>Început</th>
                            <th>Sfârșit</th>
                            <th>Chirie</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($row['numar_contract']); ?></td>
                                <td><?php echo e($row['nume'] . ' ' . $row['prenume']); ?></td>
                                <td><?php echo e($row['adresa_apartament'] ?? 'Nesetat'); ?></td>
                                <td><?php echo e($row['data_inceput']); ?></td>
                                <td><?php echo e($row['data_sfarsit']); ?></td>
                                <td><?php echo e($row['chirie_lunara']); ?> lei</td>
                                <td>
                                    <a class="button button-secondary button-small" href="contract_chirias.php?id=<?php echo e($row['id']); ?>">Vezi</a>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="7">Nu există contracte de afișat.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
