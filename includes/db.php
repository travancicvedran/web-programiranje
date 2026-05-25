<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "cinevault";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Greška pri spajanju na bazu: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>