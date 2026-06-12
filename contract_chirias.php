<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$chirias = null;
$plati = null;

if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT c.*, a.adresa AS adresa_apartament, a.numar_apartament, a.etaj,
                                          CASE WHEN c.data_inceput <= CURDATE() AND c.data_sfarsit >= CURDATE() THEN 'activ' ELSE 'expirat' END AS status_contract
                                   FROM chiriasi c
                                   LEFT JOIN apartamente a ON c.apartament_id = a.id
                                   WHERE c.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $chirias = mysqli_fetch_assoc($result);

    if ($chirias) {
        $stmtPlati = mysqli_prepare($conn, "SELECT DATE_FORMAT(scadenta, '%Y-%m') AS luna_aferenta, suma, data_platii, status
                                           FROM facturi
                                           WHERE apartament_id = ? AND tip_factura = 'chirie'
                                           ORDER BY scadenta DESC, id DESC");
        mysqli_stmt_bind_param($stmtPlati, "i", $chirias['apartament_id']);
        mysqli_stmt_execute($stmtPlati);
        $plati = mysqli_stmt_get_result($stmtPlati);
    }
}

function document_label($value) {
    return (int)$value === 1 ? 'Primit' : 'Lipse&#537;te';
}

function contract_status_class($status) {
    return $status === 'activ' ? 'status-free' : 'status-occupied';
}

function payment_status_class($status) {
    return $status === 'platita' ? 'status-free' : 'status-occupied';
}

function apartament_contract_label($chirias) {
    if (empty($chirias['adresa_apartament'])) {
        return 'Nesetat';
    }

    $prefix = !empty($chirias['numar_apartament']) ? 'Ap. ' . $chirias['numar_apartament'] . ' - ' : '';
    $suffix = $chirias['etaj'] !== null ? ' (etaj ' . $chirias['etaj'] . ')' : '';

    return $prefix . $chirias['adresa_apartament'] . $suffix;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vizualizare contract</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Contract</p>
                <h1>Detalii contract</h1>
            </div>

            <a class="button button-secondary" href="chiriasi.php">&#206;napoi la chiria&#537;i</a>
        </section>

        <?php if ($chirias) { ?>
            <section class="form-card form-card-wide">
                <div class="detail-header">
                    <div>
                        <p class="eyebrow">Chiria&#537;</p>
                        <h2><?php echo e($chirias['nume'] . ' ' . $chirias['prenume']); ?></h2>
                    </div>
                    <span class="status-pill <?php echo e(contract_status_class($chirias['status_contract'])); ?>">
                        <?php echo e(ucfirst($chirias['status_contract'])); ?>
                    </span>
                </div>

                <div class="detail-grid">
                    <div>
                        <span>Num&#259;r contract</span>
                        <strong><?php echo e($chirias['numar_contract']); ?></strong>
                    </div>
                    <div>
                        <span>Data &#238;nceput</span>
                        <strong><?php echo e($chirias['data_inceput']); ?></strong>
                    </div>
                    <div>
                        <span>Data sf&#226;r&#537;it</span>
                        <strong><?php echo e($chirias['data_sfarsit']); ?></strong>
                    </div>
                    <div>
                        <span>Chirie lunar&#259;</span>
                        <strong><?php echo e($chirias['chirie_lunara']); ?> lei</strong>
                    </div>
                    <div>
                        <span>Garan&#539;ie</span>
                        <strong><?php echo e($chirias['garantie']); ?> lei</strong>
                    </div>
                    <div>
                        <span>Apartament</span>
                        <strong><?php echo e(apartament_contract_label($chirias)); ?></strong>
                    </div>
                    <div>
                        <span>Status contract</span>
                        <strong><?php echo e(ucfirst($chirias['status_contract'])); ?></strong>
                    </div>
                </div>

                <h2 class="form-section-title">Date chiria&#537;</h2>
                <div class="detail-grid">
                    <div>
                        <span>Telefon</span>
                        <strong><?php echo e($chirias['telefon']); ?></strong>
                    </div>
                    <div>
                        <span>Email</span>
                        <strong><?php echo e($chirias['email']); ?></strong>
                    </div>
                    <div>
                        <span>CNP</span>
                        <strong><?php echo e($chirias['cnp']); ?></strong>
                    </div>
                    <div>
                        <span>Serie CI</span>
                        <strong><?php echo e($chirias['serie_ci']); ?></strong>
                    </div>
                    <div>
                        <span>Adres&#259;</span>
                        <strong><?php echo e($chirias['adresa']); ?></strong>
                    </div>
                    <div>
                        <span>Data mut&#259;rii</span>
                        <strong><?php echo e($chirias['data_mutarii']); ?></strong>
                    </div>
                </div>

                <h2 class="form-section-title">Documente</h2>
                <div class="document-list">
                    <div>
                        <span>Contract de &#238;nchiriere</span>
                        <strong><?php echo document_label($chirias['document_contract']); ?></strong>
                    </div>
                    <div>
                        <span>Copie CI</span>
                        <strong><?php echo document_label($chirias['document_copie_ci']); ?></strong>
                    </div>
                    <div>
                        <span>Proces verbal predare-primire</span>
                        <strong><?php echo document_label($chirias['document_proces_verbal']); ?></strong>
                    </div>
                    <div>
                        <span>Dovada pl&#259;&#539;ii garan&#539;iei</span>
                        <strong><?php echo document_label($chirias['document_garantie']); ?></strong>
                    </div>
                </div>

                <h2 class="form-section-title">Istoric pl&#259;&#539;i chirie</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Luna aferent&#259;</th>
                                <th>Sum&#259; pl&#259;tit&#259;</th>
                                <th>Data pl&#259;&#539;ii</th>
                                <th>Status plat&#259;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($plati && mysqli_num_rows($plati) > 0) { ?>
                                <?php while($plata = mysqli_fetch_assoc($plati)) { ?>
                                    <tr>
                                        <td><?php echo e($plata['luna_aferenta']); ?></td>
                                        <td><?php echo e($plata['suma']); ?> lei</td>
                                        <td><?php echo e($plata['data_platii'] ?: '-'); ?></td>
                                        <td>
                                            <span class="status-pill <?php echo e(payment_status_class($plata['status'])); ?>">
                                                <?php echo e($plata['status'] === 'platita' ? 'Achitat' : 'Neachitat'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td class="empty-state" colspan="4">Nu exist&#259; pl&#259;&#539;i de chirie pentru acest contract.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php } else { ?>
            <section class="form-card">
                <p class="empty-state">Contractul nu a fost g&#259;sit.</p>
            </section>
        <?php } ?>
    </main>

</body>
</html>
