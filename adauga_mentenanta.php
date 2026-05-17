<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$prioritati = ['scazuta', 'medie', 'ridicata', 'urgenta'];
$statusuri = ['deschisa', 'in_lucru', 'rezolvata'];
$errors = [];
$apartament_id = '';
$chirias_id = '';
$problema = '';
$descriere = '';
$prioritate = 'medie';
$status = 'deschisa';
$data_raportare = date('Y-m-d');

$apartamente = mysqli_query($conn, "SELECT id, adresa FROM apartamente ORDER BY adresa ASC");
$chiriasi = mysqli_query($conn, "SELECT c.id, c.nume, c.prenume, a.adresa AS adresa_apartament
                                 FROM chiriasi c
                                 LEFT JOIN apartamente a ON c.apartament_id = a.id
                                 ORDER BY c.nume ASC, c.prenume ASC");

if (is_chirias()) {
    $chiriasSessionId = (int)($_SESSION['user_chirias_id'] ?? 0);
    $stmtChirias = mysqli_prepare($conn, "SELECT c.id, c.apartament_id, c.nume, c.prenume, a.adresa AS adresa_apartament
                                          FROM chiriasi c
                                          LEFT JOIN apartamente a ON c.apartament_id = a.id
                                          WHERE c.id = ?");
    mysqli_stmt_bind_param($stmtChirias, "i", $chiriasSessionId);
    mysqli_stmt_execute($stmtChirias);
    $chiriasResult = mysqli_stmt_get_result($stmtChirias);
    $chiriasCurent = mysqli_fetch_assoc($chiriasResult);

    $apartamentCurentId = $chiriasCurent ? (int)$chiriasCurent['apartament_id'] : -1;
    $apartamente = mysqli_query($conn, "SELECT id, adresa FROM apartamente WHERE id = " . (int)$apartamentCurentId);
    $chiriasi = mysqli_query($conn, "SELECT c.id, c.nume, c.prenume, a.adresa AS adresa_apartament
                                     FROM chiriasi c
                                     LEFT JOIN apartamente a ON c.apartament_id = a.id
                                     WHERE c.id = " . (int)$chiriasSessionId);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['adauga'])) {
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $chirias_id = (int)($_POST['chirias_id'] ?? 0);
    $problema = trim($_POST['problema'] ?? '');
    $descriere = trim($_POST['descriere'] ?? '');
    $prioritate = trim($_POST['prioritate'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $data_raportare = trim($_POST['data_raportare'] ?? '');
    $fotografie = null;

    if (is_chirias()) {
        $chirias_id = (int)($_SESSION['user_chirias_id'] ?? 0);
        $apartament_id = isset($apartamentCurentId) ? (int)$apartamentCurentId : 0;
    }

    if ($apartament_id < 1) {
        $errors[] = 'Alege apartamentul.';
    }

    if ($problema === '') {
        $errors[] = 'Problema este obligatorie.';
    }

    if ($descriere === '') {
        $errors[] = 'Descrierea este obligatorie.';
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

    if (isset($_FILES['fotografie']) && $_FILES['fotografie']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['fotografie']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Fotografia nu a putut fi incarcata.';
        } else {
            $maxSize = 2 * 1024 * 1024;
            $extensie = strtolower(pathinfo($_FILES['fotografie']['name'], PATHINFO_EXTENSION));
            $extensiiPermise = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($extensie, $extensiiPermise, true)) {
                $errors[] = 'Fotografia trebuie sa fie JPG, PNG, GIF sau WEBP.';
            } elseif ($_FILES['fotografie']['size'] > $maxSize) {
                $errors[] = 'Fotografia trebuie sa aiba maximum 2MB.';
            } else {
                $uploadDir = 'uploads/mentenanta/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $numeFisier = uniqid('sesizare_', true) . '.' . $extensie;
                $caleFisier = $uploadDir . $numeFisier;

                if (move_uploaded_file($_FILES['fotografie']['tmp_name'], $caleFisier)) {
                    $fotografie = $caleFisier;
                } else {
                    $errors[] = 'Fotografia nu a putut fi salvata.';
                }
            }
        }
    }

    if (!empty($errors)) {
        array_unshift($errors, 'Eroare: completati toate campurile obligatorii.');
    }

    if (empty($errors)) {
        $chiriasParam = $chirias_id > 0 ? $chirias_id : null;
        $stmt = mysqli_prepare($conn, "INSERT INTO cereri_mentenanta (apartament_id, chirias_id, problema, fotografie, descriere, prioritate, status, data_raportare) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iissssss", $apartament_id, $chiriasParam, $problema, $fotografie, $descriere, $prioritate, $status, $data_raportare);

        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Sesizarea a fost adaugata cu succes.');
            header("Location: mentenanta.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la salvarea sesizarii.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaug&#259; sesizare</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Mentenan&#539;&#259;</p>
                <h1>Adaug&#259; sesizare</h1>
            </div>

            <a class="button button-secondary" href="mentenanta.php">&#206;napoi la mentenan&#539;&#259;</a>
        </section>

        <section class="form-card form-card-wide">
            <?php if (!empty($errors)) { ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error) { ?>
                        <p><?php echo e($error); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <label>
                        <span>Apartament</span>
                        <select name="apartament_id" required>
                            <option value="">Alege apartamentul</option>
                            <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                                <?php while($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                                    <option value="<?php echo e($apartament['id']); ?>" <?php echo (int)$apartament_id === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($apartament['adresa']); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Chiria&#537;</span>
                        <select name="chirias_id">
                            <option value="0">F&#259;r&#259; chiria&#537; selectat</option>
                            <?php if ($chiriasi && mysqli_num_rows($chiriasi) > 0) { ?>
                                <?php while($chirias = mysqli_fetch_assoc($chiriasi)) { ?>
                                    <option value="<?php echo e($chirias['id']); ?>" <?php echo (int)$chirias_id === (int)$chirias['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($chirias['nume'] . ' ' . $chirias['prenume'] . ' - ' . ($chirias['adresa_apartament'] ?? '')); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Problem&#259;</span>
                        <input type="text" name="problema" value="<?php echo e($problema); ?>" placeholder="Ex: robinet stricat" required>
                    </label>

                    <label>
                        <span>Fotografie</span>
                        <input type="file" name="fotografie" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                    </label>

                    <label>
                        <span>Prioritate</span>
                        <select name="prioritate" required>
                            <option value="scazuta" <?php echo $prioritate === 'scazuta' ? 'selected' : ''; ?>>Sc&#259;zut&#259;</option>
                            <option value="medie" <?php echo $prioritate === 'medie' ? 'selected' : ''; ?>>Medie</option>
                            <option value="ridicata" <?php echo $prioritate === 'ridicata' ? 'selected' : ''; ?>>Ridicat&#259;</option>
                            <option value="urgenta" <?php echo $prioritate === 'urgenta' ? 'selected' : ''; ?>>Urgent&#259;</option>
                        </select>
                    </label>

                    <label>
                        <span>Status</span>
                        <select name="status" required>
                            <option value="deschisa" <?php echo $status === 'deschisa' ? 'selected' : ''; ?>>Deschis&#259;</option>
                            <option value="in_lucru" <?php echo $status === 'in_lucru' ? 'selected' : ''; ?>>&#206;n lucru</option>
                            <option value="rezolvata" <?php echo $status === 'rezolvata' ? 'selected' : ''; ?>>Rezolvat&#259;</option>
                        </select>
                    </label>

                    <label>
                        <span>Data raport&#259;rii</span>
                        <input type="date" name="data_raportare" value="<?php echo e($data_raportare); ?>" required>
                    </label>

                    <label class="form-full">
                        <span>Descriere problem&#259;</span>
                        <textarea name="descriere" rows="5" required><?php echo e($descriere); ?></textarea>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" name="adauga">
                        Salveaz&#259; sesizarea
                    </button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>
