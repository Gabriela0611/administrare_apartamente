<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$cautare = trim($_GET['cautare'] ?? '');

$selectSql = "SELECT c.*, a.adresa AS adresa_apartament
              FROM chiriasi c
              LEFT JOIN apartamente a ON c.apartament_id = a.id";

if ($cautare !== '') {
    $termen = '%' . $cautare . '%';
    $stmt = mysqli_prepare($conn, $selectSql . " WHERE c.nume LIKE ? OR c.prenume LIKE ? OR c.telefon LIKE ? OR c.email LIKE ? OR c.numar_contract LIKE ? ORDER BY c.id DESC");
    mysqli_stmt_bind_param($stmt, "sssss", $termen, $termen, $termen, $termen, $termen);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $selectSql . " ORDER BY c.id DESC");
}

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
    <link rel="stylesheet" href="style.css">
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
                <?php if (is_admin()) { ?>
                    <a class="button button-primary" href="adauga_chirias.php">Adaug&#259; chiria&#537;</a>
                <?php } ?>
            </div>
        </section>

        <?php include "flash_messages.php"; ?>

        <form class="search-form" method="GET">
            <label>
                <span>Caut&#259; dup&#259; nume, telefon, email sau contract</span>
                <input type="search" name="cautare" value="<?php echo e($cautare); ?>" placeholder="Ex: Popescu, 07..., CTR-01">
            </label>
            <button class="button button-secondary" type="submit">Caut&#259;</button>
            <?php if ($cautare !== '') { ?>
                <a class="button button-secondary" href="chiriasi.php">Reseteaz&#259;</a>
            <?php } ?>
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
                                <td><?php echo e($row['adresa_apartament'] ?? 'Nesetat'); ?></td>
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
                                        <?php if (is_admin()) { ?>
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
