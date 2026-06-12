<?php
include "config/db.php";
include "auth.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_bani($value) {
    return number_format((float)$value, 2, '.', '') . ' lei';
}

function tip_factura_label($tip) {
    return ucfirst(str_replace('_', ' ', (string)$tip));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$factura = null;

if ($id) {
    $stmt = mysqli_prepare($conn, "SELECT f.*, a.adresa AS adresa_apartament, a.numar_apartament, c.nume, c.prenume, c.email, c.telefon
                                   FROM facturi f
                                   LEFT JOIN apartamente a ON f.apartament_id = a.id
                                   LEFT JOIN chiriasi c ON f.chirias_id = c.id
                                   WHERE f.id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $factura = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function status_class_factura($status) {
    return $status === 'platita' ? 'status-free' : 'status-occupied';
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalii factura</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Facturi</p>
                <h1>Detalii factura</h1>
            </div>
            <a class="button button-secondary" href="facturi.php">Inapoi la facturi</a>
        </section>

        <?php if ($factura) { ?>
            <section class="form-card form-card-wide">
                <div class="detail-header">
                    <div>
                        <p class="eyebrow">Factura</p>
                        <h2><?php echo e(tip_factura_label($factura['tip_factura'] ?? '-')); ?></h2>
                    </div>
                    <span class="status-pill <?php echo e(status_class_factura($factura['status'])); ?>">
                        <?php echo e($factura['status'] === 'platita' ? 'Platita' : 'Neplatita'); ?>
                    </span>
                </div>

                <div class="detail-grid">
                    <div>
                        <span>Tip factura</span>
                        <strong><?php echo e(tip_factura_label($factura['tip_factura'] ?? '-')); ?></strong>
                    </div>
                    <div>
                        <span>Chirias asociat</span>
                        <strong><?php echo e(trim(($factura['nume'] ?? '') . ' ' . ($factura['prenume'] ?? '')) ?: 'Nesetat'); ?></strong>
                    </div>
                    <div>
                        <span>Apartament asociat</span>
                        <strong><?php echo e(($factura['numar_apartament'] ? 'Ap. ' . $factura['numar_apartament'] . ' - ' : '') . ($factura['adresa_apartament'] ?? 'Nesetat')); ?></strong>
                    </div>
                    <div>
                        <span>Email chirias</span>
                        <strong><?php echo e($factura['email'] ?? '-'); ?></strong>
                    </div>
                    <div>
                        <span>Telefon chirias</span>
                        <strong><?php echo e($factura['telefon'] ?? '-'); ?></strong>
                    </div>
                    <div>
                        <span>Data emiterii</span>
                        <strong><?php echo e($factura['data_emitere'] ?: '-'); ?></strong>
                    </div>
                    <div>
                        <span>Data scadentei</span>
                        <strong><?php echo e($factura['scadenta']); ?></strong>
                    </div>
                    <div>
                        <span>Valoare chirie</span>
                        <strong><?php echo e(format_bani($factura['valoare_chirie'])); ?></strong>
                    </div>
                    <div>
                        <span>Cost utilitati</span>
                        <strong><?php echo e(format_bani($factura['cost_utilitati'])); ?></strong>
                    </div>
                    <div>
                        <span>Cost mentenanta</span>
                        <strong><?php echo e(format_bani($factura['cost_mentenanta'])); ?></strong>
                    </div>
                    <div>
                        <span>Valoare totala</span>
                        <strong><?php echo e(format_bani($factura['valoare_totala'])); ?></strong>
                    </div>
                    <div>
                        <span>Data platii</span>
                        <strong><?php echo e($factura['data_platii'] ?: '-'); ?></strong>
                    </div>
                </div>
            </section>
        <?php } else { ?>
            <section class="form-card">
                <p class="empty-state">Factura nu a fost gasita.</p>
            </section>
        <?php } ?>
    </main>
</body>
</html>
