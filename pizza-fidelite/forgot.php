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
        $stmt = getDB()->prepare('SELECT id FROM utilisateurs WHERE login = :login');
        $stmt->execute([':login' => $login]);
        $compte = $stmt->fetch();

        if (!$compte) {
            $erreur = 'Identifiant introuvable.';
        } else {
            $token = bin2hex(random_bytes(32));

            getDB()->prepare('DELETE FROM reset_tokens WHERE login = :login')
                   ->execute([':login' => $login]);

            getDB()->prepare('INSERT INTO reset_tokens (login, token, expire_at) VALUES (:login, :token, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                   ->execute([':login' => $login, ':token' => $token]);

            $lien  = 'http://localhost/pizza-fidelite/reset_token.php?token=' . $token;
            $sujet = '🔑 Réinitialisation de votre mot de passe — Fidélité Pizza';
            $corps = '
            <div style="font-family:Segoe UI,sans-serif;max-width:480px;margin:0 auto;padding:32px;">
              <div style="background:#e63012;padding:20px 28px;border-radius:12px 12px 0 0;">
                <h1 style="color:white;margin:0;font-size:1.4rem;">🍕 Fidélité Pizza</h1>
              </div>
              <div style="background:#fff;border:1px solid #f0ebe5;padding:28px;border-radius:0 0 12px 12px;">
                <h2 style="color:#222;font-size:1.1rem;">Réinitialisation du mot de passe</h2>
                <p style="color:#555;margin:16px 0;">Demande pour le compte <strong>' . htmlspecialchars($login) . '</strong>.</p>
                <p style="color:#555;margin:16px 0;">Ce lien est valable <strong>30 minutes</strong>. Vérifiez aussi les spams.</p>
                <div style="text-align:center;margin:28px 0;">
                  <a href="' . $lien . '" style="background:#e63012;color:white;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;">
                    Réinitialiser mon mot de passe
                  </a>
                </div>
                <p style="color:#aaa;font-size:.8rem;margin-top:24px;">Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce mail.</p>
              </div>
            </div>';

            $envoye = envoyerMail($sujet, $corps);
            if ($envoye) {
                $message = '✅ Lien envoyé ! Vérifiez votre boîte mail (et les spams).';
            } else {
                $erreur = '❌ Erreur lors de l\'envoi du mail.';
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
    <h2>🔑 Mot de passe oublié</h2>
    <p class="sub">Entrez votre identifiant — un lien sera envoyé par mail.</p>

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
        <button type="submit" class="btn btn-primary">Envoyer le lien</button>
      </form>
      <a href="login.php" class="back">← Retour à la connexion</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
