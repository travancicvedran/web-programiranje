<?php
session_start();
include("../includes/db.php");

// Min/max Godina iz baze (za slider)
$Godina_range = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(Godina) as min_g, MAX(Godina) as max_g FROM films"));
$min_Godina = $Godina_range['min_g'] ?? 1900;
$max_Godina = $Godina_range['max_g'] ?? date('Y');
$selected_min = (int)($_GET['Godina_od'] ?? $min_Godina);
$selected_max = (int)($_GET['Godina_do'] ?? $max_Godina);

// Dohvati SVE filmove (filtriranje je na klijentskoj strani)
$films_result = mysqli_query($conn, "SELECT * FROM films ORDER BY Naslov ASC");
$films = mysqli_fetch_all($films_result, MYSQLI_ASSOC);

// Izvuci jedinstvene žanrove i države iz svih filmova (razbijanje po separatorima)
$svi_zanrovi = [];
$sve_zemlje  = [];
foreach ($films as $f) {
    if (!empty($f['Zanr'])) {
        foreach (preg_split('/[,\/]+/', $f['Zanr']) as $z) {
            $z = trim($z);
            if ($z !== '') $svi_zanrovi[$z] = true;
        }
    }
    if (!empty($f['Zemlja_porijekla'])) {
        foreach (preg_split('/[\/,]+/', $f['Zemlja_porijekla']) as $z) {
            $z = trim($z);
            if ($z !== '') $sve_zemlje[$z] = true;
        }
    }
}
ksort($svi_zanrovi);
ksort($sve_zemlje);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_film']) && isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    $id_filma = (int)$_POST['id_filma'];
    $id_usera = (int)$_SESSION['user_id'];

    $Ocjena_stmt = mysqli_prepare($conn, "SELECT Ocjena FROM films WHERE id = ?");
    mysqli_stmt_bind_param($Ocjena_stmt, "i", $id_filma);
    mysqli_stmt_execute($Ocjena_stmt);
    $film_data = mysqli_fetch_assoc(mysqli_stmt_get_result($Ocjena_stmt));

    $upozorenje = ($film_data && $film_data['Ocjena'] < 5.0)
        ? "Ovaj film ima nisku ocjenu – jeste li sigurni da ga želite dodati?"
        : "";

    $ins = mysqli_prepare($conn, "INSERT IGNORE INTO films_wishlist (id_user, id_film) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, "ii", $id_usera, $id_filma);
    mysqli_stmt_execute($ins);
    $affected = mysqli_stmt_affected_rows($ins);

    // Dohvati novi badge count
    $badge_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM films_wishlist WHERE id_user = ?");
    mysqli_stmt_bind_param($badge_stmt, "i", $id_usera);
    mysqli_stmt_execute($badge_stmt);
    $badge_count = mysqli_fetch_assoc(mysqli_stmt_get_result($badge_stmt))['c'];

    if ($affected > 0) {
        echo json_encode([
            'status'      => $upozorenje ? 'warning' : 'success',
            'message'     => $upozorenje ?: 'Film dodan u vašu videoteku!',
            'badge_count' => $badge_count,
        ]);
    } else {
        echo json_encode([
            'status'      => 'info',
            'message'     => 'Film je već u vašoj videoteci.',
            'badge_count' => $badge_count,
        ]);
    }
    exit();
}

// Badge count za navigaciju
$badge_count = 0;
if (isset($_SESSION['user_id'])) {
    $badge_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM films_wishlist WHERE id_user = ?");
    mysqli_stmt_bind_param($badge_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($badge_stmt);
    $badge_count = mysqli_fetch_assoc(mysqli_stmt_get_result($badge_stmt))['c'];
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CineVault - baza podataka filmova s Ocjenama, žanrovima i detaljima o filmsma iz cijelog svijeta.">
  <meta name="keywords" content="films, baza filmova, ocjene filmova, žanrovi, kino">
  <meta name="author" content="CineVault">
  <meta property="og:title" content="CineVault - Filmska Baza Podataka">
  <title>CineVault — Filmska Baza Podataka</title>
  <link rel="stylesheet" href="assets/style/style_main.css">
</head>
<body>

  <!-- Zaglavlje -->
  <header class="site-header" role="banner">
    <div class="header-inner">
      <span class="logo-text">CINE<em>VAULT</em></span>

      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true">
      <label for="nav-toggle" class="nav-burger" aria-label="Otvori navigacijski izbornik" role="button">
        <span></span><span></span><span></span>
      </label>

      <nav class="main-nav" aria-label="Primarna navigacija">
        <ul role="list">
          <li><a href="index.php"     class="nav-link active" aria-current="page">Početna</a></li>
          <li><a href="grafikon.php" class="nav-link">Grafikoni</a></li>
          <li><a href="slike.php"    class="nav-link">Galerija</a></li>
          <li>
            <a href="videoteka.php" class="nav-link" id="nav-videoteka">
              Moja videoteka
              <span class="cart-badge" id="cart-badge" style="display:none">0</span>
            </a>
          </li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <li><a href="admin.php" class="nav-link">Admin</a></li>
            <?php endif; ?>
            <li>
              <a href="logout.php" class="nav-link">
                Odjava (<?= htmlspecialchars($_SESSION['username']) ?>)
              </a>
            </li>
          <?php else: ?>
            <li><a href="login.php"    class="nav-link">Prijava</a></li>
            <li><a href="register.php" class="nav-link">Registracija</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="hero-content">
      <p class="hero-eyebrow">Dobrodošli u</p>
      <h1 id="hero-title" class="hero-title">CINEVAULT</h1>
    </div>
  </section>

  <main id="main-content">
    <section id="films" class="films-section" aria-labelledby="films-title">
      <div class="section-header">
        <h2 id="films-title" class="section-title">Popis Filmova</h2>
      </div>

      <div id="status-message" class="status-message" style="display:none"></div>

      <div class="films-layout">
        <div class="films-layout vertical">

          <!-- Filter panel — real-time, bez submit tipke -->
          <div class="filters-panel" id="filter-form">

            <div class="filter-group">
              <label for="filter-genre">Žanr</label>
              <select id="filter-genre">
                <option value="">— Svi —</option>
                <?php foreach (array_keys($svi_zanrovi) as $z): ?>
                  <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="filter-group">
              <label for="filter-country">Država</label>
              <select id="filter-country">
                <option value="">— Sve —</option>
                <?php foreach (array_keys($sve_zemlje) as $z): ?>
                  <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="filter-group">
              <label for="filter-rating">Min. Ocjena (0–10)</label>
              <input type="number" id="filter-rating"
                     min="0" max="10" step="0.1" placeholder="npr. 7.5">
            </div>

            <div class="filter-group">
              <label for="filter-sort">Sortiraj po</label>
              <select id="filter-sort">
                <option value="Naslov">Naslovu</option>
                <option value="Godina">Godini</option>
                <option value="Ocjena">Ocjeni</option>
              </select>
            </div>

            <div class="filter-group filter-group--years">
              <label>Raspon Godina</label>
              <div class="year-range">
                <span id="year-min-label"><?= $min_Godina ?></span>
                <input type="range" id="filter-year-min"
                       min="<?= $min_Godina ?>" max="<?= $max_Godina ?>"
                       value="<?= $min_Godina ?>">
                <input type="range" id="filter-year-max"
                       min="<?= $min_Godina ?>" max="<?= $max_Godina ?>"
                       value="<?= $max_Godina ?>">
                <span id="year-max-label"><?= $max_Godina ?></span>
              </div>
            </div>

            <div class="filter-group filter-group--actions">
              <a href="index.php" class="btn-reset" id="btn-reset-filters">Resetiraj filtere</a>
            </div>

          </div>

          <!-- Tablica filmova -->
          <div class="table-wrapper">
            <table class="films-table" aria-label="Tablica filmova s osnovnim podacima">
              <thead>
                <tr>
                  <th scope="col">Naslov</th>
                  <th scope="col">Godina</th>
                  <th scope="col">Žanr</th>
                  <th scope="col">Trajanje</th>
                  <th scope="col">Država</th>
                  <th scope="col">Ocjena</th>
                  <th scope="col">Dodaj</th>
                </tr>
              </thead>
              <tbody id="films-tbody">
                <?php foreach ($films as $film): ?>
                  <tr
                    data-naslov="<?= htmlspecialchars(mb_strtolower($film['Naslov'])) ?>"
                    data-godina="<?= (int)$film['Godina'] ?>"
                    data-ocjena="<?= (float)$film['Ocjena'] ?>"
                    data-zanr="<?= htmlspecialchars(mb_strtolower($film['Zanr'] ?? '')) ?>"
                    data-zemlja="<?= htmlspecialchars(mb_strtolower($film['Zemlja_porijekla'] ?? '')) ?>"
                  >
                    <td><?= htmlspecialchars($film['Naslov']) ?></td>
                    <td><?= (int)$film['Godina'] ?></td>
                    <td><?= htmlspecialchars($film['Zanr'] ?? '—') ?></td>
                    <td><?= $film['Trajanje_min'] ? (int)$film['Trajanje_min'] . ' min' : '—' ?></td>
                    <td><?= htmlspecialchars($film['Zemlja_porijekla'] ?? '—') ?></td>
                    <td><?= number_format((float)$film['Ocjena'], 1) ?></td>
                    <td>
                      <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="button" class="btn-add-cart"
                          data-id="<?= (int)$film['id'] ?>"
                          title="Dodaj u videoteku">+</button>
                      <?php else: ?>
                        <a href="login.php" class="btn-add-cart btn-add-cart--locked"
                           title="Prijavi se za dodavanje">🔒</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <p id="results-count" class="results-count"></p>

        </div>

        <aside>
          <div class="side-images">
            <img src="https://unsplash.it/300/200?random=1" alt="Slika 1">
            <img src="https://unsplash.it/300/200?random=2" alt="Slika 2">
          </div>
        </aside>
      </div>
    </section>
  </main>

  <footer class="site-footer" role="contentinfo">
    <div class="footer-inner">
      <div class="footer-logo" aria-label="CineVault logo u podnožju">CINEVAULT</div>
      <p class="footer-copy">© 1999 CineVault</p>
    </div>
  </footer>

  <script>
  (function () {
    // ── Elementi ──────────────────────────────────────────────────────────────
    const tbody      = document.getElementById('films-tbody');
    const allRows    = Array.from(tbody.querySelectorAll('tr'));
    const countEl    = document.getElementById('results-count');
    const badge      = document.getElementById('cart-badge');
    const statusBox  = document.getElementById('status-message');

    const selGenre   = document.getElementById('filter-genre');
    const selCountry = document.getElementById('filter-country');
    const inpRating  = document.getElementById('filter-rating');
    const selSort    = document.getElementById('filter-sort');
    const sliderMin  = document.getElementById('filter-year-min');
    const sliderMax  = document.getElementById('filter-year-max');
    const labelMin   = document.getElementById('year-min-label');
    const labelMax   = document.getElementById('year-max-label');

    // ── Helpers ───────────────────────────────────────────────────────────────

    // Razbija "Adventure, Comedy" ili "USA/UK" po separatorima
    function splitValues(str) {
      return str.split(/[,\/]+/).map(s => s.trim().toLowerCase()).filter(Boolean);
    }

    function updateCount(n) {
      const label = n === 1 ? 'film' : (n < 5 ? 'filma' : 'filmova');
      countEl.textContent = n + ' ' + label;
    }

    function showStatus(type, message) {
      statusBox.className = 'status-message status-message--' + type;
      statusBox.textContent = message;
      statusBox.style.display = '';
      clearTimeout(statusBox._timer);
      statusBox._timer = setTimeout(() => { statusBox.style.display = 'none'; }, 4000);
    }

    // ── Filtriranje ───────────────────────────────────────────────────────────
    function applyFilters() {
      const genre   = selGenre.value.trim().toLowerCase();
      const country = selCountry.value.trim().toLowerCase();
      const minRat  = parseFloat(inpRating.value) || 0;
      const minYear = parseInt(sliderMin.value);
      const maxYear = parseInt(sliderMax.value);
      const sort    = selSort.value;

      let visible = [];

      allRows.forEach(row => {
        const godina = parseInt(row.dataset.godina);
        const ocjena = parseFloat(row.dataset.ocjena);
        const zanrovi = splitValues(row.dataset.zanr);
        const zemlje  = splitValues(row.dataset.zemlja);

        const matchGenre   = !genre   || zanrovi.includes(genre);
        const matchCountry = !country || zemlje.includes(country);
        const matchRating  = ocjena >= minRat;
        const matchYear    = godina >= minYear && godina <= maxYear;

        if (matchGenre && matchCountry && matchRating && matchYear) {
          row.style.display = '';
          visible.push(row);
        } else {
          row.style.display = 'none';
        }
      });

      // Sortiranje vidljivih redova
      visible.sort((a, b) => {
        if (sort === 'Godina') return parseInt(a.dataset.godina) - parseInt(b.dataset.godina);
        if (sort === 'Ocjena') return parseFloat(b.dataset.ocjena) - parseFloat(a.dataset.ocjena);
        return a.dataset.naslov.localeCompare(b.dataset.naslov, 'hr');
      });
      visible.forEach(row => tbody.appendChild(row));

      updateCount(visible.length);
    }

    // ── Year slider ───────────────────────────────────────────────────────────
    sliderMin.addEventListener('input', () => {
      if (parseInt(sliderMin.value) > parseInt(sliderMax.value))
        sliderMin.value = sliderMax.value;
      labelMin.textContent = sliderMin.value;
      applyFilters();
    });
    sliderMax.addEventListener('input', () => {
      if (parseInt(sliderMax.value) < parseInt(sliderMin.value))
        sliderMax.value = sliderMin.value;
      labelMax.textContent = sliderMax.value;
      applyFilters();
    });

    // ── Ostali filteri ────────────────────────────────────────────────────────
    [selGenre, selCountry, selSort].forEach(el =>
      el.addEventListener('change', applyFilters)
    );
    inpRating.addEventListener('input', applyFilters);

    // ── localStorage košarica ─────────────────────────────────────────────────
    function getCart() {
      return JSON.parse(localStorage.getItem('cinevault_cart') || '[]');
    }
    function saveCart(cart) {
      localStorage.setItem('cinevault_cart', JSON.stringify(cart));
    }
    function updateBadge() {
      const n = getCart().length;
      if (badge) {
        badge.textContent = n;
        badge.style.display = n > 0 ? '' : 'none';
      }
    }

    tbody.addEventListener('click', function (e) {
      const btn = e.target.closest('.btn-add-cart[data-id]');
      if (!btn) return;

      const id = parseInt(btn.dataset.id);
      const ocjena = parseFloat(btn.closest('tr').dataset.ocjena);
      const cart = getCart();

      if (cart.includes(id)) {
        showStatus('info', 'Film je već u odabiru.');
        return;
      }

      if (ocjena < 5.0) {
        if (!confirm('Ovaj film ima nisku ocjenu (' + ocjena.toFixed(1) + ') – jeste li sigurni da ga želite dodati?')) {
          return;
        }
      }

      cart.push(id);
      saveCart(cart);
      showStatus(ocjena < 5.0 ? 'warning' : 'success', 'Film dodan u odabir!');
      updateBadge();
    });

    // Inicijalno postavi badge
    updateBadge();

    // ── Inicijalno ────────────────────────────────────────────────────────────
    applyFilters();
  })();
  </script>

</body>
</html>