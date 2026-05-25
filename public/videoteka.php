<?php
session_start();
include('../includes/db.php');
include('../includes/auth.php');
zahtijevaj_prijavu();

$id_usera = (int)$_SESSION['user_id'];

// Potvrdi odabir — prima JSON listu ID-eva iz localStorage, sprema u films_wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['potvrdi_odabir'])) {
    $ids = json_decode($_POST['film_ids'] ?? '[]', true);
    if (is_array($ids) && !empty($ids)) {
        $ins = mysqli_prepare($conn,
            "INSERT IGNORE INTO films_wishlist (id_user, id_film) VALUES (?, ?)");
        foreach ($ids as $id_filma) {
            $id_filma = (int)$id_filma;
            if ($id_filma > 0) {
                mysqli_stmt_bind_param($ins, "ii", $id_usera, $id_filma);
                mysqli_stmt_execute($ins);
            }
        }
    }
    header("Location: videoteka.php?potvrdjeno=1");
    exit();
}

// Uklanjanje jednog filma iz spremljenih (films_wishlist)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ukloni_spremljeni'])) {
    $id_filma = (int)$_POST['id_filma'];
    $stmt = mysqli_prepare($conn,
        "DELETE FROM films_wishlist WHERE id_user = ? AND id_film = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_usera, $id_filma);
    mysqli_stmt_execute($stmt);
    header("Location: videoteka.php");
    exit();
}

// Dohvati sve filmove (za JS prikaz košarice)
$svi_filmovi_result = mysqli_query($conn,
    "SELECT id, Naslov, Godina, zanr, Trajanje_min, Ocjena FROM films");
$svi_filmovi = mysqli_fetch_all($svi_filmovi_result, MYSQLI_ASSOC);
$film_map = [];
foreach ($svi_filmovi as $f) {
    $film_map[$f['id']] = $f;
}

// Dohvati spremljene filmove iz films_wishlist
$stmt = mysqli_prepare($conn,
    "SELECT f.*, zf.added_at
     FROM films f
     JOIN films_wishlist zf ON f.id = zf.id_film
     WHERE zf.id_user = ?
     ORDER BY zf.added_at DESC");
mysqli_stmt_bind_param($stmt, "i", $id_usera);
mysqli_stmt_execute($stmt);
$saved_result = mysqli_stmt_get_result($stmt);
$saved_films = mysqli_fetch_all($saved_result, MYSQLI_ASSOC);
$broj_saved = count($saved_films);

// Badge u navigaciji = broj u košarici (localStorage) — JS će ga postaviti
?>
<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CineVault — pregled osobne videoteke odabranih filmova.">
  <title>Moja videoteka — CineVault</title>
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
          <li><a href="index.php"     class="nav-link">Početna</a></li>
          <li><a href="grafikon.php" class="nav-link">Grafikoni</a></li>
          <li><a href="slike.php"    class="nav-link">Galerija</a></li>
          <li>
            <a href="videoteka.php" class="nav-link active" aria-current="page">
              Moja videoteka
              <span class="cart-badge" id="cart-badge" style="display:none">0</span>
            </a>
          </li>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li><a href="admin.php" class="nav-link">Admin</a></li>
          <?php endif; ?>
          <li>
            <a href="logout.php" class="nav-link">
              Odjava (<?= htmlspecialchars($_SESSION['username']) ?>)
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <!-- ── Odabrani filmovi (localStorage košarica) ───────────────────────── -->
    <section class="cart-section" aria-labelledby="cart-title">
      <div class="section-header">
        <h2 id="cart-title" class="section-title">
          Odabrani filmovi
          <span class="cart-count-badge" id="cart-counter">0</span>
        </h2>
      </div>

      <div id="cart-empty" class="cart-empty">
        <p class="cart-empty__icon">:(</p>
        <p class="cart-empty__text">Nema odabranih filmova.</p>
        <a href="index.php" class="btn-back">Odaberi filmove</a>
      </div>

      <div id="cart-table-wrapper" class="table-wrapper" style="display:none">
        <table class="films-table" aria-label="Odabrani filmovi">
          <thead>
            <tr>
              <th scope="col">Naslov</th>
              <th scope="col">Godina</th>
              <th scope="col">Žanr</th>
              <th scope="col">Trajanje</th>
              <th scope="col">Ocjena</th>
              <th scope="col">Ukloni</th>
            </tr>
          </thead>
          <tbody id="cart-tbody"></tbody>
        </table>
      </div>

      <div class="cart-actions" id="cart-actions" style="display:none">
        <button type="button" class="btn-clear" id="btn-ocisti">🗑 Očisti odabir</button>
        <form method="POST" id="form-potvrdi">
          <input type="hidden" name="potvrdi_odabir" value="1">
          <input type="hidden" name="film_ids" id="input-film-ids">
          <button type="submit" class="btn-confirm">✔ Potvrdi odabir</button>
        </form>
      </div>
    </section>

    <!-- ── Spremljeni filmovi (films_wishlist) ────────────────────────────── -->
    <section class="cart-section saved-section" aria-labelledby="saved-title">
      <div class="section-header">
        <h2 id="saved-title" class="section-title">
          Spremljeni filmovi
          <span class="cart-count-badge"><?= $broj_saved ?></span>
        </h2>
      </div>

      <?php if (empty($saved_films)): ?>
        <div class="cart-empty">
          <p class="cart-empty__text">Još nema spremljenih filmova. Potvrdite odabir da biste ih ovdje vidjeli.</p>
        </div>
      <?php else: ?>
        <div class="table-wrapper">
          <table class="films-table" aria-label="Spremljeni filmovi">
            <thead>
              <tr>
                <th scope="col">Naslov</th>
                <th scope="col">Godina</th>
                <th scope="col">Žanr</th>
                <th scope="col">Trajanje</th>
                <th scope="col">Ocjena</th>
                <th scope="col">Spremljeno</th>
                <th scope="col">Ukloni</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($saved_films as $film): ?>
                <tr>
                  <td><?= htmlspecialchars($film['Naslov']) ?></td>
                  <td><?= (int)$film['Godina'] ?></td>
                  <td><?= htmlspecialchars($film['zanr'] ?? '—') ?></td>
                  <td><?= $film['Trajanje_min'] ? (int)$film['Trajanje_min'] . ' min' : '—' ?></td>
                  <td><?= number_format((float)$film['Ocjena'], 1) ?></td>
                  <td><?= date('d.m.Y.', strtotime($film['added_at'])) ?></td>
                  <td>
                    <form method="POST"
                          onsubmit="return confirm('Ukloniti ovaj film iz spremljenih?')">
                      <input type="hidden" name="id_filma" value="<?= (int)$film['id'] ?>">
                      <button type="submit" name="ukloni_spremljeni" class="btn-remove"
                              title="Ukloni iz spremljenih">✕</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </main>

  <footer class="site-footer" role="contentinfo">
    <div class="footer-inner">
      <div class="footer-logo" aria-label="CineVault logo u podnožju">CINEVAULT</div>
      <p class="footer-copy">© 1999 CineVault</p>
    </div>
  </footer>

  <script>
  // Podaci o svim filmovima iz PHP-a (za prikaz u JS tablici košarice)
  const FILM_DATA = <?= json_encode($film_map, JSON_HEX_TAG) ?>;

  // Ako je upravo potvrđen odabir, očisti localStorage
  <?php if (isset($_GET['potvrdjeno'])): ?>
  localStorage.removeItem('cinevault_cart');
  <?php endif; ?>

  (function () {
    const cartTbody   = document.getElementById('cart-tbody');
    const cartEmpty   = document.getElementById('cart-empty');
    const cartWrapper = document.getElementById('cart-table-wrapper');
    const cartActions = document.getElementById('cart-actions');
    const cartCounter = document.getElementById('cart-counter');
    const badge       = document.getElementById('cart-badge');
    const btnOcisti   = document.getElementById('btn-ocisti');
    const formPotvrdi = document.getElementById('form-potvrdi');
    const inputIds    = document.getElementById('input-film-ids');

    function getCart() {
      return JSON.parse(localStorage.getItem('cinevault_cart') || '[]');
    }
    function saveCart(cart) {
      localStorage.setItem('cinevault_cart', JSON.stringify(cart));
    }
    function updateBadge(n) {
      if (!badge) return;
      badge.textContent = n;
      badge.style.display = n > 0 ? '' : 'none';
    }

    function renderCart() {
      const cart = getCart();
      cartCounter.textContent = cart.length;
      updateBadge(cart.length);
      cartTbody.innerHTML = '';

      if (cart.length === 0) {
        cartEmpty.style.display = '';
        cartWrapper.style.display = 'none';
        cartActions.style.display = 'none';
        return;
      }

      cartEmpty.style.display = 'none';
      cartWrapper.style.display = '';
      cartActions.style.display = '';

      cart.forEach(id => {
        const f = FILM_DATA[id];
        if (!f) return;
        const tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + f.Naslov + '</td>' +
          '<td>' + f.Godina + '</td>' +
          '<td>' + (f.zanr || '—') + '</td>' +
          '<td>' + (f.Trajanje_min ? f.Trajanje_min + ' min' : '—') + '</td>' +
          '<td>' + parseFloat(f.Ocjena).toFixed(1) + '</td>' +
          '<td><button class="btn-remove" data-id="' + id + '" title="Ukloni">✕</button></td>';
        cartTbody.appendChild(tr);
      });
    }

    // Ukloni film iz košarice
    cartTbody.addEventListener('click', function (e) {
      const btn = e.target.closest('.btn-remove[data-id]');
      if (!btn) return;
      const id = parseInt(btn.dataset.id);
      saveCart(getCart().filter(x => x !== id));
      renderCart();
    });

    // Očisti cijeli odabir
    btnOcisti.addEventListener('click', function () {
      if (confirm('Očistiti cijeli odabir?')) {
        saveCart([]);
        renderCart();
      }
    });

    // Potvrdi odabir — upiši IDs u hidden input pa submit
    formPotvrdi.addEventListener('submit', function (e) {
      const cart = getCart();
      if (cart.length === 0) {
        e.preventDefault();
        return;
      }
      inputIds.value = JSON.stringify(cart);
    });

    renderCart();
  })();
  </script>

</body>
</html>