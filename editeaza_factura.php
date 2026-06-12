<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$tipuriFacturi = ['chirie', 'apa', 'gaz', 'curent', 'internet', 'intretinere', 'mentenanta'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('error', 'Eroare: factura nu a fost gasita.');
    header("Location: facturi.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM facturi WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$factura = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$factura) {
    set_flash('error', 'Eroare: factura nu a fost gasita.');
    header("Location: facturi.php");
    exit;
}

$errors = [];
$numar_factura = $factura['numar_factura'] ?? ('F-' . $id);
$tip_factura = $factura['tip_factura'] ?? 'chirie';
$chirias_id = (int)($factura['chirias_id'] ?? 0);
$apartament_id = (int)($factura['apartament_id'] ?? 0);
$data_emitere = $factura['data_emitere'] ?? '';
$scadenta = $factura['scadenta'] ?? '';
$valoare_chirie = $factura['valoare_chirie'] ?? '0.00';
$cost_utilitati = $factura['cost_utilitati'] ?? '0.00';
$cost_mentenanta = $factura['cost_mentenanta'] ?? '0.00';
$status = $factura['status'] ?? 'neplatita';
$data_platii = $factura['data_platii'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['salveaza'])) {
    $tip_factura = trim($_POST['tip_factura'] ?? '');
    $chirias_id = (int)($_POST['chirias_id'] ?? 0);
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $data_emitere = trim($_POST['data_emitere'] ?? '');
    $scadenta = trim($_POST['scadenta'] ?? '');
    $valoareChirieInput = trim($_POST['valoare_chirie'] ?? '');
    $costUtilitatiInput = trim($_POST['cost_utilitati'] ?? '');
    $costMentenantaInput = trim($_POST['cost_mentenanta'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $data_platii = trim($_POST['data_platii'] ?? '');

    if (!in_array($tip_factura, $tipuriFacturi, true)) {
        $errors[] = 'Tipul facturii nu este valid.';
    }

    if ($chirias_id < 1) {
        $errors[] = 'Chiriasul trebuie sa existe.';
    } else {
        $stmtChirias = mysqli_prepare($conn, "SELECT id, apartament_id FROM chiriasi WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtChirias, "i", $chirias_id);
        mysqli_stmt_execute($stmtChirias);
        $chirias = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtChirias));

        if (!$chirias) {
            $errors[] = 'Chiriasul trebuie sa existe.';
        } elseif ($apartament_id > 0 && (int)$chirias['apartament_id'] !== $apartament_id) {
            $errors[] = 'Chiriasul trebuie sa fie asociat apartamentului selectat.';
        }
    }

    if ($apartament_id < 1) {
        $errors[] = 'Apartamentul trebuie sa existe.';
    } else {
        $stmtApartament = mysqli_prepare($conn, "SELECT id FROM apartamente WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtApartament, "i", $apartament_id);
        mysqli_stmt_execute($stmtApartament);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtApartament))) {
            $errors[] = 'Apartamentul trebuie sa existe.';
        }
    }

    if ($data_emitere === '') {
        $errors[] = 'Data emiterii este obligatorie.';
    }

    if ($scadenta === '') {
        $errors[] = 'Data scadentei este obligatorie.';
    } elseif ($data_emitere !== '' && $scadenta <= $data_emitere) {
        $errors[] = 'Data scadentei trebuie sa fie mai mare decat data emiterii.';
    }

    $valoareChirie = $valoareChirieInput === '' ? 0 : (float)str_replace(',', '.', $valoareChirieInput);
    $costUtilitati = $costUtilitatiInput === '' ? 0 : (float)str_replace(',', '.', $costUtilitatiInput);
    $costMentenanta = $costMentenantaInput === '' ? 0 : (float)str_replace(',', '.', $costMentenantaInput);

    if ($valoareChirieInput === '' || !is_numeric(str_replace(',', '.', $valoareChirieInput)) || $valoareChirie < 0) {
        $errors[] = 'Valoarea chiriei nu poate fi negativa.';
    }

    if ($costUtilitatiInput === '' || !is_numeric(str_replace(',', '.', $costUtilitatiInput)) || $costUtilitati < 0) {
        $errors[] = 'Costul utilitatilor nu poate fi negativ.';
    }

    if ($costMentenantaInput === '' || !is_numeric(str_replace(',', '.', $costMentenantaInput)) || $costMentenanta < 0) {
        $errors[] = 'Costul de mentenanta nu poate fi negativ.';
    }

    $valoareTotala = $valoareChirie + $costUtilitati + $costMentenanta;

    if ($valoareTotala <= 0) {
        $errors[] = 'Valoarea totala a facturii trebuie sa fie mai mare decat 0.';
    }

    if (!in_array($status, ['platita', 'neplatita'], true)) {
        $errors[] = 'Statusul selectat nu este valid.';
    }

    if ($status === 'platita' && $data_platii === '') {
        $data_platii = date('Y-m-d');
    }

    if ($status === 'neplatita') {
        $data_platii = null;
    }

    if (empty($errors)) {
        $stmtUpdate = mysqli_prepare($conn, "UPDATE facturi
                                            SET chirias_id = ?, apartament_id = ?, tip_factura = ?, suma = ?,
                                                data_emitere = ?, scadenta = ?, valoare_chirie = ?, cost_utilitati = ?, cost_mentenanta = ?,
                                                valoare_totala = ?, status = ?, data_platii = ?
                                            WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, "iisdssddddssi", $chirias_id, $apartament_id, $tip_factura, $valoareTotala, $data_emitere, $scadenta, $valoareChirie, $costUtilitati, $costMentenanta, $valoareTotala, $status, $data_platii, $id);

        if (mysqli_stmt_execute($stmtUpdate)) {
            set_flash('success', 'Factura a fost actualizata cu succes.');
            header("Location: facturi.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la actualizarea facturii.';
    }

    $valoare_chirie = $valoareChirieInput;
    $cost_utilitati = $costUtilitatiInput;
    $cost_mentenanta = $costMentenantaInput;
}

$chiriasi = mysqli_query($conn, "SELECT c.id, c.nume, c.prenume, c.email, c.apartament_id FROM chiriasi c ORDER BY c.nume ASC, c.prenume ASC");
$apartamente = mysqli_query($conn, "SELECT a.id, a.adresa, a.numar_apartament,
                                           c.id AS chirias_id,
                                           c.nume AS chirias_nume,
                                           c.prenume AS chirias_prenume
                                    FROM apartamente a
                                    LEFT JOIN chiriasi c ON c.id = (
                                        SELECT c2.id
                                        FROM chiriasi c2
                                        WHERE c2.apartament_id = a.id
                                          AND c2.data_inceput <= CURDATE()
                                          AND c2.data_sfarsit >= CURDATE()
                                        ORDER BY c2.id DESC
                                        LIMIT 1
                                    )
                                    ORDER BY a.adresa ASC, a.numar_apartament ASC");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editeaza factura</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Facturi</p>
                <h1>Editeaza factura</h1>
            </div>
            <a class="button button-secondary" href="facturi.php">Inapoi la facturi</a>
        </section>

        <section class="form-card form-card-wide">
            <?php if (!empty($errors)) { ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error) { ?>
                        <p><?php echo e($error); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <form method="POST">
                <div class="form-grid">
                    <label>
                        <span>Tip factura</span>
                        <select name="tip_factura" required>
                            <?php foreach ($tipuriFacturi as $tip) { ?>
                                <option value="<?php echo e($tip); ?>" <?php echo $tip_factura === $tip ? 'selected' : ''; ?>>
                                    <?php echo e(ucfirst($tip)); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Chirias asociat</span>
                        <select name="chirias_id" required>
                            <option value="">Alege chiriasul</option>
                            <?php while($chirias = mysqli_fetch_assoc($chiriasi)) { ?>
                                <option value="<?php echo e($chirias['id']); ?>"
                                        data-apartament-id="<?php echo e($chirias['apartament_id'] ?? ''); ?>"
                                        <?php echo (int)$chirias_id === (int)$chirias['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($chirias['nume'] . ' ' . $chirias['prenume'] . ' - ' . $chirias['email']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Apartament asociat</span>
                        <select name="apartament_id" required>
                            <?php while($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                                <?php $optionChirias = trim(($apartament['chirias_nume'] ?? '') . ' ' . ($apartament['chirias_prenume'] ?? '')); ?>
                                <option value="<?php echo e($apartament['id']); ?>"
                                        data-chirias-id="<?php echo e($apartament['chirias_id'] ?? ''); ?>"
                                        data-chirias-name="<?php echo e($optionChirias); ?>"
                                        <?php echo (int)$apartament_id === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                    <?php echo e(($apartament['numar_apartament'] ? 'Ap. ' . $apartament['numar_apartament'] . ' - ' : '') . $apartament['adresa']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Data emiterii</span>
                        <input type="date" name="data_emitere" value="<?php echo e($data_emitere); ?>" required>
                    </label>

                    <label>
                        <span>Data scadentei</span>
                        <input type="date" name="scadenta" value="<?php echo e($scadenta); ?>" required>
                    </label>

                    <label>
                        <span>Valoare chirie</span>
                        <input type="number" step="0.01" min="0" name="valoare_chirie" value="<?php echo e($valoare_chirie); ?>" required>
                    </label>

                    <label>
                        <span>Cost utilitati</span>
                        <input type="number" step="0.01" min="0" name="cost_utilitati" value="<?php echo e($cost_utilitati); ?>" required>
                    </label>

                    <label>
                        <span>Cost mentenanta</span>
                        <input type="number" step="0.01" min="0" name="cost_mentenanta" value="<?php echo e($cost_mentenanta); ?>" required>
                    </label>

                    <label>
                        <span>Status factura</span>
                        <select name="status" required>
                            <option value="neplatita" <?php echo $status === 'neplatita' ? 'selected' : ''; ?>>Neplatita</option>
                            <option value="platita" <?php echo $status === 'platita' ? 'selected' : ''; ?>>Platita</option>
                        </select>
                    </label>

                    <label>
                        <span>Data platii</span>
                        <input type="date" name="data_platii" value="<?php echo e($data_platii); ?>">
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" name="salveaza">Salveaza modificarile</button>
                </div>
            </form>
        </section>
    </main>
    <script src="js/linked-selects.js?v=<?php echo filemtime(__DIR__ . '/js/linked-selects.js'); ?>"></script>
</body>
</html>
