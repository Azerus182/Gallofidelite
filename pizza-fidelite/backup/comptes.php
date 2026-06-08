<?php
session_start();
if (!isset($_SESSION['pizza_auth'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$message = '';
$erreur  = '';

// ---- Créer un compte ----
if (isset($_POST['action']) && $_POST['action'] === 'creer') {
    $login = trim($_POST['login'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';
    $mdp2  = $_POST['mdp2'] ?? '';

    if ($login === '' || $mdp === '') {
        $erreur = 'Identifiant et mot de passe requis.';
    } elseif (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit faire au moins 6 caractères.';
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } else {
        try {
            $hash = password_hash($mdp, PASSWORD_BCRYPT);
            getDB()->prepare('INSERT INTO utilisateurs (login, mot_de_passe) VALUES (:l, :h)')
                   ->execute([':l' => $login, ':h' => $hash]);
            $message = "✅ Compte « $login » créé avec succès.";
        } catch (PDOException $e) {
            $erreur = 'Cet identifiant existe déjà.';
        }
    }
}

// ---- Changer le mot de passe ----
if (isset($_POST['action']) && $_POST['action'] === 'changer_mdp') {
    $id   = (int)($_POST['id'] ?? 0);
    $mdp  = $_POST['mdp'] ?? '';
    $mdp2 = $_POST['mdp2'] ?? '';

    if ($mdp === '') {
        $erreur = 'Le mot de passe ne peut pas être vide.';
    } elseif (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit faire au moins 6 caractères.';
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        getDB()->prepare('UPDATE utilisateurs SET mot_de_passe = :h WHERE id = :id')
               ->execute([':h' => $hash, ':id' => $id]);
        $message = '✅ Mot de passe modifié avec succès.';
    }
}

// ---- Supprimer un compte ----
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id = (int)($_POST['id'] ?? 0);
    $total = (int)getDB()->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
    if ($total <= 1) {
        $erreur = 'Impossible de supprimer le dernier compte.';
    } else {
        getDB()->prepare('DELETE FROM utilisateurs WHERE id = :id')->execute([':id' => $id]);
        $message = '✅ Compte supprimé.';
    }
}

// ---- Liste des comptes ----
$comptes = getDB()->query('SELECT id, login, created_at FROM utilisateurs ORDER BY created_at')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🔐 Gestion des comptes — Fidélité Pizza</title>
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
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
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
    .header-right { margin-left: auto; display: flex; align-items: center; gap: 12px; }
    .header-right a {
      background: rgba(255,255,255,0.2);
      color: white;
      padding: 7px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: .9rem;
      font-weight: 600;
    }
    .header-right a:hover { background: rgba(255,255,255,0.35); }

    .container { max-width: 700px; margin: 36px auto; padding: 0 16px; }

    .card {
      background: white;
      border-radius: 12px;
      padding: 28px 32px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      margin-bottom: 24px;
    }
    .card h2 {
      font-size: 1.1rem;
      color: #e63012;
      margin-bottom: 20px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .form-group { margin-bottom: 14px; }
    label { display: block; font-size: .875rem; font-weight: 600; color: #555; margin-bottom: 5px; }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid #e0d8d0;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color .2s;
    }
    input:focus { outline: none; border-color: #e63012; }

    .btn {
      padding: 10px 22px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: .95rem;
      font-weight: 600;
      transition: background .15s;
    }
    .btn-primary { background: #e63012; color: white; }
    .btn-primary:hover { background: #c4260e; }
    .btn-red { background: #e74c3c; color: white; padding: 6px 14px; font-size: .85rem; }
    .btn-red:hover { background: #c0392b; }
    .btn-blue { background: #2980b9; color: white; padding: 6px 14px; font-size: .85rem; }
    .btn-blue:hover { background: #2471a3; }

    .alert-ok  { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
    .alert-err { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: .8rem; text-transform: uppercase; color: #999; padding: 8px 10px; border-bottom: 2px solid #f0ebe5; }
    td { padding: 12px 10px; border-bottom: 1px solid #f5f0eb; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .login-badge {
      background: #fef0ec;
      color: #e63012;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .9rem;
    }
    .you { font-size: .75rem; color: #27ae60; margin-left: 6px; }
    .actions-td { display: flex; gap: 8px; flex-wrap: wrap; }

    /* Modal changement mdp */
    .overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 100; align-items: center; justify-content: center;
    }
    .overlay.active { display: flex; }
    .modal {
      background: white; border-radius: 16px;
      padding: 32px 36px; max-width: 380px; width: 90%;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    }
    .modal h3 { font-size: 1.2rem; margin-bottom: 20px; color: #e63012; }
    .modal-btns { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
    .btn-cancel { background: #eee; color: #555; }
    .btn-cancel:hover { background: #ddd; }
  </style>
</head>
<body>

<header>
  <span>🔐</span>
  <h1>Gestion des comptes</h1>
  <div class="header-right">
    <span style="font-size:.9rem; opacity:.85;">👤 <?= htmlspecialchars($_SESSION['pizza_login']) ?></span>
    <a href="index.php">← Retour</a>
    <a href="logout.php">Déconnexion</a>
  </div>
</header>

<div class="container">

  <?php if ($message): ?>
    <div class="alert-ok"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($erreur): ?>
    <div class="alert-err"><?= htmlspecialchars($erreur) ?></div>
  <?php endif; ?>

  <!-- Liste des comptes -->
  <div class="card">
    <h2>👥 Comptes existants</h2>
    <table>
      <thead>
        <tr>
          <th>Identifiant</th>
          <th>Créé le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($comptes as $c): ?>
        <tr>
          <td>
            <span class="login-badge"><?= htmlspecialchars($c['login']) ?></span>
            <?php if ($c['login'] === $_SESSION['pizza_login']): ?>
              <span class="you">← vous</span>
            <?php endif; ?>
          </td>
          <td style="color:#888; font-size:.85rem;">
            <?= date('d/m/Y à H:i', strtotime($c['created_at'])) ?>
          </td>
          <td>
            <div class="actions-td">
              <button class="btn btn-blue"
                onclick="ouvrirMdp(<?= $c['id'] ?>, '<?= htmlspecialchars($c['login']) ?>')">
                🔑 MDP
              </button>
              <?php if ($c['login'] !== $_SESSION['pizza_login']): ?>
              <form method="POST" onsubmit="return confirm('Supprimer le compte « <?= htmlspecialchars($c['login']) ?> » ?')">
                <input type="hidden" name="action" value="supprimer" />
                <input type="hidden" name="id"     value="<?= $c['id'] ?>" />
                <button class="btn btn-red" type="submit">✕ Supprimer</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Créer un compte -->
  <div class="card">
    <h2>➕ Créer un nouveau compte</h2>
    <form method="POST">
      <input type="hidden" name="action" value="creer" />
      <div class="form-group">
        <label>Identifiant</label>
        <input type="text" name="login" placeholder="ex: marie" autocomplete="off"
               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Mot de passe <span style="color:#aaa; font-weight:400;">(6 caractères min.)</span></label>
        <input type="password" name="mdp" placeholder="Mot de passe" />
      </div>
      <div class="form-group">
        <label>Confirmer le mot de passe</label>
        <input type="password" name="mdp2" placeholder="Répétez le mot de passe" />
      </div>
      <button type="submit" class="btn btn-primary">Créer le compte</button>
    </form>
  </div>

</div>

<!-- Modal changement de mot de passe -->
<div class="overlay" id="overlay">
  <div class="modal">
    <h3>🔑 Nouveau mot de passe</h3>
    <p style="color:#888; font-size:.85rem; margin-bottom:16px;">Compte : <strong id="modal-login"></strong></p>
    <form method="POST" id="form-mdp">
      <input type="hidden" name="action" value="changer_mdp" />
      <input type="hidden" name="id" id="modal-id" />
      <div class="form-group">
        <label>Nouveau mot de passe</label>
        <input type="password" name="mdp" id="mdp-input" placeholder="Nouveau mot de passe" />
      </div>
      <div class="form-group">
        <label>Confirmer</label>
        <input type="password" name="mdp2" placeholder="Répétez le mot de passe" />
      </div>
      <div class="modal-btns">
        <button type="button" class="btn btn-cancel" onclick="fermerModal()">Annuler</button>
        <button type="submit" class="btn btn-primary">Valider</button>
      </div>
    </form>
  </div>
</div>

<script>
  function ouvrirMdp(id, login) {
    document.getElementById('modal-id').value    = id;
    document.getElementById('modal-login').textContent = login;
    document.getElementById('overlay').classList.add('active');
    setTimeout(() => document.getElementById('mdp-input').focus(), 100);
  }
  function fermerModal() {
    document.getElementById('overlay').classList.remove('active');
  }
  document.getElementById('overlay').addEventListener('click', e => {
    if (e.target === document.getElementById('overlay')) fermerModal();
  });
</script>

</body>
</html>
