<?php
session_start();
include("../includes/db.php");

// Dohvati sve slike iz baze zajedno s prosječnom ocjenom
$slike_stmt = mysqli_query($conn,
    "SELECT s.id, s.naziv_datoteke, s.opis, s.putanja, s.izvor,
            ROUND(AVG(o.ocjena), 1) AS prosjek_ocjena,
            COUNT(o.id) AS broj_ocjena
     FROM slike s
     LEFT JOIN ocjene o ON o.id_slika = s.id
     GROUP BY s.id
     ORDER BY s.id ASC"
);
$sve_slike = mysqli_fetch_all($slike_stmt, MYSQLI_ASSOC);

// Dohvati korisnikove ocjene (ako je prijavljen)
$moje_ocjene = [];
if (isset($_SESSION['user_id'])) {
    $moj_stmt = mysqli_prepare($conn,
        "SELECT id_slika, ocjena FROM ocjene WHERE id_korisnik = ?"
    );
    mysqli_stmt_bind_param($moj_stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($moj_stmt);
    $moj_result = mysqli_stmt_get_result($moj_stmt);
    while ($row = mysqli_fetch_assoc($moj_result)) {
        $moje_ocjene[$row['id_slika']] = (int)$row['ocjena'];
    }
}

// Scan lokalnog /slike/ foldera i dodaj nove u bazu (auto-import)
$folder = '../slike/';
if (is_dir($folder)) {
    $fajlovi = glob($folder . '*.{jpg,jpeg,png}', GLOB_BRACE);
    foreach ($fajlovi as $fajl) {
        $naziv   = basename($fajl);
        $putanja = 'slike/' . $naziv;
        // Dodaj samo ako već nije u bazi
        $check = mysqli_prepare($conn, "SELECT id FROM slike WHERE naziv_datoteke = ?");
        mysqli_stmt_bind_param($check, 's', $naziv);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) === 0) {
            $ins = mysqli_prepare($conn,
                "INSERT INTO slike (naziv_datoteke, opis, putanja, izvor) VALUES (?, '', ?, 'lokalno')"
            );
            mysqli_stmt_bind_param($ins, 'ss', $naziv, $putanja);
            mysqli_stmt_execute($ins);
        }
    }
    // Osvježi listu nakon eventualno novih unosa
    $slike_stmt = mysqli_query($conn,
        "SELECT s.id, s.naziv_datoteke, s.opis, s.putanja, s.izvor,
                ROUND(AVG(o.ocjena), 1) AS prosjek_ocjena,
                COUNT(o.id) AS broj_ocjena
         FROM slike s
         LEFT JOIN ocjene o ON o.id_slika = s.id
         GROUP BY s.id
         ORDER BY s.id ASC"
    );
    $sve_slike = mysqli_fetch_all($slike_stmt, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="CineVault Galerija - kolekcija fotografija vezanih uz filmsku umjetnost">
  <meta name="author" content="CineVault">
  <title>CineVault — Galerija slika</title>
  <link rel="stylesheet" href="assets/style/style_main.css">
  <link rel="stylesheet" href="assets/style/style_slike.css">
</head>
<body>

  <!-- Zaglavlje -->
  <header class="site-header" role="banner">
    <div class="header-inner">
      <div class="logo" aria-label="CineVault logo">
        <span class="logo-text">CINE<em>VAULT</em></span>
      </div>
      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true">
      <label for="nav-toggle" class="nav-burger" aria-label="Otvori navigacijski izbornik" role="button">
        <span></span><span></span><span></span>
      </label>
      <nav class="main-nav" aria-label="Primarna navigacija">
        <ul role="list">
          <li><a href="index.php" class="nav-link">Početna</a></li>
          <li><a href="grafikon.php" class="nav-link">Grafikoni</a></li>
          <li><a href="slike.php" class="nav-link active" aria-current="page">Galerija</a></li>
          <li><a href="videoteka.php" class="nav-link">Moja videoteka</a></li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <li><a href="admin.php" class="nav-link">Admin</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="nav-link">Odjava (<?= htmlspecialchars($_SESSION['username']) ?>)</a></li>
          <?php else: ?>
            <li><a href="login.php" class="nav-link">Prijava</a></li>
            <li><a href="register.php" class="nav-link">Registracija</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <main id="main-content">

    <div class="gallery-page-header">
      <h1 class="section-title gallery-main-title">GALERIJA SLIKA</h1>
    </div>

    <!-- Statusna poruka -->
    <div id="status-message" class="status-message" style="display:none" aria-live="polite"></div>

    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Upload forma — samo za prijavljene -->
    <section class="upload-section" aria-labelledby="upload-title">
      <h2 id="upload-title" class="upload-title">Dodaj novu sliku</h2>
      <div class="upload-form">
        <input type="file" id="upload-file" accept="image/jpeg,image/png" aria-label="Odaberi sliku">
        <input type="text" id="upload-opis" placeholder="Kratki opis (neobavezno)" maxlength="200">
        <button type="button" id="upload-btn" class="btn-upload">Uploadi sliku</button>
      </div>
      <p class="upload-note">Dozvoljeni formati: JPEG, PNG. Maksimalna veličina: 5MB.</p>
    </section>
    <?php endif; ?>

    <!-- Lightbox containeri (generirani PHP-om) -->
    <?php foreach ($sve_slike as $slika): ?>
    <div id="lb-<?= $slika['id'] ?>" class="lightbox-overlay" role="dialog" aria-label="Lightbox - <?= htmlspecialchars($slika['opis'] ?: $slika['naziv_datoteke']) ?>">
      <a href="#" class="lb-close" aria-label="Zatvori lightbox">✕ Zatvori</a>
      <img src="<?= htmlspecialchars($slika['putanja']) ?>"
           alt="<?= htmlspecialchars($slika['opis'] ?: $slika['naziv_datoteke']) ?> - puna veličina"
           loading="lazy">
    </div>
    <?php endforeach; ?>

    <!-- Galerija grid -->
    <section class="galerija" aria-labelledby="gallery-title">
      <h2 id="gallery-title" class="sr-only">Galerija fotografija</h2>

      <div class="gallery-grid" role="list">

        <?php foreach ($sve_slike as $slika):
          $id        = $slika['id'];
          $moja_ocj  = $moje_ocjene[$id] ?? 0;
          $prosjek   = $slika['prosjek_ocjena'] ?? 0;
          $broj_ocj  = $slika['broj_ocjena'] ?? 0;
          $opis_alt  = htmlspecialchars($slika['opis'] ?: $slika['naziv_datoteke']);
        ?>
        <figure class="gallery-item" role="listitem" data-id="<?= $id ?>">
          <a href="#lb-<?= $id ?>" class="gallery-link" aria-label="Otvori sliku u punoj veličini">
            <img src="<?= htmlspecialchars($slika['putanja']) ?>"
                 alt="<?= $opis_alt ?>"
                 loading="lazy"
                 width="300" height="200">
            <span class="gallery-overlay" aria-hidden="true">
              <span class="gallery-caption"><?= $opis_alt ?></span>
            </span>
          </a>

          <!-- Ocjenjivanje -->
          <div class="rating-container">
            <!-- Prosječna ocjena (IMDb stil) -->
            <div class="avg-rating" aria-label="Prosječna ocjena: <?= $prosjek ?: 'Još nema ocjena' ?>">
              <span class="avg-stars" aria-hidden="true">
                <?php
                  $full  = floor($prosjek);
                  $half  = ($prosjek - $full) >= 0.5 ? 1 : 0;
                  $empty = 5 - $full - $half;
                  echo str_repeat('★', $full);
                  echo $half ? '½' : '';
                  echo str_repeat('☆', $empty);
                ?>
              </span>
              <span class="avg-number">
                <?= $prosjek > 0 ? number_format($prosjek, 1) . '/5' : 'Bez ocjene' ?>
                <?= $broj_ocj > 0 ? "({$broj_ocj})" : '' ?>
              </span>
            </div>

            <!-- Interaktivne zvjezdice — samo za prijavljene -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="star-rating" data-id="<?= $id ?>" aria-label="Ocijeni ovu sliku">
              <?php for ($z = 1; $z <= 5; $z++): ?>
              <button type="button"
                      class="star <?= $z <= $moja_ocj ? 'active' : '' ?>"
                      data-val="<?= $z ?>"
                      aria-label="<?= $z ?> zvjezdica<?= $z > 1 ? 'e' : '' ?>">★</button>
              <?php endfor; ?>
            </div>
            <p class="rating-hint">
              <?= $moja_ocj > 0 ? "Vaša ocjena: {$moja_ocj}/5" : 'Kliknite za ocjenu' ?>
            </p>
            <?php else: ?>
            <p class="rating-hint"><a href="login.php">Prijavite se</a> za ocjenjivanje</p>
            <?php endif; ?>
          </div>

          <figcaption class="sr-only"><?= $opis_alt ?></figcaption>
        </figure>
        <?php endforeach; ?>

      </div>
    </section>

  </main>

  <footer class="site-footer" role="contentinfo">
    <div class="footer-inner">
      <div class="footer-logo" aria-label="CineVault logo">CINEVAULT</div>
      <p class="footer-copy">© 1999 CineVault</p>
    </div>
  </footer>

  <script>
  (function () {
    const statusBox = document.getElementById('status-message');

    // ── Prikaži statusnu poruku ───────────────────────────────────────────────
    function showStatus(type, msg) {
      statusBox.className = 'status-message status-message--' + type;
      statusBox.textContent = msg;
      statusBox.style.display = '';
      clearTimeout(statusBox._t);
      statusBox._t = setTimeout(() => { statusBox.style.display = 'none'; }, 4000);
    }

    // ── Hover efekt za zvjezdice ──────────────────────────────────────────────
    document.querySelectorAll('.star-rating').forEach(function (widget) {
      const stars = widget.querySelectorAll('.star');

      stars.forEach(function (star, idx) {
        // Hover — osvijetli do te zvjezdice
        star.addEventListener('mouseenter', function () {
          stars.forEach(function (s, i) {
            s.classList.toggle('hover', i <= idx);
          });
        });
      });

      // Napusti widget — ukloni hover
      widget.addEventListener('mouseleave', function () {
        stars.forEach(function (s) { s.classList.remove('hover'); });
      });

      // ── Klik — pošalji ocjenu AJAX-om ────────────────────────────────────
      stars.forEach(function (star) {
        star.addEventListener('click', function () {
          const idSlika = widget.dataset.id;
          const ocjena  = star.dataset.val;

          fetch('ocijeni_sliku.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_slika=' + encodeURIComponent(idSlika)
                + '&ocjena='  + encodeURIComponent(ocjena),
          })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.status === 'success') {
              // Ažuriraj aktivne zvjezdice
              stars.forEach(function (s, i) {
                s.classList.toggle('active', i < parseInt(ocjena));
              });

              // Ažuriraj prikaz prosječne ocjene
              const figure   = widget.closest('.gallery-item');
              const avgNum   = figure.querySelector('.avg-number');
              const avgStars = figure.querySelector('.avg-stars');
              const hint     = figure.querySelector('.rating-hint');

              if (avgNum) {
                avgNum.textContent = data.prosjek + '/5 (' + data.broj + ')';
              }
              if (avgStars) {
                avgStars.textContent = renderStars(parseFloat(data.prosjek));
              }
              if (hint) {
                hint.textContent = 'Vaša ocjena: ' + ocjena + '/5';
              }

              showStatus('success', 'Ocjena uspješno spremljena!');
            } else {
              showStatus('error', data.message || 'Greška pri ocjenjivanju.');
            }
          })
          .catch(function () {
            showStatus('error', 'Greška u komunikaciji sa serverom.');
          });
        });
      });
    });

    // Render zvjezdica na temelju prosječne ocjene
    function renderStars(prosjek) {
      var full  = Math.floor(prosjek);
      var half  = (prosjek - full) >= 0.5 ? 1 : 0;
      var empty = 5 - full - half;
      return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
    }

    // ── Upload slike ──────────────────────────────────────────────────────────
    const uploadBtn  = document.getElementById('upload-btn');
    const uploadFile = document.getElementById('upload-file');
    const uploadOpis = document.getElementById('upload-opis');

    if (uploadBtn) {
      uploadBtn.addEventListener('click', function () {
        const file = uploadFile.files[0];
        if (!file) {
          showStatus('error', 'Odaberite sliku za upload.');
          return;
        }

        // Klijentska validacija (server uvijek validira ponovo)
        const dozvoljeni = ['image/jpeg', 'image/png'];
        if (!dozvoljeni.includes(file.type)) {
          showStatus('error', 'Samo JPEG i PNG formati su dozvoljeni.');
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          showStatus('error', 'Slika ne smije biti veća od 5MB.');
          return;
        }

        const fd = new FormData();
        fd.append('slika', file);
        fd.append('opis',  uploadOpis.value);

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploada se...';

        fetch('upload_slike.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          showStatus(data.status, data.message);
          if (data.status === 'success') {
            uploadFile.value = '';
            uploadOpis.value = '';
            // Reload stranice da bi nova slika bila vidljiva
            setTimeout(function () { location.reload(); }, 1500);
          }
        })
        .catch(function () {
          showStatus('error', 'Greška pri uploadu.');
        })
        .finally(function () {
          uploadBtn.disabled = false;
          uploadBtn.textContent = 'Uploadi sliku';
        });
      });
    }
  })();
  </script>

</body>
</html>