<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash('error', 'Eroare: chiriasul nu a fost gasit.');
    header("Location: chiriasi.php");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT * FROM chiriasi WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$chirias = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$chirias) {
    set_flash('error', 'Eroare: chiriasul nu a fost gasit.');
    header("Location: chiriasi.php");
    exit;
}

$errors = [];
$nume = $chirias['nume'] ?? '';
$prenume = $chirias['prenume'] ?? '';
$telefon = $chirias['telefon'] ?? '';
$email = $chirias['email'] ?? '';
$cnp = $chirias['cnp'] ?? '';
$serie_ci = $chirias['serie_ci'] ?? '';
$adresa = $chirias['adresa'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['salveaza'])) {
    $nume = trim($_POST['nume'] ?? '');
    $prenume = trim($_POST['prenume'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cnp = trim($_POST['cnp'] ?? '');
    $serie_ci = trim($_POST['serie_ci'] ?? '');
    $adresa = trim($_POST['adresa'] ?? '');

    if ($nume === '') {
        $errors[] = 'Numele este obligatoriu.';
    }

    if ($prenume === '') {
        $errors[] = 'Prenumele este obligatoriu.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Emailul trebuie sa fie valid.';
    }

    $telefonDigits = preg_replace('/\D+/', '', $telefon);

    if ($telefon === '' || strlen($telefonDigits) < 7 || strlen($telefonDigits) > 15) {
        $errors[] = 'Numarul de telefon trebuie sa fie valid.';
    }

    if (!preg_match('/^[0-9]{13}$/', $cnp)) {
        $errors[] = 'CNP-ul trebuie sa contina 13 cifre.';
    }

    if ($serie_ci === '') {
        $errors[] = 'Seria CI este obligatorie.';
    }

    if ($adresa === '') {
        $errors[] = 'Adresa chiriasului este obligatorie.';
    }

    if (empty($errors)) {
        $stmtUpdate = mysqli_prepare($conn, "UPDATE chiriasi
                                            SET nume = ?, prenume = ?, telefon = ?, email = ?, cnp = ?, serie_ci = ?, adresa = ?
                                            WHERE id = ?");
        mysqli_stmt_bind_param(
            $stmtUpdate,
            "sssssssi",
            $nume,
            $prenume,
            $telefon,
            $email,
            $cnp,
            $serie_ci,
            $adresa,
            $id
        );

        if (mysqli_stmt_execute($stmtUpdate)) {
            set_flash('success', 'Chiriasul a fost actualizat cu succes.');
            header("Location: chiriasi.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la actualizarea chiriasului.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editeaz&#259; chiria&#537;</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Chiria&#537;i</p>
                <h1>Editeaz&#259; chiria&#537;</h1>
            </div>

            <a class="button button-secondary" href="chiriasi.php">&#206;napoi la list&#259;</a>
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
                <h2 class="form-section-title">Date chiria&#537;</h2>
                <div class="form-grid">
                    <label>
                        <span>Nume</span>
                        <input type="text" name="nume" value="<?php echo e($nume); ?>" required>
                    </label>

                    <label>
                        <span>Prenume</span>
                        <input type="text" name="prenume" value="<?php echo e($prenume); ?>" required>
                    </label>

                    <label>
                        <span>Telefon</span>
                        <input type="tel" name="telefon" value="<?php echo e($telefon); ?>" required>
                    </label>

                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo e($email); ?>" required>
                    </label>

                    <label>
                        <span>CNP</span>
                        <input type="text" name="cnp" maxlength="13" value="<?php echo e($cnp); ?>" required>
                    </label>

                    <label>
                        <span>Serie CI</span>
                        <input type="text" name="serie_ci" value="<?php echo e($serie_ci); ?>" required>
                    </label>

                    <label>
                        <span>Adres&#259;</span>
                        <input type="text" name="adresa" value="<?php echo e($adresa); ?>" required>
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
