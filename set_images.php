<?php
// One-time script: assigns photos to the 5 visible apartments.
// Visit once in the browser, then you can delete this file.
include "config/db.php";

$assignments = [
    11 => ['images/img6.jpg',  'images/img7.jpg',  'images/img8.jpg',  'images/img9.jpg',  'images/img10.jpg'], // Test - Calea Victoriei
    10 => ['images/img1.jpg',  'images/img2.jpg',  'images/img3.jpg',  'images/img4.jpg',  'images/img5.jpg'],  // Test - Strada Teilor
     9 => ['images/img11.jpg', 'images/img12.jpg', 'images/img13.jpg', 'images/img14.jpg', 'images/img15.jpg'], // Test - Aleea Parcului
     8 => ['images/img16.jpg', 'images/img17.jpg', 'images/img18.jpg'],                                         // Test - Bulevardul Unirii
     7 => ['images/img19.jpg', 'images/img20.jpg', 'images/img21.jpg'],                                         // Test - Strada Libertatii
     3 => ['images/img22.jpg', 'images/img23.jpg', 'images/img24.jpg'],                                         // Bulevardul Central
];

$stmt = mysqli_prepare($conn, "UPDATE apartamente SET images = ? WHERE id = ?");

echo "<pre>";
foreach ($assignments as $id => $imgs) {
    $json = json_encode($imgs);
    mysqli_stmt_bind_param($stmt, "si", $json, $id);
    mysqli_stmt_execute($stmt);

    $r = mysqli_query($conn, "SELECT adresa FROM apartamente WHERE id = $id");
    $row = mysqli_fetch_assoc($r);
    $adresa = $row['adresa'] ?? "ID $id";

    if (mysqli_stmt_affected_rows($stmt) >= 0) {
        echo "✅ ID $id — $adresa\n   " . implode(', ', array_map('basename', $imgs)) . "\n\n";
    } else {
        echo "❌ ID $id — failed\n\n";
    }
}
echo "Done!";
echo "</pre>";
