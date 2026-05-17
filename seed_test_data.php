<?php
include "config/db.php";

function get_one($conn, $sql, $types = '', ...$params) {
    $stmt = mysqli_prepare($conn, $sql);

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result);
}

function ensure_apartament($conn, $adresa, $camere, $suprafata, $chirie, $status) {
    $row = get_one($conn, "SELECT id FROM apartamente WHERE adresa = ? LIMIT 1", "s", $adresa);

    if ($row) {
        return (int)$row['id'];
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO apartamente (adresa, numar_camere, suprafata, chirie, status) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sidds", $adresa, $camere, $suprafata, $chirie, $status);
    mysqli_stmt_execute($stmt);

    return mysqli_insert_id($conn);
}

function ensure_chirias($conn, $data) {
    $row = get_one($conn, "SELECT id FROM chiriasi WHERE email = ? LIMIT 1", "s", $data['email']);

    if ($row) {
        return (int)$row['id'];
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO chiriasi (
        nume, prenume, telefon, email, cnp, serie_ci, adresa, data_mutarii, apartament_id,
        numar_contract, data_inceput, data_sfarsit, chirie_lunara, garantie,
        document_contract, document_copie_ci, document_proces_verbal, document_garantie
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1, 1)");

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssisssdd",
        $data['nume'],
        $data['prenume'],
        $data['telefon'],
        $data['email'],
        $data['cnp'],
        $data['serie_ci'],
        $data['adresa'],
        $data['data_mutarii'],
        $data['apartament_id'],
        $data['numar_contract'],
        $data['data_inceput'],
        $data['data_sfarsit'],
        $data['chirie_lunara'],
        $data['garantie']
    );
    mysqli_stmt_execute($stmt);

    mysqli_query($conn, "UPDATE apartamente SET status = 'ocupat' WHERE id = " . (int)$data['apartament_id']);

    return mysqli_insert_id($conn);
}

function ensure_user($conn, $email, $parola, $role, $chirias_id = null) {
    $row = get_one($conn, "SELECT id FROM users WHERE email = ? LIMIT 1", "s", $email);

    if ($row) {
        return;
    }

    $hash = password_hash($parola, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (email, parola, role, chirias_id) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $email, $hash, $role, $chirias_id);
    mysqli_stmt_execute($stmt);
}

function ensure_factura($conn, $apartament_id, $tip, $suma, $scadenta, $status, $data_platii) {
    $row = get_one(
        $conn,
        "SELECT id FROM facturi WHERE apartament_id = ? AND tip_factura = ? AND scadenta = ? AND suma = ? LIMIT 1",
        "issd",
        $apartament_id,
        $tip,
        $scadenta,
        $suma
    );

    if ($row) {
        return;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO facturi (apartament_id, tip_factura, suma, scadenta, status, data_platii) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isdsss", $apartament_id, $tip, $suma, $scadenta, $status, $data_platii);
    mysqli_stmt_execute($stmt);
}

function ensure_mentenanta($conn, $apartament_id, $chirias_id, $problema, $descriere, $prioritate, $status, $data_raportare) {
    $row = get_one($conn, "SELECT id FROM cereri_mentenanta WHERE problema = ? AND apartament_id = ? LIMIT 1", "si", $problema, $apartament_id);

    if ($row) {
        return;
    }

    $fotografie = null;
    $stmt = mysqli_prepare($conn, "INSERT INTO cereri_mentenanta (apartament_id, chirias_id, problema, fotografie, descriere, prioritate, status, data_raportare) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iissssss", $apartament_id, $chirias_id, $problema, $fotografie, $descriere, $prioritate, $status, $data_raportare);
    mysqli_stmt_execute($stmt);
}

$apartamente = [];
$apartamente[] = ensure_apartament($conn, 'Test - Strada Libertatii 10, Ap. 1', 2, 54.50, 1800, 'ocupat');
$apartamente[] = ensure_apartament($conn, 'Test - Bulevardul Unirii 22, Ap. 8', 3, 72.00, 2500, 'ocupat');
$apartamente[] = ensure_apartament($conn, 'Test - Aleea Parcului 5, Ap. 3', 1, 38.20, 1400, 'ocupat');
$apartamente[] = ensure_apartament($conn, 'Test - Strada Teilor 14, Ap. 12', 2, 60.00, 2000, 'liber');
$apartamente[] = ensure_apartament($conn, 'Test - Calea Victoriei 90, Ap. 6', 4, 95.00, 3600, 'liber');

$chiriasi = [];
$chiriasi[] = ensure_chirias($conn, [
    'nume' => 'Popescu',
    'prenume' => 'Ana',
    'telefon' => '0711111111',
    'email' => 'test.chirias1@example.com',
    'cnp' => '1960101123456',
    'serie_ci' => 'AB123456',
    'adresa' => 'Test - Bucuresti, Sector 1',
    'data_mutarii' => '2026-01-15',
    'apartament_id' => $apartamente[0],
    'numar_contract' => 'CT-TEST-001',
    'data_inceput' => '2026-01-15',
    'data_sfarsit' => '2027-01-14',
    'chirie_lunara' => 1800,
    'garantie' => 1800
]);

$chiriasi[] = ensure_chirias($conn, [
    'nume' => 'Ionescu',
    'prenume' => 'Mihai',
    'telefon' => '0722222222',
    'email' => 'test.chirias2@example.com',
    'cnp' => '1900202123456',
    'serie_ci' => 'CD654321',
    'adresa' => 'Test - Bucuresti, Sector 2',
    'data_mutarii' => '2026-02-01',
    'apartament_id' => $apartamente[1],
    'numar_contract' => 'CT-TEST-002',
    'data_inceput' => '2026-02-01',
    'data_sfarsit' => '2027-01-31',
    'chirie_lunara' => 2500,
    'garantie' => 2500
]);

$chiriasi[] = ensure_chirias($conn, [
    'nume' => 'Dumitrescu',
    'prenume' => 'Elena',
    'telefon' => '0733333333',
    'email' => 'test.chirias3@example.com',
    'cnp' => '2990303123456',
    'serie_ci' => 'EF987654',
    'adresa' => 'Test - Bucuresti, Sector 3',
    'data_mutarii' => '2026-03-10',
    'apartament_id' => $apartamente[2],
    'numar_contract' => 'CT-TEST-003',
    'data_inceput' => '2026-03-10',
    'data_sfarsit' => '2027-03-09',
    'chirie_lunara' => 1400,
    'garantie' => 1400
]);

ensure_user($conn, 'proprietar@test.com', 'proprietar123', 'proprietar');
ensure_user($conn, 'chirias@test.com', 'chirias123', 'chirias', $chiriasi[0]);

ensure_factura($conn, $apartamente[0], 'chirie', 1800, '2026-05-05', 'platita', '2026-05-03');
ensure_factura($conn, $apartamente[0], 'apa', 120, '2026-05-10', 'platita', '2026-05-09');
ensure_factura($conn, $apartamente[1], 'chirie', 2500, '2026-05-05', 'platita', '2026-05-04');
ensure_factura($conn, $apartamente[1], 'gaz', 210, '2026-05-12', 'neplatita', null);
ensure_factura($conn, $apartamente[2], 'curent', 180, '2026-05-15', 'neplatita', null);
ensure_factura($conn, $apartamente[2], 'internet', 80, '2026-05-20', 'neplatita', null);
ensure_factura($conn, $apartamente[3], 'intretinere', 350, '2026-04-25', 'neplatita', null);
ensure_factura($conn, $apartamente[4], 'apa', 95, '2026-05-18', 'neplatita', null);

ensure_mentenanta($conn, $apartamente[0], $chiriasi[0], 'Test - Robinet stricat', 'Robinetul de la baie curge constant.', 'ridicata', 'deschisa', '2026-05-01');
ensure_mentenanta($conn, $apartamente[1], $chiriasi[1], 'Test - Centrala defecta', 'Centrala nu porneste si nu incalzeste apa.', 'urgenta', 'in_lucru', '2026-05-06');
ensure_mentenanta($conn, $apartamente[2], $chiriasi[2], 'Test - Bec ars pe hol', 'Becul de pe holul de intrare nu mai functioneaza.', 'scazuta', 'rezolvata', '2026-05-08');
ensure_mentenanta($conn, $apartamente[0], $chiriasi[0], 'Test - Priza slabita', 'Priza din dormitor se misca in perete.', 'medie', 'deschisa', '2026-05-12');

echo "Datele de test au fost adaugate.\n";
