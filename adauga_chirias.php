<?php
include "config/db.php";
include "auth.php";
include_once "flash.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$nume = '';
$prenume = '';
$telefon = '';
$email = '';
$cnp = '';
$serie_ci = '';
$adresa = '';
$data_mutarii = '';
$apartament_id = '';
$numar_contract = '';
$data_inceput = '';
$data_sfarsit = '';
$chirie_lunara = '';
$garantie = '';
$document_contract = 0;
$document_copie_ci = 0;
$document_proces_verbal = 0;
$document_garantie = 0;

$apartamente = mysqli_query($conn, "SELECT id, adresa FROM apartamente ORDER BY adresa ASC");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['adauga'])) {
    $nume = trim($_POST['nume'] ?? '');
    $prenume = trim($_POST['prenume'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cnp = trim($_POST['cnp'] ?? '');
    $serie_ci = trim($_POST['serie_ci'] ?? '');
    $adresa = trim($_POST['adresa'] ?? '');
    $data_mutarii = trim($_POST['data_mutarii'] ?? '');
    $apartament_id = (int)($_POST['apartament_id'] ?? 0);
    $numar_contract = trim($_POST['numar_contract'] ?? '');
    $data_inceput = trim($_POST['data_inceput'] ?? '');
    $data_sfarsit = trim($_POST['data_sfarsit'] ?? '');
    $chirie_lunara = (float)($_POST['chirie_lunara'] ?? 0);
    $garantie = (float)($_POST['garantie'] ?? 0);
    $document_contract = isset($_POST['document_contract']) ? 1 : 0;
    $document_copie_ci = isset($_POST['document_copie_ci']) ? 1 : 0;
    $document_proces_verbal = isset($_POST['document_proces_verbal']) ? 1 : 0;
    $document_garantie = isset($_POST['document_garantie']) ? 1 : 0;

    if ($nume === '') {
        $errors[] = 'Numele nu poate fi gol.';
    }

    if ($prenume === '') {
        $errors[] = 'Prenumele este obligatoriu.';
    }

    if ($telefon === '') {
        $errors[] = 'Telefonul este obligatoriu.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Emailul trebuie sa fie valid.';
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

    if ($data_mutarii === '') {
        $errors[] = 'Data mutarii este obligatorie.';
    }

    if ($apartament_id < 1) {
        $errors[] = 'Nu poti adauga chirias fara apartament.';
    }

    if ($numar_contract === '') {
        $errors[] = 'Numarul contractului este obligatoriu.';
    }

    if ($data_inceput === '' || $data_sfarsit === '') {
        $errors[] = 'Datele de inceput si sfarsit ale contractului sunt obligatorii.';
    } elseif ($data_sfarsit < $data_inceput) {
        $errors[] = 'Data de sfarsit trebuie sa fie dupa data de inceput.';
    }

    if ($chirie_lunara <= 0) {
        $errors[] = 'Chiria lunara trebuie sa fie un numar pozitiv.';
    }

    if ($garantie < 0) {
        $errors[] = 'Garantia nu poate fi negativa.';
    }

    if (!empty($errors)) {
        array_unshift($errors, 'Eroare: completati toate campurile obligatorii.');
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO chiriasi (
            nume, prenume, telefon, email, cnp, serie_ci, adresa, data_mutarii, apartament_id,
            numar_contract, data_inceput, data_sfarsit, chirie_lunara, garantie,
            document_contract, document_copie_ci, document_proces_verbal, document_garantie
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssisssddiiii",
            $nume,
            $prenume,
            $telefon,
            $email,
            $cnp,
            $serie_ci,
            $adresa,
            $data_mutarii,
            $apartament_id,
            $numar_contract,
            $data_inceput,
            $data_sfarsit,
            $chirie_lunara,
            $garantie,
            $document_contract,
            $document_copie_ci,
            $document_proces_verbal,
            $document_garantie
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_query($conn, "UPDATE apartamente SET status = 'ocupat' WHERE id = " . (int)$apartament_id);
            set_flash('success', 'Chirias adaugat cu succes.');
            header("Location: chiriasi.php");
            exit;
        }

        $errors[] = 'A aparut o eroare la salvarea chiriasului.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaug&#259; chiria&#537;</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>

    <main class="page-shell">
        <section class="page-header">
            <div>
                <p class="eyebrow">Chiria&#537;i</p>
                <h1>Adaug&#259; chiria&#537;</h1>
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

                    <label>
                        <span>Data mut&#259;rii</span>
                        <input type="date" name="data_mutarii" value="<?php echo e($data_mutarii); ?>" required>
                    </label>

                    <label>
                        <span>Apartamentul &#238;nchiriat</span>
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
                </div>

                <h2 class="form-section-title">Contract</h2>
                <div class="form-grid">
                    <label>
                        <span>Num&#259;r contract</span>
                        <input type="text" name="numar_contract" value="<?php echo e($numar_contract); ?>" required>
                    </label>

                    <label>
                        <span>Data &#238;nceput</span>
                        <input type="date" name="data_inceput" value="<?php echo e($data_inceput); ?>" required>
                    </label>

                    <label>
                        <span>Data sf&#226;r&#537;it</span>
                        <input type="date" name="data_sfarsit" value="<?php echo e($data_sfarsit); ?>" required>
                    </label>

                    <label>
                        <span>Chirie lunar&#259;</span>
                        <input type="number" step="0.01" min="0.01" name="chirie_lunara" value="<?php echo e($chirie_lunara); ?>" required>
                    </label>

                    <label>
                        <span>Garan&#539;ie</span>
                        <input type="number" step="0.01" min="0" name="garantie" value="<?php echo e($garantie); ?>" required>
                    </label>
                </div>

                <h2 class="form-section-title">Documente primite</h2>
                <div class="checkbox-grid">
                    <label class="check-option">
                        <input type="checkbox" name="document_contract" <?php echo $document_contract ? 'checked' : ''; ?>>
                        <span>Contract de &#238;nchiriere</span>
                    </label>

                    <label class="check-option">
                        <input type="checkbox" name="document_copie_ci" <?php echo $document_copie_ci ? 'checked' : ''; ?>>
                        <span>Copie CI</span>
                    </label>

                    <label class="check-option">
                        <input type="checkbox" name="document_proces_verbal" <?php echo $document_proces_verbal ? 'checked' : ''; ?>>
                        <span>Proces verbal predare-primire</span>
                    </label>

                    <label class="check-option">
                        <input type="checkbox" name="document_garantie" <?php echo $document_garantie ? 'checked' : ''; ?>>
                        <span>Dovada pl&#259;&#539;ii garan&#539;iei</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit" name="adauga">
                        Salveaz&#259; chiria&#537;ul
                    </button>
                </div>
            </form>
        </section>
    </main>

</body>
</html>
