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

$errors = [];
$chirias_id = '';
$apartament_id = '';
$numar_contract = '';
$data_inceput = '';
$data_sfarsit = '';
$chirie_lunara = '';
$garantie = '';

$chiriasi = mysqli_query($conn, "SELECT id, nume, prenume, email, apartament_id FROM chiriasi ORDER BY nume ASC, prenume ASC");
$apartamente = mysqli_query($conn, "SELECT a.id, a.adresa, a.numar_apartament, a.etaj,
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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['adauga'])) {
    $chirias_id = (int)($_POST['chirias_id'] ?? 0);
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $numar_contract = trim($_POST['numar_contract'] ?? '');
    $data_inceput = trim($_POST['data_inceput'] ?? '');
    $data_sfarsit = trim($_POST['data_sfarsit'] ?? '');
    $chirie_lunara = trim($_POST['chirie_lunara'] ?? '');
    $garantie = trim($_POST['garantie'] ?? '');

    if ($chirias_id < 1) {
        $errors[] = 'Chiriasul trebuie sa existe.';
    } else {
        $stmtChirias = mysqli_prepare($conn, "SELECT id, apartament_id FROM chiriasi WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtChirias, "i", $chirias_id);
        mysqli_stmt_execute($stmtChirias);
        $chirias = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtChirias));

        if (!$chirias) {
            $errors[] = 'Chiriasul trebuie sa existe.';
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

    if ($apartament_id > 0 && $data_inceput !== '' && $data_sfarsit !== '') {
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
        $oldApartamentId = (int)($chirias['apartament_id'] ?? 0);
        $stmtUpdate = mysqli_prepare($conn, "UPDATE chiriasi
                                            SET apartament_id = ?, numar_contract = ?, data_inceput = ?, data_sfarsit = ?, chirie_lunara = ?, garantie = ?
                                            WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, "isssddi", $apartament_id, $numar_contract, $data_inceput, $data_sfarsit, $chirieValue, $garantieValue, $chirias_id);

        if (mysqli_stmt_execute($stmtUpdate)) {
            mysqli_query($conn, "UPDATE apartamente SET status = 'ocupat' WHERE id = " . (int)$apartament_id);

            if ($oldApartamentId && $oldApartamentId !== $apartament_id) {
                mysqli_query($conn, "UPDATE apartamente a
                                     SET status = 'liber'
                                     WHERE id = " . (int)$oldApartamentId . "
                                       AND NOT EXISTS (
                                           SELECT 1 FROM chiriasi c
                                           WHERE c.apartament_id = a.id
                                             AND c.data_inceput <= CURDATE()
                                             AND c.data_sfarsit >= CURDATE()
                                       )");
            }

            set_flash('success', 'Contractul a fost adaugat cu succes.');
            header("Location: contracte.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la salvarea contractului.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adauga contract</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Contracte</p>
                <h1>Adauga contract</h1>
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
                                <?php while($chiriasRow = mysqli_fetch_assoc($chiriasi)) { ?>
                                    <option value="<?php echo e($chiriasRow['id']); ?>"
                                            data-apartament-id="<?php echo e($chiriasRow['apartament_id'] ?? ''); ?>"
                                            <?php echo (int)$chirias_id === (int)$chiriasRow['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($chiriasRow['nume'] . ' ' . $chiriasRow['prenume'] . ' - ' . $chiriasRow['email']); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Apartament asociat</span>
                        <select name="apartament_id" required>
                            <option value="">Alege apartamentul</option>
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
                    <button class="button button-primary" type="submit" name="adauga">Salveaza contractul</button>
                </div>
            </form>
        </section>
    </main>
    <script src="js/linked-selects.js?v=<?php echo filemtime(__DIR__ . '/js/linked-selects.js'); ?>"></script>
</body>
</html>
