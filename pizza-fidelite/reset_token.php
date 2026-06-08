<?php
session_start();
require_once 'db.php';

$token   = trim($_GET['token'] ?? '');
$erreur  = '';
$message = '';
$valide  = false;
$login   = '';

if ($token === '') {
    $erreur = 'Lien invalide.';
} else {
    $stmt = getDB()->prepare(
        'SELECT login FROM reset_tokens WHERE token = :token AND expire_at > NOW()'
    );
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        $erreur = 'Ce lien est invalide ou a expiré (30 min). Recommencez depuis la page de connexion.';
    } else {
        $valide = true;
        $login  = $row['login'];
    }
}

if ($valide && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mdp  = $_POST['mdp']  ?? '';
    $mdp2 = $_POST['mdp2'] ?? '';

    if (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit faire au moins 6 caractères.';
        $valide = true;
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
        $valide = true;
    } else {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        getDB()->prepare('UPDATE utilisateurs SET mot_de_passe = :h WHERE login = :login')
               ->execute([':h' => $hash, ':login' => $login]);
        getDB()->prepare('DELETE FROM reset_tokens WHERE token = :token')
               ->execute([':token' => $token]);
        $valide  = false;
        $message = '✅ Mot de passe réinitialisé avec succès ! Vous pouvez vous connecter.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🔓 Réinitialisation — Fidélité Pizza</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/auth.css" />
</head>
<body>
<header>
  <span>🍕</span>
  <h1>Carte de Fidélité Pizza</h1>
</header>
<div class="wrap">
  <div class="card">

    <?php if ($message): ?>
      <h2>✅ Mot de passe réinitialisé</h2>
      <br>
      <div class="alert-ok"><?= htmlspecialchars($message) ?></div>
      <a href="login.php" class="btn-link">→ Se connecter</a>

    <?php elseif (!$valide): ?>
      <h2>❌ Lien invalide</h2>
      <br>
      <div class="alert-err"><?= htmlspecialchars($erreur) ?></div>
      <a href="forgot.php" class="back">← Faire une nouvelle demande</a>

    <?php else: ?>
      <h2>🔓 Nouveau mot de passe</h2>
      <p class="sub">Compte : <span class="compte-badge"><?= htmlspecialchars($login) ?></span></p>

      <?php if ($erreur): ?>
        <div class="alert-err"><?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <form method="POST">
        <label>Nouveau mot de passe <span style="color:#aaa;font-weight:400;">(6 min.)</span></label>
        <input type="password" name="mdp" placeholder="Nouveau mot de passe" autofocus />
        <label>Confirmer</label>
        <input type="password" name="mdp2" placeholder="Répétez le mot de passe" />
        <button type="submit" class="btn btn-primary">Valider</button>
      </form>
      <a href="login.php" class="back">← Retour à la connexion</a>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
