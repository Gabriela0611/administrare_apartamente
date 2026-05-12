<?php
include "config/db.php";

$sql = "SELECT * FROM apartamente";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Administrare apartamente</title>

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: lightgray;
        }
    </style>
</head>
<body>

    <h1>Lista apartamentelor</h1>

    <a href="adauga_apartament.php">
    <button>Adaugă apartament</button>
</a>

    <table>

        <tr>
            <th>ID</th>
            <th>Adresă</th>
            <th>Număr camere</th>
            <th>Suprafață</th>
            <th>Chirie</th>
            <th>Status</th>
            <th>Acțiuni</th>
        </tr>

        <?php
        while($row = mysqli_fetch_assoc($result)) {
        ?>

        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['adresa']; ?></td>
            <td><?php echo $row['numar_camere']; ?></td>
            <td><?php echo $row['suprafata']; ?> m²</td>
            <td><?php echo $row['chirie']; ?> lei</td>
            <td><?php echo $row['status']; ?></td>
            
            <td>

    <a href="sterge_apartament.php?id=<?php echo $row['id']; ?>">
        <button>Șterge</button>
    </a>
        </td>
        </tr>

        <?php
        }
        ?>

    </table>

</body>
</html>