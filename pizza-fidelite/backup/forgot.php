<?php
session_start();
require_once 'db.php';

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');

    if ($login === '') {
        $erreur = 'Veuillez saisir votre identifiant.';
    } else {
        // Vérifier que le compte existe
        $stmt = getDB()->prepare('SELECT id FROM utilisateurs WHERE login = :login');
        $stmt->execute([':login' => $login]);
        $compte = $stmt->fetch();

        if (!$compte) {
            $erreur = 'Identifiant introuvable.';
        } else {
            // Générer un token unique valable 30 minutes
            $token = bin2hex(random_bytes(32));

            // Supprimer les anciens tokens de ce login
            getDB()->prepare('DELETE FROM reset_tokens WHERE login = :login')
                   ->execute([':login' => $login]);

            // Expiration calculée par MySQL pour éviter les décalages de fuseau horaire
            getDB()->prepare('INSERT INTO reset_tokens (login, token, expire_at) VALUES (:login, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                   ->execute([':login' => $login, ':token' => $token]);

            // Construire le lien de reset
            $lien = 'http://localhost/pizza-fidelite/reset_token.php?token=' . $token;

            // Envoyer le mail
            $sujet = '🔑 Réinitialisation de votre mot de passe — Fidélité Pizza';
            $corps = '
            <div style="font-family: Segoe UI, sans-serif; max-width: 480px; margin: 0 auto; padding: 32px;">
              <div style="background:#e63012; padding:20px 28px; border-radius:12px 12px 0 0;">
                <h1 style="color:white; margin:0; font-size:1.4rem;">🍕 Fidélité Pizza</h1>
              </div>
              <div style="background:#fff; border:1px solid #f0ebe5; padding:28px; border-radius:0 0 12px 12px;">
                <h2 style="color:#222; font-size:1.1rem;">Réinitialisation du mot de passe</h2>
                <p style="color:#555; margin:16px 0;">Une demande de réinitialisation a été effectuée pour le compte <strong>' . htmlspecialchars($login) . '</strong>.</p>
                <p style="color:#555; margin:16px 0;">Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe. Ce lien est valable <strong>30 minutes</strong>.</p>
                <div style="text-align:center; margin:28px 0;">
                  <a href="' . $lien . '" style="background:#e63012; color:white; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; font-size:1rem;">
                    Réinitialiser mon mot de passe
                  </a>
                </div>
                <p style="color:#aaa; font-size:.8rem; margin-top:24px;">Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce mail.</p>
              </div>
            </div>';

            $envoye = envoyerMail($sujet, $corps);

            if ($envoye) {
                $message = '✅ Un lien de réinitialisation a été envoyé à l\'adresse mail configurée. Vérifiez votre boîte mail (et les spams).';
            } else {
                $erreur = '❌ Erreur lors de l\'envoi du mail. Vérifiez la configuration SMTP dans db.php.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🔑 Mot de passe oublié — Fidélité Pizza</title>
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
    input[type="text"] {
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
    .alert-ok  { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
    .alert-err { background: #fdecea; color: #c0392b; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
    .back { display: block; text-align: center; margin-top: 18px; color: #e63012; text-decoration: none; font-size: .9rem; }
    .back:hover { text-decoration: underline; }
  </style>
</head>
<body>
<header>
  <span>🍕</span>
  <h1>Carte de Fidélité Pizza</h1>
</header>
<div class="wrap">
  <div class="card">
    <h2>🔑 Mot de passe oublié</h2>
    <p class="sub">Entrez votre identifiant — un lien de réinitialisation sera envoyé par mail.</p>

    <?php if ($message): ?>
      <div class="alert-ok"><?= htmlspecialchars($message) ?></div>
      <a href="login.php" class="back">← Retour à la connexion</a>
    <?php else: ?>
      <?php if ($erreur): ?>
        <div class="alert-err"><?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>
      <form method="POST">
        <label for="login">Identifiant</label>
        <input type="text" id="login" name="login" placeholder="ex: admin" autofocus
               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" />
        <button type="submit" class="btn">Envoyer le lien</button>
      </form>
      <a href="login.php" class="back">← Retour à la connexion</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
