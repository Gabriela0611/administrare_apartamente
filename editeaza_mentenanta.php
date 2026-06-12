<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$prioritati = ['scazuta', 'medie', 'ridicata', 'urgenta'];
$statusuri = ['deschisa', 'in_lucru', 'rezolvata'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('error', 'Eroare: sesizarea nu a fost gasita.');
    header("Location: mentenanta.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM cereri_mentenanta WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$cerere = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$cerere) {
    set_flash('error', 'Eroare: sesizarea nu a fost gasita.');
    header("Location: mentenanta.php");
    exit;
}

if (($cerere['status'] ?? '') === 'rezolvata') {
    set_flash('error', 'Mentenanta finalizata nu mai poate fi modificata.');
    header("Location: mentenanta.php");
    exit;
}

$errors = [];
$apartament_id = (int)($cerere['apartament_id'] ?? 0);
$chirias_id = (int)($cerere['chirias_id'] ?? 0);
$problema = $cerere['problema'] ?? '';
$descriere = $cerere['descriere'] ?? '';
$prioritate = $cerere['prioritate'] ?? 'medie';
$status = $cerere['status'] ?? 'deschisa';
$data_raportare = $cerere['data_raportare'] ?? date('Y-m-d');
$data_rezolvare = $cerere['data_rezolvare'] ?? '';

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
$chiriasi = mysqli_query($conn, "SELECT c.id, c.nume, c.prenume, c.apartament_id, a.adresa AS adresa_apartament
                                 FROM chiriasi c
                                 LEFT JOIN apartamente a ON c.apartament_id = a.id
                                 ORDER BY c.nume ASC, c.prenume ASC");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['salveaza'])) {
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $chirias_id = (int)($_POST['chirias_id'] ?? 0);
    $problema = trim($_POST['problema'] ?? '');
    $descriere = trim($_POST['descriere'] ?? '');
    $prioritate = trim($_POST['prioritate'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $data_raportare = trim($_POST['data_raportare'] ?? '');
    $data_rezolvare = trim($_POST['data_rezolvare'] ?? '');

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

    if ($chirias_id > 0) {
        $stmtChirias = mysqli_prepare($conn, "SELECT id, apartament_id FROM chiriasi WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtChirias, "i", $chirias_id);
        mysqli_stmt_execute($stmtChirias);
        $chirias = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtChirias));

        if (!$chirias) {
            $errors[] = 'Chiriasul trebuie sa existe.';
        } elseif ($apartament_id > 0 && (int)$chirias['apartament_id'] !== $apartament_id) {
            $errors[] = 'Chiriasul trebuie sa corespunda apartamentului selectat.';
        }
    }

    if ($problema === '') {
        $errors[] = 'Tipul problemei este obligatoriu.';
    }

    if ($descriere === '') {
        $errors[] = 'Descrierea problemei nu poate fi goala.';
    }

    if (!in_array($prioritate, $prioritati, true)) {
        $errors[] = 'Prioritatea selectata nu este valida.';
    }

    if (!in_array($status, $statusuri, true)) {
        $errors[] = 'Statusul selectat nu este valid.';
    }

    if ($data_raportare === '') {
        $errors[] = 'Data raportarii este obligatorie.';
    }

    if ($status === 'rezolvata') {
        if ($data_rezolvare === '') {
            $data_rezolvare = date('Y-m-d');
        }

        if ($data_raportare !== '' && $data_rezolvare < $data_raportare) {
            $errors[] = 'Data rezolvarii nu poate fi mai mica decat data raportarii.';
        }
    } else {
        $data_rezolvare = null;
    }

    if (empty($errors)) {
        $chiriasParam = $chirias_id > 0 ? $chirias_id : null;
        $stmtUpdate = mysqli_prepare($conn, "UPDATE cereri_mentenanta
                                            SET apartament_id = ?, chirias_id = ?, problema = ?, descriere = ?, prioritate = ?, status = ?,
                                                data_raportare = ?, data_rezolvare = ?
                                            WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, "iissssssi", $apartament_id, $chiriasParam, $problema, $descriere, $prioritate, $status, $data_raportare, $data_rezolvare, $id);

        if (mysqli_stmt_execute($stmtUpdate)) {
            set_flash('success', 'Sesizarea de mentenanta a fost actualizata.');
            header("Location: mentenanta.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la actualizarea sesizarii.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editeaza mentenanta</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Mentenanta</p>
                <h1>Editeaza sesizare</h1>
            </div>
            <a class="button button-secondary" href="mentenanta.php">Inapoi la mentenanta</a>
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
                        <span>Apartament</span>
                        <select name="apartament_id" required>
                            <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                                <?php while($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                                    <?php $optionChirias = trim(($apartament['chirias_nume'] ?? '') . ' ' . ($apartament['chirias_prenume'] ?? '')); ?>
                                    <option value="<?php echo e($apartament['id']); ?>"
                                            data-chirias-id="<?php echo e($apartament['chirias_id'] ?? ''); ?>"
                                            data-chirias-name="<?php echo e($optionChirias); ?>"
                                            <?php echo (int)$apartament_id === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                        <?php echo e(($apartament['numar_apartament'] ? 'Ap. ' . $apartament['numar_apartament'] . ' - ' : '') . $apartament['adresa']); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Chirias</span>
                        <select name="chirias_id">
                            <option value="0">Fara chirias selectat</option>
                            <?php if ($chiriasi && mysqli_num_rows($chiriasi) > 0) { ?>
                                <?php while($chirias = mysqli_fetch_assoc($chiriasi)) { ?>
                                    <option value="<?php echo e($chirias['id']); ?>"
                                            data-apartament-id="<?php echo e($chirias['apartament_id'] ?? ''); ?>"
                                            <?php echo (int)$chirias_id === (int)$chirias['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($chirias['nume'] . ' ' . $chirias['prenume'] . ' - ' . ($chirias['adresa_apartament'] ?? '')); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Tip problem&#259;</span>
                        <input type="text" name="problema" value="<?php echo e($problema); ?>" required>
                    </label>

                    <label>
                        <span>Prioritate</span>
                        <select name="prioritate" required>
                            <option value="scazuta" <?php echo $prioritate === 'scazuta' ? 'selected' : ''; ?>>Scazuta</option>
                            <option value="medie" <?php echo $prioritate === 'medie' ? 'selected' : ''; ?>>Medie</option>
                            <option value="ridicata" <?php echo $prioritate === 'ridicata' ? 'selected' : ''; ?>>Ridicata</option>
                            <option value="urgenta" <?php echo $prioritate === 'urgenta' ? 'selected' : ''; ?>>Urgenta</option>
                        </select>
                    </label>

                    <label>
                        <span>Status mentenanta</span>
                        <select name="status" required>
                            <option value="deschisa" <?php echo $status === 'deschisa' ? 'selected' : ''; ?>>Noua</option>
                            <option value="in_lucru" <?php echo $status === 'in_lucru' ? 'selected' : ''; ?>>In lucru</option>
                            <option value="rezolvata" <?php echo $status === 'rezolvata' ? 'selected' : ''; ?>>Finalizata</option>
                        </select>
                    </label>

                    <label>
                        <span>Data raportarii</span>
                        <input type="date" name="data_raportare" value="<?php echo e($data_raportare); ?>" required>
                    </label>

                    <label>
                        <span>Data rezolvarii</span>
                        <input type="date" name="data_rezolvare" value="<?php echo e($data_rezolvare); ?>">
                    </label>

                    <label class="form-full">
                        <span>Descriere problema</span>
                        <textarea name="descriere" rows="5" required><?php echo e($descriere); ?></textarea>
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
