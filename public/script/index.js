let allFilms = [];

//  KOŠARICA 

const CART_KEY = 'cinevault_cart';

function cartLoad() {
  try {
    return JSON.parse(sessionStorage.getItem(CART_KEY)) || [];
  } catch {
    return [];
  }
}

function cartSave(cart) {
  sessionStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function cartAdd(film) {
  const cart = cartLoad();
  const vec  = cart.some(f => f['Naslov'] === film['Naslov']);
  if (!vec) {
    cart.push(film);
    cartSave(cart);
  }
  return !vec;
}

function cartCount() {
  return cartLoad().length;
}

function updateCartBadge() {
  const badge = document.getElementById('cart-badge');
  if (!badge) return;
  const n = cartCount();
  badge.textContent = n;
  badge.style.display = n > 0 ? 'inline-block' : 'none';
}

//  CSV PARSIRANJE 

function parseCSVLine(line) {
  const result = [];
  let current = '';
  let insideQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    if (char === '"') {
      insideQuotes = !insideQuotes;
    } else if (char === ',' && !insideQuotes) {
      result.push(current);
      current = '';
    } else {
      current += char;
    }
  }
  result.push(current);
  return result;
}

function parseCSV(text) {
  const lines = text.trim().split('\n');
  if (lines.length < 2) return [];
  const headers = parseCSVLine(lines[0]);
  return lines.slice(1).map(line => {
    const values = parseCSVLine(line);
    const obj = {};
    headers.forEach((header, i) => {
      obj[header.trim()] = (values[i] ?? '').trim();
    });
    return obj;
  });
}

// ─── POMOCNE FUNKCIJE ─────────────────────────────────────────────────────────

function getGenres(film) {
  return (film['Zanr'] || film['Žanr'] || '')
    .split(',').map(g => g.trim()).filter(Boolean);
}

function getCountry(film) {
  return (film['Zemlja_porijekla'] || film['Zemlja'] || '').trim();
}

function getYear(film) {
  return parseInt(film['Godina'], 10) || 0;
}

function getRating(film) {
  return parseFloat(film['Ocjena']) || 0;
}

// ─── FILTERI ──────────────────────────────────────────────────────────────────

function populateFilters(films) {
    const allGenres = new Set();
    films.forEach(film => getGenres(film).forEach(g => allGenres.add(g)));

    const genreSelect = document.getElementById('filter-genre');
    if (genreSelect) {
        [...allGenres].sort().forEach(genre => {
        const opt = document.createElement('option');
        opt.value = genre;
        opt.textContent = genre;
        genreSelect.appendChild(opt);
        });
    }

    const allCountries = new Set();
    films.forEach(film => {
        const c = getCountry(film);
        if (c) {
            c.split('/').forEach(country => {
            const trimmed = country.trim();
            if (trimmed) allCountries.add(trimmed);
            });
        }
    });

    const countrySelect = document.getElementById('filter-country');
    if (countrySelect) {
        [...allCountries].sort().forEach(country => {
        const opt = document.createElement('option');
        opt.value = country;
        opt.textContent = country;
        countrySelect.appendChild(opt);
        });
    }

    const years = films.map(getYear).filter(y => y > 0);
    const minYear = Math.min(...years);
    const maxYear = Math.max(...years);

    const sliderMin = document.getElementById('filter-year-min');
    const sliderMax = document.getElementById('filter-year-max');
    const labelMin  = document.getElementById('year-min-label');
    const labelMax  = document.getElementById('year-max-label');

    if (sliderMin && sliderMax) {
        sliderMin.min = minYear; sliderMin.max = maxYear; sliderMin.value = minYear;
        sliderMax.min = minYear; sliderMax.max = maxYear; sliderMax.value = maxYear;
        if (labelMin) labelMin.textContent = minYear;
        if (labelMax) labelMax.textContent = maxYear;
    }
}

function applyFilters() {
    const selectedGenre = document.getElementById('filter-genre')?.value || '';
    const selectedCountry = document.getElementById('filter-country')?.value || '';
    const minRating = parseFloat(document.getElementById('filter-rating')?.value) || 0;
    const yearMin = parseInt(document.getElementById('filter-year-min')?.value, 10) || 0;
    const yearMax = parseInt(document.getElementById('filter-year-max')?.value, 10) || 9999;

    const filtered = allFilms.filter(film => {
        if (selectedGenre && !getGenres(film).includes(selectedGenre))
            return false;
        if (selectedCountry) {
            const countries = getCountry(film).split('/').map(c => c.trim());

            if (!countries.includes(selectedCountry)) return false;
        }

        if (getRating(film) < minRating)
            return false;
        const year = getYear(film);

        if (year < yearMin || year > yearMax)
            return false;

        return true;
    });

    renderFilms(filtered);
    updateResultCount(filtered.length);
}

function updateResultCount(count) {
    const counter = document.getElementById('results-count');
    if (counter) {
        counter.textContent = `Prikazano: ${count} ${count === 1 ? 'film' : 'filmova'}`;
    }
}

// ─── RENDERIRANJE TABLICE ─────────────────────────────────────────────────────

function renderFilms(films) {
  const tbody = document.querySelector('.films-table tbody');
  if (!tbody) return;

  tbody.innerHTML = '';

  if (films.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="no-data">Nema filmova koji odgovaraju odabranim filterima.</td></tr>';
    return;
  }

  const cart = cartLoad();

  films.forEach(film => {
    const tr = document.createElement('tr');

    const zanrTagovi = getGenres(film)
      .map(g => `<span class="genre-tag">${g}</span>`).join(' ');

    const trajanje = film['Trajanje_min'] ? `${film['Trajanje_min']} min` : '—';
    const uKosarici = cart.some(f => f['Naslov'] === film['Naslov']);

    tr.innerHTML = `
      <td>${film['Naslov'] || '—'}</td>
      <td>${film['Godina'] || '—'}</td>
      <td>${zanrTagovi || '—'}</td>
      <td>${trajanje}</td>
      <td>${getCountry(film) || '—'}</td>
      <td>${getRating(film) || '—'}</td>
      <td>
        <button
          class="btn-add-cart ${uKosarici ? 'btn-add-cart--added' : ''}"
          data-naslov="${film['Naslov']}"
          ${uKosarici ? 'disabled' : ''}
          aria-label="Dodaj film ${film['Naslov']} u košaricu"
        >
          ${uKosarici ? 'Dodano' : '+ Košarica'}
        </button>
      </td>
    `;

    // Listener na gumb unutar ovog retka
    const btn = tr.querySelector('.btn-add-cart');
    btn?.addEventListener('click', () => {
      const dodan = cartAdd(film);
      if (dodan) {
        btn.textContent = 'Dodano';
        btn.classList.add('btn-add-cart--added');
        btn.disabled = true;
        updateCartBadge();
        showCartToast(film['Naslov']);
      }
    });

    tbody.appendChild(tr);
  });
}

// ─── TOAST OBAVIJEST ──────────────────────────────────────────────────────────

/** Prikazuje kratku obavijest na dnu ekrana. */
function showCartToast(naslov) {
  let toast = document.getElementById('cart-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'cart-toast';
    document.body.appendChild(toast);
  }
  toast.textContent = `"${naslov}" dodan u košaricu.`;
  toast.classList.add('cart-toast--visible');
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('cart-toast--visible'), 2800);
}

// ─── EVENT LISTENERI ──────────────────────────────────────────────────────────

function bindFilterEvents() {
  ['filter-genre', 'filter-country', 'filter-rating'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', applyFilters);
    document.getElementById(id)?.addEventListener('change', applyFilters);
  });

  const sliderMin = document.getElementById('filter-year-min');
  const sliderMax = document.getElementById('filter-year-max');
  const labelMin  = document.getElementById('year-min-label');
  const labelMax  = document.getElementById('year-max-label');

  sliderMin?.addEventListener('input', () => {
    if (parseInt(sliderMin.value) > parseInt(sliderMax.value)) sliderMin.value = sliderMax.value;
    if (labelMin) labelMin.textContent = sliderMin.value;
    applyFilters();
  });

  sliderMax?.addEventListener('input', () => {
    if (parseInt(sliderMax.value) < parseInt(sliderMin.value)) sliderMax.value = sliderMin.value;
    if (labelMax) labelMax.textContent = sliderMax.value;
    applyFilters();
  });

  document.getElementById('btn-reset-filters')?.addEventListener('click', resetFilters);
}

function resetFilters() {
  const sliderMin = document.getElementById('filter-year-min');
  const sliderMax = document.getElementById('filter-year-max');
  const labelMin  = document.getElementById('year-min-label');
  const labelMax  = document.getElementById('year-max-label');

  document.getElementById('filter-genre')  && (document.getElementById('filter-genre').value  = '');
  document.getElementById('filter-country') && (document.getElementById('filter-country').value = '');
  document.getElementById('filter-rating')  && (document.getElementById('filter-rating').value  = '');

  if (sliderMin && sliderMax) {
    sliderMin.value = sliderMin.min; sliderMax.value = sliderMax.max;
    if (labelMin) labelMin.textContent = sliderMin.min;
    if (labelMax) labelMax.textContent = sliderMax.max;
  }

  renderFilms(allFilms);
  updateResultCount(allFilms.length);
}

// ─── INICIJALIZACIJA ──────────────────────────────────────────────────────────

async function loadFilms() {
  const tbody = document.querySelector('.films-table tbody');

  try {
    const response = await fetch('data/movies.csv');
    if (!response.ok) throw new Error(`HTTP greška: ${response.status}`);

    const text = await response.text();
    allFilms = parseCSV(text);

    populateFilters(allFilms);
    bindFilterEvents();
    renderFilms(allFilms);
    updateResultCount(allFilms.length);
    updateCartBadge();

  } catch (error) {
    console.error('Greška pri ucitavanju filmova:', error);
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="7" class="no-data error">
        Greška pri ucitavanju podataka: ${error.message}
      </td></tr>`;
    }
  }
}

document.addEventListener('DOMContentLoaded', loadFilms);