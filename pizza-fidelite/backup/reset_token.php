<?php
session_start();
require_once 'db.php';

$token   = trim($_GET['token'] ?? '');
$erreur  = '';
$message = '';
$valide  = false;
$login   = '';

// Vérifier le token
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

// Traitement du nouveau mot de passe
if ($valide && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $mdp  = $_POST['mdp']  ?? '';
    $mdp2 = $_POST['mdp2'] ?? '';

    if (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit faire au moins 6 caractères.';
        $valide = true; // garder le formulaire
    } elseif ($mdp !== $mdp2) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
        $valide = true;
    } else {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        getDB()->prepare('UPDATE utilisateurs SET mot_de_passe = :h WHERE login = :login')
               ->execute([':h' => $hash, ':login' => $login]);

        // Supprimer le token utilisé
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
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: url('pizza.png') center center / cover fixed no-repeat;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    body::before {
      content: '';
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.52);
      z-index: 0;
    }
    header {
      position: relative; z-index: 1;
      background: #e63012;
      color: white;
      padding: 20px 32px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    header h1 { font-size: 1.7rem; font-weight: 700; }
    .wrap {
      position: relative; z-index: 1;
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
    }
    .card {
      background: white;
      border-radius: 16px;
      padding: 40px 36px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.22);
    }
    .card h2 { text-align: center; font-size: 1.3rem; color: #e63012; margin-bottom: 8px; }
    .card p.sub { text-align: center; color: #999; font-size: .85rem; margin-bottom: 24px; }
    label { display: block; font-size: .875rem; font-weight: 600; color: #555; margin-bottom: 6px; margin-top: 16px; }
    input[type="password"] {
      width: 100%;
      padding: 11px 14px;
      border: 2px solid #e0d8d0;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color .2s;
    }
    input:focus { outline: none; border-color: #e63012; }
    .btn {
      width: 100%;
      margin-top: 24px;
      padding: 13px;
      background: #e63012;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
    }
    .btn:hover { background: #c4260e; }
    .btn-link {
      display: block;
      text-align: center;
      margin-top: 18px;
      color: white;
      background: #27ae60;
      padding: 13px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
    }
    .alert-ok  { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
    .alert-err { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
    .back { display: block; text-align: center; margin-top: 18px; color: #e63012; text-decoration: none; font-size: .9rem; }
    .back:hover { text-decoration: underline; }
    .compte-badge {
      display: inline-block;
      background: #fef0ec;
      color: #e63012;
      border-radius: 20px;
      padding: 3px 12px;
      font-weight: 700;
      font-size: .85rem;
      margin-bottom: 8px;
    }
  </style>
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
        <label>Nouveau mot de passe <span style="color:#aaa; font-weight:400;">(6 min.)</span></label>
        <input type="password" name="mdp" placeholder="Nouveau mot de passe" autofocus />

        <label>Confirmer</label>
        <input type="password" name="mdp2" placeholder="Répétez le mot de passe" />

        <button type="submit" class="btn">Valider</button>
      </form>
      <a href="login.php" class="back">← Retour à la connexion</a>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
