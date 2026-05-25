<?php
session_start();
include('../includes/db.php');
include('../includes/auth.php');
zahtijevaj_admina();

$greska = "";
$uspjeh = "";

// Brisanje
if (isset($_POST['obrisi']) && isset($_POST['id_filma'])) {
    $id = (int)$_POST['id_filma'];
    $stmt = mysqli_prepare($conn, "DELETE FROM films WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $uspjeh = "Film obrisan.";
}

// Dodavanje novog filma
if (isset($_POST['dodaj'])) {
    $Naslov    = trim($_POST['Naslov']);
    $Zanr     = trim($_POST['Zanr']);
    $Godina   = (int)$_POST['Godina'];
    $Zemlja_porijekla   = trim($_POST['Zemlja_porijekla']);
    $Trajanje_min = (int)$_POST['Trajanje_min'];
    $ocjena   = (float)$_POST['Ocjena'];

    // Validacija
    if (empty($Naslov)) {
        $greska = "Naslov je obavezan.";
    } elseif ($Godina < 1888 || $Godina > date('Y')) {
        $greska = "Godina nije ispravna.";
    } elseif ($Trajanje_min < 1 || $Trajanje_min > 600) {
        $greska = "Trajanje mora biti između 1 i 600 minuta.";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO films (Naslov, Zanr, Godina, Zemlja_porijekla, Trajanje_min, Ocjena)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssissd",
            $Naslov, $Zanr, $Godina, $Zemlja_porijekla, $Trajanje_min, $ocjena);
        mysqli_stmt_execute($stmt)
            ? $uspjeh = "Film dodan!"
            : $greska = "Greška pri dodavanju.";
    }
}

// Dohvati sve filmove
$films = mysqli_query($conn, "SELECT * FROM films ORDER BY Naslov");
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Admin – CineVault</title>
    <link rel="stylesheet" href="assets/style/style_main.css">
</head>
<body>

<div class="page-shell">

    <header class="page-header">
        <div>
            <h1 >Admin Dashboard</h1>
            <p class="hero-subtitle">
                Upravljanje CineVault bazom filmova
            </p>
        </div>

        <a href="index.php" class="btn-back">
            ← Povratak na početnu
        </a>
    </header>

    <?php if ($greska): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($greska) ?>
        </div>
    <?php endif; ?>

    <?php if ($uspjeh): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($uspjeh) ?>
        </div>
    <?php endif; ?>

    <!-- DODAVANJE FILMA -->
    <section class="admin-card">

        <h2 class="section-title">
            Dodaj novi film
        </h2>

        <form method="POST" class="admin-form">

            <input
                type="text"
                name="Naslov"
                placeholder="Naslov"
                required
            >

            <input
                type="text"
                name="Zanr"
                placeholder="Žanr"
            >

            <input
                type="number"
                name="Godina"
                placeholder="Godina"
                required
            >

            <input
                type="text"
                name="Zemlja_porijekla"
                placeholder="Država"
            >

            <input
                type="number"
                name="Trajanje_min"
                placeholder="Trajanje (min)"
            >

            <input
                type="number"
                name="Ocjena"
                placeholder="Ocjena"
                step="0.1"
                min="0"
                max="10"
            >

            <button
                type="submit"
                name="dodaj"
                class="btn-primary"
            >
                Dodaj film
            </button>

        </form>

    </section>

    <!-- TABLICA -->
    <section class="admin-card">

        <h2 class="section-title">
            Svi filmovi
        </h2>

        <div class="table-wrapper">

            <table
                class="films-table"
                aria-label="Tablica svih filmova"
            >

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naslov</th>
                        <th>Godina</th>
                        <th>Žanr</th>
                        <th>Trajanje</th>
                        <th>Država</th>
                        <th>Ocjena</th>
                        <th>Akcija</th>
                    </tr>
                </thead>

                <tbody>

                <?php while($film = mysqli_fetch_assoc($films)): ?>

                    <tr>

                        <td>
                            <?= $film['id'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($film['Naslov']) ?>
                        </td>

                        <td>
                            <?= (int)$film['Godina'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($film['Zanr'] ?? '—') ?>
                        </td>

                        <td>
                            <?= (int)$film['Trajanje_min'] ?> min
                        </td>

                        <td>
                            <?= htmlspecialchars($film['Zemlja_porijekla'] ?? '—') ?>
                        </td>

                        <td>
                            <?= number_format((float)$film['Ocjena'], 1) ?>
                        </td>

                        <td>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="id_filma"
                                    value="<?= $film['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="obrisi"
                                    class="btn-delete"
                                    onclick="return confirm('Obrisati film?')"
                                >
                                    Obriši
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </section>

</div>

</body>
</html>