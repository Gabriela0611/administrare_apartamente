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
    apartament_id INT NOT NULL,
    tip_factura VARCHAR(30) NOT NULL,
    suma DECIMAL(10,2) NOT NULL,
    scadenta DATE NOT NULL,
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

// Add images column to apartamente (silently ignored if it already exists)
mysqli_query($conn, "ALTER TABLE apartamente ADD COLUMN images VARCHAR(2000) NULL DEFAULT NULL");

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
