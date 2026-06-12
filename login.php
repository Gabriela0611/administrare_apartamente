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

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$email = '';

if (!empty($_SESSION['user_logged_in'])) {
    header("Location: dashboard.php");
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $parola = trim($_POST['parola'] ?? '');

    $stmt = mysqli_prepare($conn, "SELECT id, email, parola, role, chirias_id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($parola, $user['parola'])) {
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_chirias_id'] = $user['chirias_id'] ? (int)$user['chirias_id'] : null;

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $user['email'];
        header("Location: dashboard.php");
        exit;
    }

    $errors[] = 'Emailul sau parola nu sunt corecte.';
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body>

    <main class="auth-shell">
        <section class="form-card login-card">
            <div class="login-header">
                <p class="eyebrow">Administrare apartamente</p>
                <h1>Autentificare</h1>
            </div>

            <?php if (!empty($errors)) { ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error) { ?>
                        <p><?php echo e($error); ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <form method="POST">
                <div class="form-grid login-grid">
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?php echo e($email); ?>" required>
                    </label>

                    <label>
                        <span>Parol&#259;</span>
                        <input type="password" name="parola" required>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Intr&#259; &#238;n cont</button>
                </div>
            </form>

            <div class="auth-links">
                <span class="muted-text">Nu ai cont?</span>
                <a class="button button-secondary" href="register.php">Creeaza unul</a>
            </div>
        </section>
    </main>

</body>
</html>