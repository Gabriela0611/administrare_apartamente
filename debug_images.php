<?php
include "config/db.php";

echo "<pre>";

// 1. Check if apartamente table exists
$r = mysqli_query($conn, "SHOW TABLES LIKE 'apartamente'");
echo "Tabela apartamente exista: " . (mysqli_num_rows($r) > 0 ? "DA" : "NU") . "\n\n";

// 2. Check columns in apartamente
$r = mysqli_query($conn, "SHOW COLUMNS FROM apartamente");
echo "Coloane in apartamente:\n";
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

// 3. Show all apartments and their images value
echo "\nApartamente in baza de date:\n";
$r = mysqli_query($conn, "SELECT id, adresa, images FROM apartamente ORDER BY id ASC");
if ($r) {
    $count = 0;
    while ($row = mysqli_fetch_assoc($r)) {
        $count++;
        echo "  ID {$row['id']}: {$row['adresa']}\n";
        echo "    images = " . var_export($row['images'], true) . "\n";
    }
    if ($count === 0) echo "  (niciun apartament gasit)\n";
}

// 4. Check image files on disk
echo "\nFisiere in /images/:\n";
$files = glob(__DIR__ . '/images/*');
if ($files) {
    foreach ($files as $f) echo "  " . basename($f) . "\n";
} else {
    echo "  (niciun fisier gasit)\n";
}

echo "</pre>";
