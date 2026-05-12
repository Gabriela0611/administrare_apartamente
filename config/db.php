<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "administrare_apartamente";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Conexiunea la baza de date a esuat: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
