<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$password = "";
$database = "administrare_apartamente";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Conexiunea la baza de date a esuat: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

$sqlApartamente = "CREATE TABLE IF NOT EXISTS apartamente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numar_apartament VARCHAR(30) NULL,
    etaj INT NULL,
    adresa VARCHAR(255) NOT NULL,
    numar_camere INT NOT NULL,
    suprafata DECIMAL(10,2) NOT NULL,
    chirie DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'liber',
    observatii TEXT NULL,
    images VARCHAR(2000) NULL DEFAULT NULL,
    creat_la TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sqlApartamente);

$sqlChiriasi = "CREATE TABLE IF NOT EXISTS chiriasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    prenume VARCHAR(100) NOT NULL,
    telefon VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    cnp VARCHAR(13) NOT NULL,
    serie_ci VARCHAR(20) NOT NULL,
    adresa VARCHAR(255) NOT NULL,
    data_mutarii DATE NOT NULL,
    apartament_id INT NULL,
    numar_contract VARCHAR(50) NOT NULL,
    data_inceput DATE NOT NULL,
    data_sfarsit DATE NOT NULL,
    chirie_lunara DECIMAL(10,2) NOT NULL,
    garantie DECIMAL(10,2) NOT NULL,
    document_contract TINYINT(1) NOT NULL DEFAULT 0,
    document_copie_ci TINYINT(1) NOT NULL DEFAULT 0,
    document_proces_verbal TINYINT(1) NOT NULL DEFAULT 0,
    document_garantie TINYINT(1) NOT NULL DEFAULT 0,
    creat_la TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sqlChiriasi);

$sqlFacturi = "CREATE TABLE IF NOT EXISTS facturi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numar_factura VARCHAR(50) NULL,
    chirias_id INT NULL,
    apartament_id INT NOT NULL,
    tip_factura VARCHAR(30) NOT NULL,
    suma DECIMAL(10,2) NOT NULL,
    data_emitere DATE NULL,
    scadenta DATE NOT NULL,
    valoare_chirie DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_utilitati DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_mentenanta DECIMAL(10,2) NOT NULL DEFAULT 0,
    valoare_totala DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'neplatita',
    data_platii DATE NULL,
    creat_la TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sqlFacturi);

$sqlMentenanta = "CREATE TABLE IF NOT EXISTS cereri_mentenanta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apartament_id INT NOT NULL,
    chirias_id INT NULL,
    problema VARCHAR(150) NOT NULL,
    fotografie VARCHAR(255) NULL,
    descriere TEXT NOT NULL,
    prioritate VARCHAR(20) NOT NULL DEFAULT 'medie',
    status VARCHAR(20) NOT NULL DEFAULT 'deschisa',
    data_raportare DATE NOT NULL,
    data_rezolvare DATE NULL,
    cost_estimat DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost_final DECIMAL(10,2) NOT NULL DEFAULT 0,
    creat_la TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sqlMentenanta);

$sqlUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    parola VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'chirias',
    chirias_id INT NULL,
    creat_la TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sqlUsers);

if (!function_exists('db_column_exists')) {
    function db_column_exists($conn, $table, $column) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total
                                       FROM INFORMATION_SCHEMA.COLUMNS
                                       WHERE TABLE_SCHEMA = DATABASE()
                                         AND TABLE_NAME = ?
                                         AND COLUMN_NAME = ?");
        mysqli_stmt_bind_param($stmt, "ss", $table, $column);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        return (int)($row['total'] ?? 0) > 0;
    }
}

if (!function_exists('db_index_exists')) {
    function db_index_exists($conn, $table, $index) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total
                                       FROM INFORMATION_SCHEMA.STATISTICS
                                       WHERE TABLE_SCHEMA = DATABASE()
                                         AND TABLE_NAME = ?
                                         AND INDEX_NAME = ?");
        mysqli_stmt_bind_param($stmt, "ss", $table, $index);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        return (int)($row['total'] ?? 0) > 0;
    }
}

if (!db_column_exists($conn, 'apartamente', 'numar_apartament')) {
    mysqli_query($conn, "ALTER TABLE apartamente ADD COLUMN numar_apartament VARCHAR(30) NULL AFTER id");
}

if (!db_column_exists($conn, 'apartamente', 'etaj')) {
    mysqli_query($conn, "ALTER TABLE apartamente ADD COLUMN etaj INT NULL AFTER numar_apartament");
}

if (!db_column_exists($conn, 'apartamente', 'observatii')) {
    mysqli_query($conn, "ALTER TABLE apartamente ADD COLUMN observatii TEXT NULL AFTER status");
}

if (!db_column_exists($conn, 'apartamente', 'images')) {
    mysqli_query($conn, "ALTER TABLE apartamente ADD COLUMN images VARCHAR(2000) NULL DEFAULT NULL");
}

if (db_index_exists($conn, 'apartamente', 'idx_apartamente_numar_unic')) {
    mysqli_query($conn, "ALTER TABLE apartamente DROP INDEX idx_apartamente_numar_unic");
}

if (db_index_exists($conn, 'apartamente', 'idx_apartamente_adresa_numar_unic')) {
    mysqli_query($conn, "ALTER TABLE apartamente DROP INDEX idx_apartamente_adresa_numar_unic");
}

if (!db_column_exists($conn, 'cereri_mentenanta', 'data_rezolvare')) {
    mysqli_query($conn, "ALTER TABLE cereri_mentenanta ADD COLUMN data_rezolvare DATE NULL AFTER data_raportare");
}

if (!db_column_exists($conn, 'cereri_mentenanta', 'cost_estimat')) {
    mysqli_query($conn, "ALTER TABLE cereri_mentenanta ADD COLUMN cost_estimat DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER data_rezolvare");
}

if (!db_column_exists($conn, 'cereri_mentenanta', 'cost_final')) {
    mysqli_query($conn, "ALTER TABLE cereri_mentenanta ADD COLUMN cost_final DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER cost_estimat");
}

if (!db_column_exists($conn, 'facturi', 'numar_factura')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN numar_factura VARCHAR(50) NULL AFTER id");
}

if (!db_column_exists($conn, 'facturi', 'chirias_id')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN chirias_id INT NULL AFTER numar_factura");
}

if (!db_column_exists($conn, 'facturi', 'data_emitere')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN data_emitere DATE NULL AFTER suma");
}

if (!db_column_exists($conn, 'facturi', 'valoare_chirie')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN valoare_chirie DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER scadenta");
}

if (!db_column_exists($conn, 'facturi', 'cost_utilitati')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN cost_utilitati DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER valoare_chirie");
}

if (!db_column_exists($conn, 'facturi', 'cost_mentenanta')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN cost_mentenanta DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER cost_utilitati");
}

if (!db_column_exists($conn, 'facturi', 'valoare_totala')) {
    mysqli_query($conn, "ALTER TABLE facturi ADD COLUMN valoare_totala DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER cost_mentenanta");
}

mysqli_query($conn, "UPDATE facturi SET numar_factura = CONCAT('F-', id) WHERE numar_factura IS NULL OR numar_factura = ''");
mysqli_query($conn, "UPDATE facturi SET data_emitere = COALESCE(data_emitere, scadenta) WHERE data_emitere IS NULL");
mysqli_query($conn, "UPDATE facturi SET valoare_chirie = suma WHERE valoare_chirie = 0 AND tip_factura = 'chirie'");
mysqli_query($conn, "UPDATE facturi SET cost_utilitati = suma WHERE cost_utilitati = 0 AND tip_factura <> 'chirie'");
mysqli_query($conn, "UPDATE facturi SET valoare_totala = suma WHERE valoare_totala = 0");
mysqli_query($conn, "UPDATE facturi f
                     LEFT JOIN chiriasi c ON c.apartament_id = f.apartament_id
                     SET f.chirias_id = c.id
                     WHERE f.chirias_id IS NULL");

mysqli_query($conn, "UPDATE apartamente a
                     SET status = 'ocupat'
                     WHERE EXISTS (
                         SELECT 1 FROM chiriasi c
                         WHERE c.apartament_id = a.id
                           AND c.data_inceput <= CURDATE()
                           AND c.data_sfarsit >= CURDATE()
                     )");

mysqli_query($conn, "UPDATE apartamente a
                     SET status = 'liber'
                     WHERE NOT EXISTS (
                         SELECT 1 FROM chiriasi c
                         WHERE c.apartament_id = a.id
                           AND c.data_inceput <= CURDATE()
                           AND c.data_sfarsit >= CURDATE()
                     )");

$adminEmail = 'admin@test.com';
$adminCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($adminCheck, "s", $adminEmail);
mysqli_stmt_execute($adminCheck);
$adminResult = mysqli_stmt_get_result($adminCheck);

if (!mysqli_fetch_assoc($adminResult)) {
    $adminParola = password_hash('admin123', PASSWORD_DEFAULT);
    $adminRole = 'admin';
    $stmt = mysqli_prepare($conn, "INSERT INTO users (email, parola, role) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $adminEmail, $adminParola, $adminRole);
    mysqli_stmt_execute($stmt);
}
