<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "administrare_apartamente";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Conexiunea la baza de date a eșuat: " . mysqli_connect_error());
}

?>