<?php
session_start();
if (!isset($_SESSION['pizza_auth'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$message = '';
$erreur  = '';

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

if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id    = (int)($_POST['id'] ?? 0);
    $total = (int)getDB()->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn();
    if ($total <= 1) {
        $erreur = 'Impossible de supprimer le dernier compte.';
    } else {
        getDB()->prepare('DELETE FROM utilisateurs WHERE id = :id')->execute([':id' => $id]);
        $message = '✅ Compte supprimé.';
    }
}

$comptes = getDB()->query('SELECT id, login, created_at FROM utilisateurs ORDER BY created_at')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🔐 Gestion des comptes — Fidélité Pizza</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/comptes.css" />
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

<script src="js/comptes.js"></script>
</body>
</html>
