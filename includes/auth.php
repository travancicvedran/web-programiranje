<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function zahtijevaj_prijavu() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function zahtijevaj_admina() {
    zahtijevaj_prijavu();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
}
?>