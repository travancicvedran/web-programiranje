<?php
session_start();
include("../includes/db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Morate biti prijavljeni za ocjenjivanje.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_korisnik = (int)$_SESSION['user_id'];
    $id_slika    = (int)($_POST['id_slika'] ?? 0);
    $ocjena      = (int)($_POST['ocjena']   ?? 0);

    if ($id_slika <= 0 || $ocjena < 1 || $ocjena > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Neispravni podaci.']);
        exit();
    }

    // INSERT ili UPDATE ako već postoji ocjena (prepared statement)
    $stmt = mysqli_prepare($conn,
        "INSERT INTO ocjene (id_korisnik, id_slika, ocjena)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE ocjena = VALUES(ocjena), vrijeme_ocjene = CURRENT_TIMESTAMP"
    );
    mysqli_stmt_bind_param($stmt, 'iii', $id_korisnik, $id_slika, $ocjena);
    mysqli_stmt_execute($stmt);

    // Dohvati novu prosječnu ocjenu
    $avg_stmt = mysqli_prepare($conn,
        "SELECT ROUND(AVG(ocjena), 1) as prosjek, COUNT(*) as broj FROM ocjene WHERE id_slika = ?"
    );
    mysqli_stmt_bind_param($avg_stmt, 'i', $id_slika);
    mysqli_stmt_execute($avg_stmt);
    $avg_data = mysqli_fetch_assoc(mysqli_stmt_get_result($avg_stmt));

    echo json_encode([
        'status'  => 'success',
        'prosjek' => $avg_data['prosjek'],
        'broj'    => $avg_data['broj'],
    ]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Nevažeći zahtjev.']);