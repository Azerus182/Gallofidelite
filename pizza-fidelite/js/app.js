/* =============================================
   app.js — JavaScript de index.php
   Fidélité Pizza
   ============================================= */

const API = 'api.php';
let clients = [];
let modalClientId = null;

// ---- Appels API ----
async function api(action, method = 'GET', body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  const url = `${API}?action=${action}` + (method === 'DELETE' && body?.id ? `&id=${body.id}` : '');
  const res  = await fetch(url, opts);
  const json = await res.json();
  if (!json.ok) throw new Error(json.error || 'Erreur serveur');
  return json.data;
}

// ---- Chargement initial ----
async function charger() {
  try { clients = await api('liste'); }
  catch (e) { toast('⚠️ Impossible de contacter la base de données.'); }
  renderListe();
}

// ---- Ajouter un client ----
async function ajouterClient() {
  const prenom    = document.getElementById('prenom').value.trim();
  const nom       = document.getElementById('nom').value.trim();
  const ville     = document.getElementById('ville').value.trim();
  const telephone = document.getElementById('telephone').value.trim();
  if (!prenom || !nom) { toast('Veuillez saisir le prénom et le nom.'); return; }
  try {
    const c = await api('ajouter', 'POST', { prenom, nom, ville, telephone });
    clients.push(c);
    ['prenom','nom','ville','telephone'].forEach(id => document.getElementById(id).value = '');
    renderListe();
    toast(`✅ ${prenom} ${nom} ajouté(e) !`);
  } catch (e) { toast('❌ ' + e.message); }
}

// ---- Ajouter 1 point ----
async function ajouterPoint(id) {
  try {
    const res = await api('achat', 'POST', { id });
    const c   = clients.find(c => c.id === id);
    if (c) { c.points = res.points; c.dernier_passage = res.dernier_passage; }
    renderListe();
    if (res.points > 0 && res.points % 10 === 0) {
      modalClientId = id;
      document.getElementById('modal-msg').textContent =
        `${c.prenom} ${c.nom} a atteint ${res.points} points — une pizza offerte à valider !`;
      document.getElementById('overlay').classList.add('active');
    } else {
      toast(`+1 point pour ${c?.prenom} (${res.points}/10)`);
    }
  } catch (e) { toast('❌ ' + e.message); }
}

// ---- Valider pizza offerte ----
async function confirmerOfferte() {
  try {
    const res = await api('offerte', 'POST', { id: modalClientId });
    const c   = clients.find(c => c.id === modalClientId);
    if (c) { c.points = res.points; c.dernier_passage = res.dernier_passage; }
    renderListe();
    toast(`🍕 Pizza offerte validée pour ${c?.prenom} ! (${res.points} pts restants)`);
  } catch (e) { toast('❌ ' + e.message); }
  fermerModal();
}

function fermerModal() {
  document.getElementById('overlay').classList.remove('active');
  modalClientId = null;
}

// ---- Modifier les points manuellement ----
function ouvrirModalPoints(id) {
  modalClientId = id;
  document.getElementById('inputPts').value = '';
  document.getElementById('overlayPts').classList.add('active');
  setTimeout(() => document.getElementById('inputPts').focus(), 100);
}

function fermerModalPts() {
  document.getElementById('overlayPts').classList.remove('active');
  modalClientId = null;
}

async function confirmerPoints() {
  const pts = parseInt(document.getElementById('inputPts').value);
  if (!pts || pts === 0) { toast('Entrez un nombre valide différent de 0.'); return; }
  try {
    const res = await api('modifier-points', 'POST', { id: modalClientId, points: pts });
    const c   = clients.find(c => c.id === modalClientId);
    if (c) { c.points = res.points; c.dernier_passage = res.dernier_passage; }
    renderListe();
    fermerModalPts();
    if (res.points > 0 && res.points % 10 === 0) {
      modalClientId = res.id;
      document.getElementById('modal-msg').textContent =
        `${c?.prenom} ${c?.nom} a atteint ${res.points} points — une pizza offerte à valider !`;
      document.getElementById('overlay').classList.add('active');
    } else {
      toast(`${pts > 0 ? '+' : ''}${pts} points pour ${c?.prenom} (${res.points} total)`);
    }
  } catch (e) { toast('❌ ' + e.message); }
}

// ---- Ouvrir modal pizza offerte depuis badge ----
function ouvrirModal(id) {
  const c = clients.find(c => c.id === id);
  if (!c) return;
  modalClientId = id;
  const offerts = Math.floor(c.points / 10);
  document.getElementById('modal-msg').textContent =
    `${c.prenom} ${c.nom} a ${offerts} pizza${offerts > 1 ? 's' : ''} offerte${offerts > 1 ? 's' : ''} disponible${offerts > 1 ? 's' : ''}. Valider une récompense ?`;
  document.getElementById('overlay').classList.add('active');
}

// ---- Supprimer un client ----
async function supprimerClient(id) {
  if (!confirm('Supprimer ce client ?')) return;
  try {
    await api('supprimer', 'DELETE', { id });
    clients = clients.filter(c => c.id !== id);
    renderListe();
    toast('Client supprimé.');
  } catch (e) { toast('❌ ' + e.message); }
}

// ---- Rendu de la liste ----
function renderListe() {
  const q      = document.getElementById('search').value.toLowerCase();
  const filtre = clients.filter(c =>
    (c.prenom + ' ' + c.nom + ' ' + (c.ville || '')).toLowerCase().includes(q)
  ).sort((a, b) => (a.nom + a.prenom).localeCompare(b.nom + b.prenom));

  document.getElementById('nb-clients').textContent = clients.length;
  const el = document.getElementById('liste');

  if (filtre.length === 0) {
    el.innerHTML = `<p class="empty">Aucun client trouvé.</p>`;
    return;
  }

  el.innerHTML = filtre.map(c => {
    const restant  = c.points % 10;
    const offerts  = Math.floor(c.points / 10);
    const hasPrize = offerts > 0;

    const dots = Array.from({ length: 10 }, (_, i) =>
      `<span class="dot ${i < restant ? 'filled' : ''}"></span>`
    ).join('');

    const badgeOfferte = hasPrize
      ? `<span class="badge-offerte">🍕 ×${offerts} offerte${offerts > 1 ? 's' : ''} dispo</span>` : '';

    const btnValider = hasPrize
      ? `<button class="btn btn-orange" onclick="ouvrirModal(${c.id})">Utiliser 🍕</button>` : '';

    return `
      <div class="client-card ${hasPrize ? 'has-prize' : ''}">
        <div class="client-info">
          <div class="client-name">${escHtml(c.prenom)} ${escHtml(c.nom)}</div>
          <div class="client-sub">
            ${c.ville ? `📍 ${escHtml(c.ville)}` : ''}${c.ville && c.telephone ? ' &nbsp;·&nbsp; ' : ''}${c.telephone ? `📞 ${escHtml(c.telephone)}` : ''}
          </div>
          <div class="client-sub" style="margin-top:3px;">🏆 ${c.points} point${c.points !== 1 ? 's' : ''} au total</div>
          <div class="client-sub" style="margin-top:3px;">🕐 Dernier passage : ${c.dernier_passage ? formatDate(c.dernier_passage) : '—'}</div>
        </div>
        <div class="points-area">
          <div class="points-display">${dots}</div>
          <div class="points-text">${restant}/10</div>
        </div>
        ${badgeOfferte}
        <div class="actions">
          <button class="btn btn-green"  onclick="ajouterPoint(${c.id})">+1 🍕</button>
          <button class="btn btn-orange" onclick="ouvrirModalPoints(${c.id})" style="background:#9b59b6;">📝 Pts</button>
          ${btnValider}
          <button class="btn btn-red"   onclick="supprimerClient(${c.id})">✕</button>
        </div>
      </div>`;
  }).join('');
}

// ---- Utilitaires ----
function toast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 2800);
}

function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'2-digit', year:'numeric' })
    + ' à ' + d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ---- Événements clavier ----
['prenom','nom','ville','telephone'].forEach(id => {
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') ajouterClient();
  });
});

document.getElementById('inputPts').addEventListener('keydown', e => {
  if (e.key === 'Enter') confirmerPoints();
});

document.getElementById('overlay').addEventListener('click', e => {
  if (e.target === document.getElementById('overlay')) fermerModal();
});
document.getElementById('overlayPts').addEventListener('click', e => {
  if (e.target === document.getElementById('overlayPts')) fermerModalPts();
});

// ---- Init ----
charger();
