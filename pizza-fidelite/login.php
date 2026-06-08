<?php
session_start();
if (isset($_SESSION['pizza_auth'])) {
    header('Location: index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';
    $login = trim($_POST['login'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if ($login === '' || $mdp === '') {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = getDB()->prepare('SELECT mot_de_passe FROM utilisateurs WHERE login = :login');
        $stmt->execute([':login' => $login]);
        $row = $stmt->fetch();

        if ($row && password_verify($mdp, $row['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['pizza_auth']  = true;
            $_SESSION['pizza_login'] = $login;
            header('Location: index.php');
            exit;
        } else {
            $erreur = 'Identifiant ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🍕 Connexion — Fidélité Pizza</title>
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
    <h2>🔐 Connexion</h2>

    <?php if ($erreur): ?>
      <div class="erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <label for="login">Identifiant</label>
      <input type="text" id="login" name="login"
             value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
             autocomplete="username" autofocus required />

      <label for="mot_de_passe">Mot de passe</label>
      <input type="password" id="mot_de_passe" name="mot_de_passe"
             autocomplete="current-password" required />

      <button type="submit" class="btn-login">Se connecter</button>
    </form>

    <p class="hint">Accès réservé au personnel</p>
    <a href="forgot.php" class="back">🔑 Mot de passe oublié ?</a>
  </div>
</div>

</body>
</html>
