<?php
session_start();
require_once('../includes/db.php');
$greska = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = trim($_POST['username']);
    $loz = $_POST['password'];

    $stmt = mysqli_prepare($conn,
        "SELECT id, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $ime);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($loz, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $ime;
        $_SESSION['role']        = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $greska = "Pogrešno korisničko ime ili lozinka.";
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Prijava – CineVault</title>
    <link rel="stylesheet" href="assets/style/style_login.css">
</head>
<body>

<div class="login-container">

    <div class="login-logo">
        <h1>CINE<span>VAULT</span></h1>
    </div>

    <p class="login-title">Prijava usera</p>

    <?php if ($greska): ?>
        <div class="login-error">
            <?= htmlspecialchars($greska) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="login-form">

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
            <label>Lozinka</label>

            <input
                type="password"
                name="password"
                placeholder="Unesite lozinku"
                required
            >
        </div>

        <button type="submit" class="login-btn">
            Prijavi se
        </button>

    </form>

    <div class="register-link">
        Nemaš račun?
        <a href="register.php">Registriraj se</a>
    </div>

</div>

</body>
</html>