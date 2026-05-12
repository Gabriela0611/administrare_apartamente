<?php
include "config/db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$adresa = '';
$numar_camere = '';
$suprafata = '';
$chirie = '';
$status = 'liber';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga'])) {
    $adresa = trim($_POST['adresa'] ?? '');
    $numar_camere = (int)($_POST['numar_camere'] ?? 0);
    $suprafataInput = trim($_POST['suprafata'] ?? '');
    $suprafata = $suprafataInput === '' ? null : (float)$suprafataInput;
    $chirie = (float)($_POST['chirie'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($adresa === '') {
        $errors[] = 'Adresa este obligatorie.';
    }

    if ($numar_camere < 1) {
        $errors[] = 'Numarul de camere trebuie sa fie cel putin 1.';
    }

    if ($suprafata !== null && $suprafata <= 0) {
        $errors[] = 'Suprafata trebuie sa fie un numar pozitiv.';
    }

    if ($chirie <= 0) {
        $errors[] = 'Chiria trebuie sa fie un numar pozitiv.';
    }

    if (!in_array($status, ['liber', 'ocupat'], true)) {
        $errors[] = 'Statusul selectat nu este valid.';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO apartamente (adresa, numar_camere, suprafata, chirie, status) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sidds", $adresa, $numar_camere, $suprafata, $chirie, $status);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: index.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la salvarea apartamentului.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaug&#259; apartament</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Apartamente</p>
                <h1>Adaug&#259; apartament</h1>
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
                        <span>Adres&#259;</span>
                        <input type="text" name="adresa" value="<?php echo e($adresa); ?>" required>
                    </label>

                    <label>
                        <span>Num&#259;r camere</span>
                        <input type="number" name="numar_camere" min="1" value="<?php echo e($numar_camere); ?>" required>
                    </label>

                    <label>
                        <span>Suprafa&#539;&#259;</span>
                        <input type="number" step="0.01" min="0.01" name="suprafata" value="<?php echo e($suprafata); ?>">
                    </label>

                    <label>
                        <span>Chirie</span>
                        <input type="number" step="0.01" min="0.01" name="chirie" value="<?php echo e($chirie); ?>" required>
                    </label>

                    <label>
                        <span>Status</span>
                        <select name="status">
                            <option value="liber" <?php echo $status === 'liber' ? 'selected' : ''; ?>>Liber</option>
                            <option value="ocupat" <?php echo $status === 'ocupat' ? 'selected' : ''; ?>>Ocupat</option>
                        </select>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" name="adauga">
                        Salveaz&#259; apartamentul
                    </button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>
