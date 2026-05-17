<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$tipuriFacturi = ['chirie', 'apa', 'gaz', 'curent', 'internet', 'intretinere'];
$errors = [];
$apartament_id = '';
$tip_factura = 'chirie';
$suma = '';
$scadenta = '';
$status = 'neplatita';
$data_platii = '';

$apartamente = mysqli_query($conn, "SELECT id, adresa FROM apartamente ORDER BY adresa ASC");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['adauga'])) {
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $tip_factura = trim($_POST['tip_factura'] ?? '');
    $sumaInput = trim($_POST['suma'] ?? '');
    $suma = $sumaInput === '' ? '' : (float)$sumaInput;
    $scadenta = trim($_POST['scadenta'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $data_platii = trim($_POST['data_platii'] ?? '');

    if ($apartament_id < 1) {
        $errors[] = 'Alege apartamentul.';
    }

    if (!in_array($tip_factura, $tipuriFacturi, true)) {
        $errors[] = 'Tipul facturii nu este valid.';
    }

    if ($sumaInput === '') {
        $errors[] = 'Nu poti adauga factura fara suma.';
    } elseif ($suma <= 0) {
        $errors[] = 'Suma facturii trebuie sa fie mai mare decat 0.';
    }

    if ($scadenta === '') {
        $errors[] = 'Data scadentei trebuie completata.';
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

    if (!empty($errors)) {
        array_unshift($errors, 'Eroare: completati toate campurile obligatorii.');
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO facturi (apartament_id, tip_factura, suma, scadenta, status, data_platii) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isdsss", $apartament_id, $tip_factura, $suma, $scadenta, $status, $data_platii);

        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Factura a fost adaugata cu succes.');
            header("Location: facturi.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la salvarea facturii.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaug&#259; factur&#259;</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Facturi</p>
                <h1>Adaug&#259; factur&#259;</h1>
            </div>

            <a class="button button-secondary" href="facturi.php">&#206;napoi la facturi</a>
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
                        <span>Tip factur&#259;</span>
                        <select name="tip_factura" required>
                            <?php foreach ($tipuriFacturi as $tip) { ?>
                                <option value="<?php echo e($tip); ?>" <?php echo $tip_factura === $tip ? 'selected' : ''; ?>>
                                    <?php echo e(ucfirst($tip)); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>

                    <label>
                        <span>Sum&#259;</span>
                        <input type="number" step="0.01" min="0.01" name="suma" value="<?php echo e($suma); ?>" required>
                    </label>

                    <label>
                        <span>Scaden&#539;&#259;</span>
                        <input type="date" name="scadenta" value="<?php echo e($scadenta); ?>" required>
                    </label>

                    <label>
                        <span>Status</span>
                        <select name="status" required>
                            <option value="neplatita" <?php echo $status === 'neplatita' ? 'selected' : ''; ?>>Nepl&#259;tit&#259;</option>
                            <option value="platita" <?php echo $status === 'platita' ? 'selected' : ''; ?>>Pl&#259;tit&#259;</option>
                        </select>
                    </label>

                    <label>
                        <span>Data pl&#259;&#539;ii</span>
                        <input type="date" name="data_platii" value="<?php echo e($data_platii); ?>">
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" name="adauga">
                        Salveaz&#259; factura
                    </button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>
