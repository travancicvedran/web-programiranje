<?php
session_start();
header('Content-Type: application/json');
include("../includes/db.php");

// Samo prijavljeni korisnici mogu uploadati
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Niste prijavljeni.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slika'])) {
    $file     = $_FILES['slika'];
    $dozvoljeni_tipovi = ['image/jpeg', 'image/png'];
    $max_velicina      = 5 * 1024 * 1024; // 5MB

    // Validacija tipa
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $dozvoljeni_tipovi)) {
        echo json_encode(['status' => 'error', 'message' => 'Samo JPEG i PNG formati su dozvoljeni.']);
        exit();
    }

    // Validacija veličine
    if ($file['size'] > $max_velicina) {
        echo json_encode(['status' => 'error', 'message' => 'Slika ne smije biti veća od 5MB.']);
        exit();
    }

    // Spremi datoteku s jedinstvenim imenom
    $ext           = ($mime === 'image/jpeg') ? 'jpg' : 'png';
    $naziv         = uniqid('slika_', true) . '.' . $ext;
    $putanja_disk  = 'slike/' . $naziv;
    $putanja_web   = 'slike/' . $naziv;

    if (move_uploaded_file($file['tmp_name'], $putanja_disk)) {
        $opis = htmlspecialchars($_POST['opis'] ?? '');

        $stmt = mysqli_prepare($conn,
            "INSERT INTO slike (naziv_datoteke, opis, putanja, izvor) VALUES (?, ?, ?, 'lokalno')"
        );
        mysqli_stmt_bind_param($stmt, 'sss', $naziv, $opis, $putanja_web);
        mysqli_stmt_execute($stmt);

        echo json_encode(['status' => 'success', 'message' => 'Slika uspješno uploadana!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Greška pri spremanju slike.']);
    }
    exit();
}