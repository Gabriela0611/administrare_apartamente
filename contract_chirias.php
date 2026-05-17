<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$chirias = null;

if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT c.*, a.adresa AS adresa_apartament
                                   FROM chiriasi c
                                   LEFT JOIN apartamente a ON c.apartament_id = a.id
                                   WHERE c.id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $chirias = mysqli_fetch_assoc($result);
}

function document_label($value) {
    return (int)$value === 1 ? 'Primit' : 'Lipse&#537;te';
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vizualizare contract</title>
    <link rel="stylesheet" href="style.css">
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
                    <span class="status-pill status-free"><?php echo e($chirias['numar_contract']); ?></span>
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
                        <strong><?php echo e($chirias['adresa_apartament'] ?? 'Nesetat'); ?></strong>
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
            </section>
        <?php } else { ?>
            <section class="form-card">
                <p class="empty-state">Contractul nu a fost g&#259;sit.</p>
            </section>
        <?php } ?>
    </main>

</body>
</html>
