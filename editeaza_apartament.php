<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('error', 'Eroare: apartamentul nu a fost gasit.');
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM apartamente WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$apartament = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$apartament) {
    set_flash('error', 'Eroare: apartamentul nu a fost gasit.');
    header("Location: index.php");
    exit;
}

$errors = [];
$numar_apartament = $apartament['numar_apartament'] ?? '';
$etaj = $apartament['etaj'] ?? '';
$adresa = $apartament['adresa'] ?? '';
$numar_camere = $apartament['numar_camere'] ?? '';
$suprafata = $apartament['suprafata'] ?? '';
$chirie = $apartament['chirie'] ?? '';
$status = $apartament['status'] ?? 'liber';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['salveaza'])) {
    $numar_apartament = trim($_POST['numar_apartament'] ?? '');
    $etajInput = trim($_POST['etaj'] ?? '');
    $adresa = trim($_POST['adresa'] ?? '');
    $numarCamereInput = trim($_POST['numar_camere'] ?? '');
    $suprafataInput = trim($_POST['suprafata'] ?? '');
    $chirieInput = trim($_POST['chirie'] ?? '');
    $status = trim($_POST['status'] ?? '');

    $etaj = $etajInput;
    $numar_camere = $numarCamereInput;
    $suprafata = $suprafataInput;
    $chirie = $chirieInput;

    if ($numar_apartament === '') {
        $errors[] = 'Numarul apartamentului este obligatoriu.';
    }

    if ($etajInput === '' || !is_numeric($etajInput)) {
        $errors[] = 'Etajul trebuie sa fie numeric.';
    }

    $etajValue = (int)$etajInput;

    if ($adresa === '') {
        $errors[] = 'Adresa este obligatorie.';
    }

    if ($numarCamereInput === '' || !ctype_digit($numarCamereInput) || (int)$numarCamereInput < 1) {
        $errors[] = 'Numarul de camere trebuie sa fie mai mare decat 0.';
    }

    $numarCamereValue = (int)$numarCamereInput;
    $suprafataValue = (float)str_replace(',', '.', $suprafataInput);
    $chirieValue = (float)str_replace(',', '.', $chirieInput);

    if ($suprafataInput === '' || !is_numeric(str_replace(',', '.', $suprafataInput)) || $suprafataValue <= 0) {
        $errors[] = 'Suprafata trebuie sa fie mai mare decat 0.';
    }

    if ($chirieInput === '' || !is_numeric(str_replace(',', '.', $chirieInput)) || $chirieValue < 0) {
        $errors[] = 'Chiria lunara nu poate fi negativa.';
    }

    if (!in_array($status, ['liber', 'ocupat'], true)) {
        $errors[] = 'Statusul selectat nu este valid.';
    }

    if ($numar_apartament !== '' && $adresa !== '') {
        $stmtCheck = mysqli_prepare($conn, "SELECT id FROM apartamente WHERE numar_apartament = ? AND adresa = ? AND id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheck, "ssi", $numar_apartament, $adresa, $id);
        mysqli_stmt_execute($stmtCheck);
        $existing = mysqli_stmt_get_result($stmtCheck);

        if ($existing && mysqli_fetch_assoc($existing)) {
            $errors[] = 'Exista deja un apartament cu acest numar la aceeasi adresa.';
        }
    }

    if (empty($errors)) {
        $stmtUpdate = mysqli_prepare($conn, "UPDATE apartamente
                                            SET numar_apartament = ?, etaj = ?, adresa = ?, numar_camere = ?, suprafata = ?, chirie = ?, status = ?
                                            WHERE id = ?");
        mysqli_stmt_bind_param($stmtUpdate, "sisiddsi", $numar_apartament, $etajValue, $adresa, $numarCamereValue, $suprafataValue, $chirieValue, $status, $id);

        if (mysqli_stmt_execute($stmtUpdate)) {
            set_flash('success', 'Apartamentul a fost actualizat cu succes.');
            header("Location: index.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la actualizarea apartamentului.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editeaz&#259; apartament</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Apartamente</p>
                <h1>Editeaz&#259; apartament</h1>
            </div>

            <a class="button button-secondary" href="index.php">&#206;napoi la list&#259;</a>
        </section>

        <section class="form-card">
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
                        <span>Num&#259;r apartament</span>
                        <input type="text" name="numar_apartament" value="<?php echo e($numar_apartament); ?>" required>
                    </label>

                    <label>
                        <span>Etaj</span>
                        <input type="number" name="etaj" value="<?php echo e($etaj); ?>" required>
                    </label>

                    <label>
                        <span>Adres&#259;</span>
                        <input type="text" name="adresa" value="<?php echo e($adresa); ?>" required>
                    </label>

                    <label>
                        <span>Num&#259;r camere</span>
                        <input type="number" name="numar_camere" min="1" value="<?php echo e($numar_camere); ?>" required>
                    </label>

                    <label>
                        <span>Suprafa&#539;&#259;</span>
                        <input type="number" step="0.01" min="0.01" name="suprafata" value="<?php echo e($suprafata); ?>" required>
                    </label>

                    <label>
                        <span>Chirie lunar&#259;</span>
                        <input type="number" step="0.01" min="0" name="chirie" value="<?php echo e($chirie); ?>" required>
                    </label>

                    <label>
                        <span>Status</span>
                        <select name="status" required>
                            <option value="liber" <?php echo $status === 'liber' ? 'selected' : ''; ?>>Liber</option>
                            <option value="ocupat" <?php echo $status === 'ocupat' ? 'selected' : ''; ?>>Ocupat</option>
                        </select>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" name="salveaza">
                        Salveaz&#259; modific&#259;rile
                    </button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
