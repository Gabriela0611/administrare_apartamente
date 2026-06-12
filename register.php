<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';

    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0777, true);
    }

    session_save_path($sessionDir);
    session_start();
}

include "config/db.php";
include_once "flash.php";

if (!empty($_SESSION['user_logged_in'])) {
    header("Location: dashboard.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [
    'proprietar' => [],
    'chirias' => [],
];

$submittedType = '';

$proprietarEmail = '';
$proprietarParola = '';
$proprietarConfirmare = '';

$chiriasNume = '';
$chiriasPrenume = '';
$chiriasTelefon = '';
$chiriasEmail = '';
$chiriasParola = '';
$chiriasConfirmare = '';
$chiriasCnp = '';
$chiriasSerieCi = '';
$chiriasAdresa = '';
$chiriasDataMutarii = '';
$chiriasApartamentId = '';
$chiriasNumarContract = '';
$chiriasDataInceput = '';
$chiriasDataSfarsit = '';
$chiriasChirieLunara = '';
$chiriasGarantie = '';

$apartamente = mysqli_query($conn, "SELECT id, adresa, status FROM apartamente ORDER BY adresa ASC");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $submittedType = trim($_POST['register_type'] ?? '');

    if ($submittedType === 'proprietar') {
        $proprietarEmail = trim($_POST['email'] ?? '');
        $proprietarParola = (string)($_POST['parola'] ?? '');
        $proprietarConfirmare = (string)($_POST['confirmare_parola'] ?? '');

        if (!filter_var($proprietarEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['proprietar'][] = 'Emailul trebuie sa fie valid.';
        }

        if (strlen($proprietarParola) < 8) {
            $errors['proprietar'][] = 'Parola trebuie sa aiba cel putin 8 caractere.';
        }

        if ($proprietarParola !== $proprietarConfirmare) {
            $errors['proprietar'][] = 'Parolele nu coincid.';
        }

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $proprietarEmail);
        mysqli_stmt_execute($stmt);
        $existingUser = mysqli_stmt_get_result($stmt);

        if ($existingUser && mysqli_fetch_assoc($existingUser)) {
            $errors['proprietar'][] = 'Exista deja un cont cu acest email.';
        }

        if (empty($errors['proprietar'])) {
            $hash = password_hash($proprietarParola, PASSWORD_DEFAULT);
            $role = 'proprietar';
            $stmtInsert = mysqli_prepare($conn, "INSERT INTO users (email, parola, role) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmtInsert, "sss", $proprietarEmail, $hash, $role);

            if (mysqli_stmt_execute($stmtInsert)) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['user_email'] = $proprietarEmail;
                $_SESSION['user_role'] = 'proprietar';
                $_SESSION['user_chirias_id'] = null;

                set_flash('success', 'Contul de proprietar a fost creat.');
                header("Location: dashboard.php");
                exit;
            }

            $errors['proprietar'][] = 'Nu am putut crea contul de proprietar.';
        }
    } elseif ($submittedType === 'chirias') {
        $chiriasNume = trim($_POST['nume'] ?? '');
        $chiriasPrenume = trim($_POST['prenume'] ?? '');
        $chiriasTelefon = trim($_POST['telefon'] ?? '');
        $chiriasEmail = trim($_POST['email'] ?? '');
        $chiriasParola = (string)($_POST['parola'] ?? '');
        $chiriasConfirmare = (string)($_POST['confirmare_parola'] ?? '');
        $chiriasCnp = trim($_POST['cnp'] ?? '');
        $chiriasSerieCi = trim($_POST['serie_ci'] ?? '');
        $chiriasAdresa = trim($_POST['adresa'] ?? '');
        $chiriasDataMutarii = trim($_POST['data_mutarii'] ?? '');
        $chiriasApartamentId = (int)($_POST['apartament_id'] ?? 0);
        $chiriasNumarContract = trim($_POST['numar_contract'] ?? '');
        $chiriasDataInceput = trim($_POST['data_inceput'] ?? '');
        $chiriasDataSfarsit = trim($_POST['data_sfarsit'] ?? '');
        $chiriasChirieLunara = trim($_POST['chirie_lunara'] ?? '');
        $chiriasGarantie = trim($_POST['garantie'] ?? '');

        if ($chiriasNume === '') {
            $errors['chirias'][] = 'Numele este obligatoriu.';
        }

        if ($chiriasPrenume === '') {
            $errors['chirias'][] = 'Prenumele este obligatoriu.';
        }

        if ($chiriasTelefon === '') {
            $errors['chirias'][] = 'Telefonul este obligatoriu.';
        }

        if (!filter_var($chiriasEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['chirias'][] = 'Emailul trebuie sa fie valid.';
        }

        if (strlen($chiriasParola) < 8) {
            $errors['chirias'][] = 'Parola trebuie sa aiba cel putin 8 caractere.';
        }

        if ($chiriasParola !== $chiriasConfirmare) {
            $errors['chirias'][] = 'Parolele nu coincid.';
        }

        if (!preg_match('/^[0-9]{13}$/', $chiriasCnp)) {
            $errors['chirias'][] = 'CNP-ul trebuie sa contina 13 cifre.';
        }

        if ($chiriasSerieCi === '') {
            $errors['chirias'][] = 'Seria CI este obligatorie.';
        }

        if ($chiriasAdresa === '') {
            $errors['chirias'][] = 'Adresa este obligatorie.';
        }

        if ($chiriasDataMutarii === '') {
            $errors['chirias'][] = 'Data mutarii este obligatorie.';
        }

        if ($chiriasApartamentId < 1) {
            $errors['chirias'][] = 'Trebuie sa alegi un apartament.';
        }

        if ($chiriasNumarContract === '') {
            $errors['chirias'][] = 'Numarul contractului este obligatoriu.';
        }

        if ($chiriasDataInceput === '' || $chiriasDataSfarsit === '') {
            $errors['chirias'][] = 'Datele contractului sunt obligatorii.';
        } elseif ($chiriasDataSfarsit < $chiriasDataInceput) {
            $errors['chirias'][] = 'Data de sfarsit trebuie sa fie dupa data de inceput.';
        }

        $chirieNumerica = (float)str_replace(',', '.', $chiriasChirieLunara);
        $garantieNumerica = (float)str_replace(',', '.', $chiriasGarantie);

        if ($chirieNumerica <= 0) {
            $errors['chirias'][] = 'Chiria lunara trebuie sa fie pozitiva.';
        }

        if ($garantieNumerica < 0) {
            $errors['chirias'][] = 'Garantia nu poate fi negativa.';
        }

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $chiriasEmail);
        mysqli_stmt_execute($stmt);
        $existingUser = mysqli_stmt_get_result($stmt);

        if ($existingUser && mysqli_fetch_assoc($existingUser)) {
            $errors['chirias'][] = 'Exista deja un cont cu acest email.';
        }

        $stmt = mysqli_prepare($conn, "SELECT id FROM apartamente WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $chiriasApartamentId);
        mysqli_stmt_execute($stmt);
        $apartamentResult = mysqli_stmt_get_result($stmt);
        $apartamentRow = $apartamentResult ? mysqli_fetch_assoc($apartamentResult) : null;

        if (!$apartamentRow) {
            $errors['chirias'][] = 'Apartamentul selectat nu exista.';
        }

        if (empty($errors['chirias'])) {
            mysqli_begin_transaction($conn);

            $stmtChirias = mysqli_prepare($conn, "SELECT id FROM chiriasi WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($stmtChirias, "s", $chiriasEmail);
            mysqli_stmt_execute($stmtChirias);
            $existingChiriasResult = mysqli_stmt_get_result($stmtChirias);
            $existingChirias = $existingChiriasResult ? mysqli_fetch_assoc($existingChiriasResult) : null;

            if ($existingChirias) {
                $chiriasId = (int)$existingChirias['id'];
                $stmtUpdate = mysqli_prepare($conn, "UPDATE chiriasi
                                                     SET nume = ?, prenume = ?, telefon = ?, cnp = ?, serie_ci = ?, adresa = ?, data_mutarii = ?, apartament_id = ?, numar_contract = ?, data_inceput = ?, data_sfarsit = ?, chirie_lunara = ?, garantie = ?
                                                     WHERE id = ?");
                mysqli_stmt_bind_param(
                    $stmtUpdate,
                    "sssssssisssddi",
                    $chiriasNume,
                    $chiriasPrenume,
                    $chiriasTelefon,
                    $chiriasCnp,
                    $chiriasSerieCi,
                    $chiriasAdresa,
                    $chiriasDataMutarii,
                    $chiriasApartamentId,
                    $chiriasNumarContract,
                    $chiriasDataInceput,
                    $chiriasDataSfarsit,
                    $chirieNumerica,
                    $garantieNumerica,
                    $chiriasId
                );

                if (!mysqli_stmt_execute($stmtUpdate)) {
                    mysqli_rollback($conn);
                    $errors['chirias'][] = 'Nu am putut actualiza datele chiriasului.';
                }
            } else {
                $documentContract = 0;
                $documentCopieCi = 0;
                $documentProcesVerbal = 0;
                $documentGarantie = 0;
                $stmtInsertChirias = mysqli_prepare($conn, "INSERT INTO chiriasi (
                    nume, prenume, telefon, email, cnp, serie_ci, adresa, data_mutarii, apartament_id,
                    numar_contract, data_inceput, data_sfarsit, chirie_lunara, garantie,
                    document_contract, document_copie_ci, document_proces_verbal, document_garantie
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param(
                    $stmtInsertChirias,
                    "ssssssssisssddiiii",
                    $chiriasNume,
                    $chiriasPrenume,
                    $chiriasTelefon,
                    $chiriasEmail,
                    $chiriasCnp,
                    $chiriasSerieCi,
                    $chiriasAdresa,
                    $chiriasDataMutarii,
                    $chiriasApartamentId,
                    $chiriasNumarContract,
                    $chiriasDataInceput,
                    $chiriasDataSfarsit,
                    $chirieNumerica,
                    $garantieNumerica,
                    $documentContract,
                    $documentCopieCi,
                    $documentProcesVerbal,
                    $documentGarantie
                );

                if (!mysqli_stmt_execute($stmtInsertChirias)) {
                    mysqli_rollback($conn);
                    $errors['chirias'][] = 'Nu am putut salva profilul de chirias.';
                } else {
                    $chiriasId = mysqli_insert_id($conn);
                }
            }

            if (empty($errors['chirias'])) {
                $hash = password_hash($chiriasParola, PASSWORD_DEFAULT);
                $role = 'chirias';
                $stmtInsertUser = mysqli_prepare($conn, "INSERT INTO users (email, parola, role, chirias_id) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtInsertUser, "sssi", $chiriasEmail, $hash, $role, $chiriasId);

                if (mysqli_stmt_execute($stmtInsertUser)) {
                    $userId = mysqli_insert_id($conn);
                    $stmtUpdateApartament = mysqli_prepare($conn, "UPDATE apartamente SET status = 'ocupat' WHERE id = ?");
                    mysqli_stmt_bind_param($stmtUpdateApartament, "i", $chiriasApartamentId);

                    if (mysqli_stmt_execute($stmtUpdateApartament)) {
                        mysqli_commit($conn);

                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['user_email'] = $chiriasEmail;
                        $_SESSION['user_role'] = 'chirias';
                        $_SESSION['user_chirias_id'] = $chiriasId;

                        set_flash('success', 'Contul de chirias a fost creat.');
                        header("Location: dashboard.php");
                        exit;
                    }
                }

                mysqli_rollback($conn);
                $errors['chirias'][] = 'Nu am putut crea contul de chirias.';
            }
        }
    } else {
        $errors['proprietar'][] = 'Tipul de inregistrare nu este valid.';
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inregistrare</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>

    <main class="auth-shell auth-shell-register">
        <section class="page-header auth-page-header">
            <div>
                <p class="eyebrow">Cont nou</p>
                <h1>Creeaza un cont</h1>
                <p class="muted-text">Alege varianta potrivita pentru proprietar sau chirias.</p>
            </div>

            <a class="button button-secondary" href="login.php">Am deja cont</a>
        </section>

        <section class="register-layout">
            <article class="form-card register-card">
                <div class="register-card-head">
                    <p class="eyebrow">Proprietar</p>
                    <h2>Cont de proprietar</h2>
                    <p class="muted-text">Acces la dashboard, apartamente, facturi si rapoarte.</p>
                </div>

                <?php if ($submittedType === 'proprietar' && !empty($errors['proprietar'])) { ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors['proprietar'] as $error) { ?>
                            <p><?php echo e($error); ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>

                <form method="POST" class="register-form">
                    <input type="hidden" name="register_type" value="proprietar">

                    <div class="form-grid">
                        <label class="form-full">
                            <span>Email</span>
                            <input type="email" name="email" value="<?php echo e($proprietarEmail); ?>" required>
                        </label>

                        <label>
                            <span>Parola</span>
                            <input type="password" name="parola" required>
                        </label>

                        <label>
                            <span>Confirma parola</span>
                            <input type="password" name="confirmare_parola" required>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="button button-primary" type="submit">Creeaza cont proprietar</button>
                    </div>
                </form>
            </article>

            <article class="form-card register-card register-card-wide">
                <div class="register-card-head">
                    <p class="eyebrow">Chirias</p>
                    <h2>Cont de chirias</h2>
                    <p class="muted-text">Completeaza datele de contract ca sa iti fie legat apartamentul.</p>
                </div>

                <?php if ($submittedType === 'chirias' && !empty($errors['chirias'])) { ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors['chirias'] as $error) { ?>
                            <p><?php echo e($error); ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>

                <form method="POST" class="register-form">
                    <input type="hidden" name="register_type" value="chirias">

                    <h3 class="form-section-title">Date de cont</h3>
                    <div class="form-grid">
                        <label>
                            <span>Nume</span>
                            <input type="text" name="nume" value="<?php echo e($chiriasNume); ?>" required>
                        </label>

                        <label>
                            <span>Prenume</span>
                            <input type="text" name="prenume" value="<?php echo e($chiriasPrenume); ?>" required>
                        </label>

                        <label>
                            <span>Telefon</span>
                            <input type="tel" name="telefon" value="<?php echo e($chiriasTelefon); ?>" required>
                        </label>

                        <label>
                            <span>Email</span>
                            <input type="email" name="email" value="<?php echo e($chiriasEmail); ?>" required>
                        </label>

                        <label>
                            <span>Parola</span>
                            <input type="password" name="parola" required>
                        </label>

                        <label>
                            <span>Confirma parola</span>
                            <input type="password" name="confirmare_parola" required>
                        </label>
                    </div>

                    <h3 class="form-section-title">Date chirias</h3>
                    <div class="form-grid">
                        <label>
                            <span>CNP</span>
                            <input type="text" name="cnp" maxlength="13" value="<?php echo e($chiriasCnp); ?>" required>
                        </label>

                        <label>
                            <span>Serie CI</span>
                            <input type="text" name="serie_ci" value="<?php echo e($chiriasSerieCi); ?>" required>
                        </label>

                        <label class="form-full">
                            <span>Adresa</span>
                            <input type="text" name="adresa" value="<?php echo e($chiriasAdresa); ?>" required>
                        </label>

                        <label>
                            <span>Data mutarii</span>
                            <input type="date" name="data_mutarii" value="<?php echo e($chiriasDataMutarii); ?>" required>
                        </label>

                        <label>
                            <span>Apartament</span>
                            <select name="apartament_id" required>
                                <option value="">Alege apartamentul</option>
                                <?php if ($apartamente && mysqli_num_rows($apartamente) > 0) { ?>
                                    <?php while ($apartament = mysqli_fetch_assoc($apartamente)) { ?>
                                        <option value="<?php echo e($apartament['id']); ?>" <?php echo (int)$chiriasApartamentId === (int)$apartament['id'] ? 'selected' : ''; ?>>
                                            <?php echo e($apartament['adresa'] . ' (' . ucfirst($apartament['status']) . ')'); ?>
                                        </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </label>

                        <label>
                            <span>Numar contract</span>
                            <input type="text" name="numar_contract" value="<?php echo e($chiriasNumarContract); ?>" required>
                        </label>

                        <label>
                            <span>Data inceput</span>
                            <input type="date" name="data_inceput" value="<?php echo e($chiriasDataInceput); ?>" required>
                        </label>

                        <label>
                            <span>Data sfarsit</span>
                            <input type="date" name="data_sfarsit" value="<?php echo e($chiriasDataSfarsit); ?>" required>
                        </label>

                        <label>
                            <span>Chirie lunara</span>
                            <input type="number" step="0.01" min="0.01" name="chirie_lunara" value="<?php echo e($chiriasChirieLunara); ?>" required>
                        </label>

                        <label>
                            <span>Garantie</span>
                            <input type="number" step="0.01" min="0" name="garantie" value="<?php echo e($chiriasGarantie); ?>" required>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button class="button button-primary" type="submit">Creeaza cont chirias</button>
                    </div>
                </form>
            </article>
        </section>
    </main>

</body>
</html>