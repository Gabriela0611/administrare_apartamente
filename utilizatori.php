<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$result = mysqli_query($conn, "SELECT u.*, c.nume, c.prenume
                               FROM users u
                               LEFT JOIN chiriasi c ON u.chirias_id = c.id
                               ORDER BY u.id DESC");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizatori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Utilizatori</p>
                <h1>Utilizatori aplicație</h1>
            </div>
        </section>

        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Chiriaș legat</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($row['email']); ?></td>
                                <td><?php echo e(ucfirst($row['role'])); ?></td>
                                <td><?php echo e(trim(($row['nume'] ?? '') . ' ' . ($row['prenume'] ?? '')) ?: '-'); ?></td>
                                <td>
                                    <span class="status-pill status-free">Activ</span>
                                </td>
                            </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td class="empty-state" colspan="4">Nu există utilizatori de afișat.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
