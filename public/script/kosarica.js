/**
 * kosarica.js — logika stranice košarice
 * Ucitava košaricu iz sessionStorage i upravlja prikazom, uklanjanjem i potvrdom.
 */

const CART_KEY = 'cinevault_cart';

// ─── POMOCNE FUNKCIJE (iste kao u movies.js) ──────────────────────────────────

function getGenres(film) {
  return (film['Zanr'] || film['Žanr'] || '')
    .split(',').map(g => g.trim()).filter(Boolean);
}

function getCountry(film) {
  return (film['Zemlja_porijekla'] || film['Zemlja'] || '').trim();
}

// ─── POHRANA ──────────────────────────────────────────────────────────────────

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

function cartRemove(naslov) {
  const cart = cartLoad().filter(f => f['Naslov'] !== naslov);
  cartSave(cart);
  return cart;
}

function cartClear() {
  sessionStorage.removeItem(CART_KEY);
}

// ─── RENDERIRANJE ─────────────────────────────────────────────────────────────

function renderCart() {
  const cart    = cartLoad();
  const wrapper = document.getElementById('cart-content');
  const empty   = document.getElementById('cart-empty');
  const counter = document.getElementById('cart-counter');
  const actions = document.getElementById('cart-actions');

  // Azuriraj brojac
  if (counter) {
    counter.textContent = cart.length;
  }

  if (!wrapper) return;

  if (cart.length === 0) {
    wrapper.innerHTML = '';
    if (empty)   empty.style.display   = 'block';
    if (actions) actions.style.display = 'none';
    return;
  }

  if (empty)   empty.style.display   = 'none';
  if (actions) actions.style.display = 'flex';

  // Gradi tablicu
  const zanrHeader = '<th scope="col">Žanr</th>';

  const redovi = cart.map(film => {
    const zanrTagovi = getGenres(film)
      .map(g => `<span class="genre-tag">${g}</span>`).join(' ');
    const ocjena  = film['Ocjena']       || '—';
    const trajanje = film['Trajanje_min'] ? `${film['Trajanje_min']} min` : '—';

    return `
      <tr data-naslov="${film['Naslov']}">
        <td>${film['Naslov'] || '—'}</td>
        <td>${film['Godina'] || '—'}</td>
        <td>${zanrTagovi || '—'}</td>
        <td>${trajanje}</td>
        <td>${getCountry(film) || '—'}</td>
        <td>${ocjena}</td>
        <td>
          <button class="btn-remove" data-naslov="${film['Naslov']}" aria-label="Ukloni ${film['Naslov']} iz košarice">
            ✕ Ukloni
          </button>
        </td>
      </tr>
    `;
  }).join('');

  wrapper.innerHTML = `
    <table class="films-table cart-table" aria-label="Košarica filmova">
      <thead>
        <tr>
          <th scope="col">Naslov</th>
          <th scope="col">Godina</th>
          ${zanrHeader}
          <th scope="col">Trajanje</th>
          <th scope="col">Država</th>
          <th scope="col">Ocjena</th>
          <th scope="col">Akcija</th>
        </tr>
      </thead>
      <tbody>${redovi}</tbody>
    </table>
  `;

  // Listeneri na gumbe za uklanjanje
  wrapper.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', () => {
      const naslov = btn.dataset.naslov;
      cartRemove(naslov);
      renderCart(); // re-render
    });
  });
}

// ─── POTVRDA POSUDBE ──────────────────────────────────────────────────────────

function confirmLoan() {
  const cart  = cartLoad();
  const count = cart.length;

  if (count === 0) return;

  const poruka = `Uspješno ste dodali ${count} ${count === 1 ? 'film' : 'filma'} u svoju košaricu za vikend maraton!`;

  // Prikazuje modalni dialog umjesto alert()
  showConfirmModal(poruka, () => {
    cartClear();
    renderCart();
    window.location.href = "index.html";
  });
}

// ─── MODALNI PROZOR ───────────────────────────────────────────────────────────

function showConfirmModal(poruka, onClose) {
  // Ukloni stari modal ako postoji
  document.getElementById('confirm-modal')?.remove();

  const modal = document.createElement('div');
  modal.id = 'confirm-modal';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-labelledby', 'modal-title');

  modal.innerHTML = `
    <div class="modal-backdrop"></div>
    <div class="modal-box">
      <div class="modal-icon">:)</div>
      <h2 id="modal-title" class="modal-title">Posudba potvrđena!</h2>
      <p class="modal-message">${poruka}</p>
      <button id="modal-close" class="btn-modal-close">Zatvori</button>
    </div>
  `;

  document.body.appendChild(modal);

  // Fokus na gumb radi pristupacnosti
  const closeBtn = modal.querySelector('#modal-close');
  closeBtn?.focus();

  const close = () => {
    modal.remove();
    if (onClose) onClose();
  };

  closeBtn?.addEventListener('click', close);
  modal.querySelector('.modal-backdrop')?.addEventListener('click', close);

  // ESC zatvara modal
  document.addEventListener('keydown', function escHandler(e) {
    if (e.key === 'Escape') { close(); document.removeEventListener('keydown', escHandler); }
  });
}

// ─── INICIJALIZACIJA ──────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  renderCart();

  document.getElementById('btn-confirm-loan')?.addEventListener('click', confirmLoan);

  document.getElementById('btn-clear-cart')?.addEventListener('click', () => {
    if (cartLoad().length === 0) return;
    cartClear();
    renderCart();
  });
});