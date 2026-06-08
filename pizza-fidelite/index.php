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
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/app.css" />
</head>
<body>

<header>
  <span>🍕</span>
  <h1>Carte de Fidélité Pizza</h1>
  <div class="header-right">
    <span style="font-size:.9rem; opacity:.85;">👤 <?= htmlspecialchars($_SESSION['pizza_login'] ?? '') ?></span>
    <a href="comptes.php">⚙️ Comptes</a>
    <a href="logout.php">Déconnexion</a>
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
    <input type="number" id="inputPts" placeholder="Nombre de points" />
    <div class="modal-btns">
      <button class="btn btn-orange" onclick="fermerModalPts()">Annuler</button>
      <button class="btn btn-green"  onclick="confirmerPoints()">Valider</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script src="js/app.js"></script>
</body>
</html>
