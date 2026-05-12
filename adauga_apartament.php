<?php
include "config/db.php";

if(isset($_POST['adauga'])) {

    $adresa = $_POST['adresa'];
    $numar_camere = $_POST['numar_camere'];
    $suprafata = $_POST['suprafata'];
    $chirie = $_POST['chirie'];
    $status = $_POST['status'];

    $sql = "INSERT INTO apartamente
    (adresa, numar_camere, suprafata, chirie, status)
    VALUES
    ('$adresa', '$numar_camere', '$suprafata', '$chirie', '$status')";

    mysqli_query($conn, $sql);

    header("Location: index.php");
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Adaugă apartament</title>
</head>
<body>

<h1>Adaugă apartament</h1>

<form method="POST">

    <label>Adresă:</label><br>
    <input type="text" name="adresa" required><br><br>

    <label>Număr camere:</label><br>
    <input type="number" name="numar_camere" required><br><br>

    <label>Suprafață:</label><br>
    <input type="number" step="0.01" name="suprafata"><br><br>

    <label>Chirie:</label><br>
    <input type="number" step="0.01" name="chirie" required><br><br>

    <label>Status:</label><br>

    <select name="status">
        <option value="liber">Liber</option>
        <option value="ocupat">Ocupat</option>
    </select>

    <br><br>

    <button type="submit" name="adauga">
        Salvează apartamentul
    </button>

</form>

</body>
</html>