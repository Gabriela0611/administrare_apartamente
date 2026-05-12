<?php
include "config/db.php";

$id = $_GET['id'];

$sql = "DELETE FROM apartamente WHERE id = $id";

mysqli_query($conn, $sql);

header("Location: index.php");
?>SS