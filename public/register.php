<?php
session_start();
require_once('../includes/db.php');
$greska = "";
$uspjeh = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime  = trim($_POST['username']);
    $email = trim($_POST['email']);
    $loz  = $_POST['password'];

    if (empty($ime) || empty($email) || empty($loz)) {
        $greska = "Sva polja su obavezna.";
    } elseif (strlen($loz) < 6) {
        $greska = "Lozinka mora imati najmanje 6 znakova.";
    } else {
        $hash = password_hash($loz, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $ime, $email, $hash);

        if (mysqli_stmt_execute($stmt)) {
            $uspjeh = "Registracija uspješna! <a href='login.php'>Prijavi se</a>.";
        } else {
            $greska = "Korisničko ime ili email već postoje.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Registracija – CineVault</title>
    <link rel="stylesheet" href="assets/style/style_login.css">
</head>
<body>

<div class="register-container">

    <div class="register-logo">
        <h1>CINE<span>VAULT</span></h1>
    </div>

    <p class="register-title">Izrada korisničkog računa</p>

    <?php if ($greska): ?>
        <div class="register-error">
            <?= htmlspecialchars($greska) ?>
        </div>
    <?php endif; ?>

    <?php if ($uspjeh): ?>
        <div class="register-success">
            <?= $uspjeh ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="register-form">

        <div class="input-group">
            <label>Korisničko ime</label>

            <input
                type="text"
                name="username"
                placeholder="Unesite korisničko ime"
                required
            >
        </div>

        <div class="input-group">
            <label>Email adresa</label>

            <input
                type="email"
                name="email"
                placeholder="Unesite email adresu"
                required
            >
        </div>

        <div class="input-group">
            <label>Lozinka</label>

            <input
                type="password"
                name="password"
                placeholder="Unesite lozinku"
                required
            >
        </div>

        <button type="submit" class="register-btn">
            Registriraj se
        </button>

    </form>

    <div class="login-link">
        Već imaš račun?
        <a href="login.php">Prijavi se</a>
    </div>

</div>

</body>
</html>