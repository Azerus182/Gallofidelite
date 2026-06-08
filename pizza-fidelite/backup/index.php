<?php
session_start();
if (!isset($_SESSION['pizza_auth'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🍕 Fidélité Pizza</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: url('pizza.png') center center / cover fixed no-repeat;
      color: #222;
      min-height: 100vh;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      z-index: 0;
    }

    header, .container { position: relative; z-index: 1; }

    header {
      background: #e63012;
      color: white;
      padding: 20px 32px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.18);
    }
    header h1 { font-size: 1.7rem; font-weight: 700; }
    header span { font-size: 2rem; }

    .container {
      max-width: 820px;
      margin: 36px auto;
      padding: 0 16px;
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 24px 28px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 28px;
    }
    .card h2 {
      font-size: 1.1rem;
      color: #e63012;
      margin-bottom: 16px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .form-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .form-row input {
      flex: 1;
      min-width: 160px;
      padding: 10px 14px;
      border: 2px solid #e0d8d0;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color .2s;
    }
    .form-row input:focus {
      outline: none;
      border-color: #e63012;
    }
    .btn {
      padding: 10px 22px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: background .15s, transform .1s;
    }
    .btn:active { transform: scale(.97); }
    .btn-primary { background: #e63012; color: white; }
    .btn-primary:hover { background: #c4260e; }
    .btn-green { background: #27ae60; color: white; }
    .btn-green:hover { background: #1e8a4a; }
    .btn-red { background: #e74c3c; color: white; font-size: .85rem; padding: 7px 14px; }
    .btn-red:hover { background: #c0392b; }
    .btn-orange { background: #e67e22; color: white; }
    .btn-orange:hover { background: #ca6f1e; }

    #search {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid #e0d8d0;
      border-radius: 8px;
      font-size: 1rem;
    }
    #search:focus { outline: none; border-color: #e63012; }

    #liste { display: flex; flex-direction: column; gap: 14px; }

    .client-card {
      background: white;
      border-radius: 12px;
      padding: 18px 22px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      display: flex;
      align-items: center;
      gap: 18px;
      flex-wrap: wrap;
      border-left: 5px solid #e0d8d0;
      transition: border-color .2s;
    }
    .client-card.has-prize {
      border-left-color: #f1c40f;
      background: #fffbec;
    }

    .client-info { flex: 1; min-width: 140px; }
    .client-name { font-size: 1.1rem; font-weight: 700; }
    .client-sub { font-size: .85rem; color: #888; margin-top: 2px; }

    .points-area { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .points-display { display: flex; gap: 4px; align-items: center; }
    .dot { width: 18px; height: 18px; border-radius: 50%; background: #e0d8d0; display: inline-block; transition: background .2s; }
    .dot.filled { background: #e63012; }

    .points-text { font-size: 1.3rem; font-weight: 800; color: #e63012; min-width: 40px; text-align: center; }

    .badge-offerte {
      background: #f1c40f;
      color: #7d6000;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: .85rem;
      white-space: nowrap;
    }

    .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

    .empty { text-align: center; color: #bbb; font-size: 1.1rem; padding: 40px 0; }

    #toast {
      position: fixed;
      bottom: 28px; left: 50%;
      transform: translateX(-50%) translateY(80px);
      background: #333;
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-size: .95rem;
      opacity: 0;
      transition: opacity .3s, transform .3s;
      z-index: 999;
      pointer-events: none;
      white-space: nowrap;
    }
    #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

    .overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 100;
      align-items: center;
      justify-content: center;
    }
    .overlay.active { display: flex; }
    .modal {
      background: white;
      border-radius: 16px;
      padding: 32px 36px;
      max-width: 380px;
      width: 90%;
      text-align: center;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    }
    .modal h3 { font-size: 1.4rem; margin-bottom: 10px; }
    .modal p { color: #555; margin-bottom: 24px; }
    .modal-btns { display: flex; gap: 12px; justify-content: center; }

    @media (max-width: 540px) {
      .client-card { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

<header>
  <span>🍕</span>
  <h1>Carte de Fidélité Pizza</h1>
  <div style="margin-left:auto; display:flex; align-items:center; gap:12px;">
    <span style="font-size:.9rem; opacity:.85;">👤 <?= htmlspecialchars($_SESSION['pizza_login'] ?? '') ?></span>
    <a href="comptes.php" style="background:rgba(255,255,255,0.2); color:white; padding:7px 16px; border-radius:8px; text-decoration:none; font-size:.9rem; font-weight:600;">⚙️ Comptes</a>
    <a href="logout.php"  style="background:rgba(255,255,255,0.2); color:white; padding:7px 16px; border-radius:8px; text-decoration:none; font-size:.9rem; font-weight:600;">Déconnexion</a>
  </div>
</header>

<div class="container">

  <div class="card">
    <h2>➕ Nouveau client</h2>
    <div class="form-row">
      <input type="text" id="prenom"    placeholder="Prénom"    autocomplete="off" />
      <input type="text" id="nom"       placeholder="Nom"       autocomplete="off" />
      <input type="text" id="ville"     placeholder="Ville"     autocomplete="off" />
      <input type="tel"  id="telephone" placeholder="Téléphone" autocomplete="off" />
      <button class="btn btn-primary" onclick="ajouterClient()">Ajouter</button>
    </div>
  </div>

  <div class="card" style="padding: 16px 28px;">
    <input type="text" id="search" placeholder="🔍 Rechercher un client…" oninput="renderListe()" />
  </div>

  <div class="card">
    <h2>👥 Clients (<span id="nb-clients">0</span>)</h2>
    <div id="liste"></div>
  </div>

</div>

<!-- Modal pizza offerte -->
<div class="overlay" id="overlay">
  <div class="modal">
    <h3>🎉 Pizza offerte !</h3>
    <p id="modal-msg">Ce client a gagné une pizza gratuite. Valider la récompense ?</p>
    <div class="modal-btns">
      <button class="btn btn-orange" onclick="fermerModal()">Annuler</button>
      <button class="btn btn-green"  onclick="confirmerOfferte()">Valider 🍕</button>
    </div>
  </div>
</div>

<!-- Modal modifier points -->
<div class="overlay" id="overlayPts">
  <div class="modal">
    <h3>📝 Modifier les points</h3>
    <p style="color:#666; font-size:.9rem; margin-bottom:14px;">Entrez un nombre positif ou négatif (ex: 5, -2)</p>
    <input type="number" id="inputPts" placeholder="Nombre de points"
           style="width:100%; padding:10px; border:2px solid #e0d8d0; border-radius:8px; font-size:1rem; margin-bottom:16px;" />
    <div class="modal-btns">
      <button class="btn btn-orange" onclick="fermerModalPts()">Annuler</button>
      <button class="btn btn-green"  onclick="confirmerPoints()">Valider</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
  const API = 'api.php';
  let clients = [];
  let modalClientId = null;

  async function api(action, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const url = `${API}?action=${action}` + (method === 'DELETE' && body?.id ? `&id=${body.id}` : '');
    const res  = await fetch(url, opts);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Erreur serveur');
    return json.data;
  }

  async function charger() {
    try { clients = await api('liste'); }
    catch (e) { toast('⚠️ Impossible de contacter la base de données.'); }
    renderListe();
  }

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

  async function supprimerClient(id) {
    if (!confirm('Supprimer ce client ?')) return;
    try {
      await api('supprimer', 'DELETE', { id });
      clients = clients.filter(c => c.id !== id);
      renderListe();
      toast('Client supprimé.');
    } catch (e) { toast('❌ ' + e.message); }
  }

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

  function ouvrirModal(id) {
    const c = clients.find(c => c.id === id);
    if (!c) return;
    modalClientId = id;
    const offerts = Math.floor(c.points / 10);
    document.getElementById('modal-msg').textContent =
      `${c.prenom} ${c.nom} a ${offerts} pizza${offerts > 1 ? 's' : ''} offerte${offerts > 1 ? 's' : ''} disponible${offerts > 1 ? 's' : ''}. Valider une récompense ?`;
    document.getElementById('overlay').classList.add('active');
  }

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

  charger();
</script>
</body>
</html>
