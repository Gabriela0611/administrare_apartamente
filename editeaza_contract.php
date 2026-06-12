<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function option_apartament_label($apartament) {
    $prefix = !empty($apartament['numar_apartament']) ? 'Ap. ' . $apartament['numar_apartament'] . ' - ' : '';
    $suffix = $apartament['etaj'] !== null ? ' (etaj ' . $apartament['etaj'] . ')' : '';

    return $prefix . $apartament['adresa'] . $suffix;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('error', 'Eroare: contractul nu a fost gasit.');
    header("Location: contracte.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM chiriasi WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$contract = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$contract) {
    set_flash('error', 'Eroare: contractul nu a fost gasit.');
    header("Location: contracte.php");
    exit;
}

$errors = [];
$chirias_id = $id;
$apartament_id = (int)($contract['apartament_id'] ?? 0);
$old_apartament_id = $apartament_id;
$numar_contract = $contract['numar_contract'] ?? '';
$data_inceput = $contract['data_inceput'] ?? '';
$data_sfarsit = $contract['data_sfarsit'] ?? '';
$chirie_lunara = $contract['chirie_lunara'] ?? '';
$garantie = $contract['garantie'] ?? '';
$chirias_afisat = trim(($contract['nume'] ?? '') . ' ' . ($contract['prenume'] ?? ''));
$contractScopeChanged = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['salveaza'])) {
    $chirias_id = (int)($_POST['chirias_id'] ?? 0);
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $numar_contract = trim($_POST['numar_contract'] ?? '');
    $data_inceput = trim($_POST['data_inceput'] ?? '');
    $data_sfarsit = trim($_POST['data_sfarsit'] ?? '');
    $chirie_lunara = trim($_POST['chirie_lunara'] ?? '');
    $garantie = trim($_POST['garantie'] ?? '');
    $targetChirias = null;

    if ($chirias_id < 1) {
        $errors[] = 'Alege un apartament care are chirias asociat.';
    } else {
        $stmtChirias = mysqli_prepare($conn, "SELECT id, nume, prenume, apartament_id, data_inceput, data_sfarsit FROM chiriasi WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtChirias, "i", $chirias_id);
        mysqli_stmt_execute($stmtChirias);
        $targetChirias = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtChirias));

        if (!$targetChirias) {
            $errors[] = 'Chiriasul asociat apartamentului nu a fost gasit.';
        } else {
            $chirias_afisat = trim($targetChirias['nume'] . ' ' . $targetChirias['prenume']);
            $old_apartament_id = (int)($targetChirias['apartament_id'] ?? 0);
            $contractScopeChanged = $apartament_id !== (int)($targetChirias['apartament_id'] ?? 0)
                || $data_inceput !== ($targetChirias['data_inceput'] ?? '')
                || $data_sfarsit !== ($targetChirias['data_sfarsit'] ?? '');
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

    if ($targetChirias && (int)$targetChirias['apartament_id'] !== $apartament_id) {
        $errors[] = 'Chiriasul selectat nu corespunde apartamentului ales.';
    }

    if ($numar_contract === '') {
        $errors[] = 'Numarul contractului este obligatoriu.';
    }

    if ($data_inceput === '' || $data_sfarsit === '') {
        $errors[] = 'Datele contractului sunt obligatorii.';
    } elseif ($data_inceput > $data_sfarsit) {
        $errors[] = 'Data de inceput nu poate fi mai tarziu decat data de final.';
    }

    $chirieValue = (float)str_replace(',', '.', $chirie_lunara);
    $garantieValue = (float)str_replace(',', '.', $garantie);

    if ($chirie_lunara === '' || !is_numeric(str_replace(',', '.', $chirie_lunara)) || $chirieValue <= 0) {
        $errors[] = 'Valoarea chiriei trebuie sa fie pozitiva.';
    }

    if ($garantie === '' || !is_numeric(str_replace(',', '.', $garantie)) || $garantieValue < 0) {
        $errors[] = 'Garantia nu poate fi negativa.';
    }

    if ($contractScopeChanged && $apartament_id > 0 && $data_inceput !== '' && $data_sfarsit !== '') {
        $stmtOverlap = mysqli_prepare($conn, "SELECT id FROM chiriasi
                                             WHERE apartament_id = ?
                                               AND data_inceput <= ?
                                               AND data_sfarsit >= ?
                                               AND id <> ?
                                             LIMIT 1");
        mysqli_stmt_bind_param($stmtOverlap, "issi", $apartament_id, $data_sfarsit, $data_inceput, $chirias_id);
        mysqli_stmt_execute($stmtOverlap);

        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmtOverlap))) {
            $errors[] = 'Apartamentul nu poate avea doua contracte active simultan.';
        }
    }

    if (empty($errors)) {
        $stmtUpdate = mysqli_prepare($conn, "UPDATE chiriasi
                                            SET apartament_id = ?, numar_contract = ?, data_inceput = ?, data_sfarsit = ?, chirie_lunara = ?, garantie = ?
                                            WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, "isssddi", $apartament_id, $numar_contract, $data_inceput, $data_sfarsit, $chirieValue, $garantieValue, $chirias_id);

        if (mysqli_stmt_execute($stmtUpdate)) {
            mysqli_query($conn, "UPDATE apartamente SET status = 'ocupat' WHERE id = " . (int)$apartament_id . " AND '" . mysqli_real_escape_string($conn, $data_sfarsit) . "' >= CURDATE()");

            if ($old_apartament_id && $old_apartament_id !== $apartament_id) {
                mysqli_query($conn, "UPDATE apartamente a
                                     SET status = 'liber'
                                     WHERE id = " . (int)$old_apartament_id . "
                                       AND NOT EXISTS (
                                           SELECT 1 FROM chiriasi c
                                           WHERE c.apartament_id = a.id
                                             AND c.data_inceput <= CURDATE()
                                             AND c.data_sfarsit >= CURDATE()
                                       )");
            }

            set_flash('success', 'Contractul a fost actualizat cu succes.');
            header("Location: contracte.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la actualizarea contractului.';
    }
}

$apartamente = mysqli_query($conn, "SELECT a.id, a.adresa, a.numar_apartament, a.etaj,
                                           c.id AS chirias_id,
                                           c.nume AS chirias_nume,
                                           c.prenume AS chirias_prenume
                                    FROM apartamente a
                                    LEFT JOIN chiriasi c ON c.id = (
                                        SELECT c2.id
                                        FROM chiriasi c2
                                        WHERE c2.apartament_id = a.id
                                        ORDER BY (c2.data_inceput <= CURDATE() AND c2.data_sfarsit >= CURDATE()) DESC, c2.id DESC
                                        LIMIT 1
                                    )
                                    ORDER BY a.adresa ASC, a.numar_apartament ASC");
$chiriasi = mysqli_query($conn, "SELECT id, nume, prenume, email, apartament_id FROM chiriasi ORDER BY nume ASC, prenume ASC");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editeaza contract</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Contracte</p>
                <h1>Editeaza contract</h1>
            </div>
            <a class="button button-secondary" href="contracte.php">Inapoi la contracte</a>
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
                        <span>Chirias asociat</span>
                        <select name="chirias_id" required>
                            <option value="">Alege chiriasul</option>
                            <?php if ($chiriasi && mysqli_num_rows($chiriasi) > 0) { ?>
                                <?php while($chirias = mysqli_fetch_assoc($chiriasi)) { ?>
                                    <option value="<?php echo e($chirias['id']); ?>"
                                            data-apartament-id="<?php echo e($chirias['apartament_id'] ?? ''); ?>"
                                            <?php echo (int)$chirias_id === (int)$chirias['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($chirias['nume'] . ' ' . $chirias['prenume'] . ' - ' . $chirias['email']); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Apartament asociat</span>
                        <select id="apartament_id" name="apartament_id" required>
                            <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                                <?php while($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                                    <?php $optionChirias = trim(($apartament['chirias_nume'] ?? '') . ' ' . ($apartament['chirias_prenume'] ?? '')); ?>
                                    <option value="<?php echo e($apartament['id']); ?>"
                                            data-chirias-id="<?php echo e($apartament['chirias_id'] ?? ''); ?>"
                                            data-chirias-name="<?php echo e($optionChirias); ?>"
                                            <?php echo (int)$apartament_id === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                        <?php echo e(option_apartament_label($apartament)); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Numar contract</span>
                        <input type="text" name="numar_contract" value="<?php echo e($numar_contract); ?>" required>
                    </label>

                    <label>
                        <span>Data inceperii contractului</span>
                        <input type="date" name="data_inceput" value="<?php echo e($data_inceput); ?>" required>
                    </label>

                    <label>
                        <span>Data expirarii contractului</span>
                        <input type="date" name="data_sfarsit" value="<?php echo e($data_sfarsit); ?>" required>
                    </label>

                    <label>
                        <span>Valoare chirie lunara</span>
                        <input type="number" step="0.01" min="0.01" name="chirie_lunara" value="<?php echo e($chirie_lunara); ?>" required>
                    </label>

                    <label>
                        <span>Garantie</span>
                        <input type="number" step="0.01" min="0" name="garantie" value="<?php echo e($garantie); ?>" required>
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
