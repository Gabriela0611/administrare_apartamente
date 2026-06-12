<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$result = mysqli_query($conn, "SELECT c.*, a.adresa AS adresa_apartament
                               FROM chiriasi c
                               LEFT JOIN apartamente a ON c.apartament_id = a.id
                               ORDER BY c.id DESC");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documente</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Documente</p>
                <h1>Documentele chiriașilor</h1>
            </div>
        </section>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Chiriaș</th>
                            <th>Apartament</th>
                            <th>Contract</th>
                            <th>Copie CI</th>
                            <th>Proces verbal</th>
                            <th>Dovadă garanție</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($row['nume'] . ' ' . $row['prenume']); ?></td>
                                <td><?php echo e($row['adresa_apartament'] ?? 'Nesetat'); ?></td>
                                <td><?php echo (int)$row['document_contract'] === 1 ? 'Primit' : 'Lipsește'; ?></td>
                                <td><?php echo (int)$row['document_copie_ci'] === 1 ? 'Primit' : 'Lipsește'; ?></td>
                                <td><?php echo (int)$row['document_proces_verbal'] === 1 ? 'Primit' : 'Lipsește'; ?></td>
                                <td><?php echo (int)$row['document_garantie'] === 1 ? 'Primit' : 'Lipsește'; ?></td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="6">Nu există documente de afișat.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
