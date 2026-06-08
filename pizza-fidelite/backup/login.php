<?php
session_start();

// Déjà connecté → redirection directe
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
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.52);
      z-index: 0;
    }

    header {
      position: relative;
      z-index: 1;
      background: #e63012;
      color: white;
      padding: 20px 32px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    header h1 { font-size: 1.7rem; font-weight: 700; }
    header span { font-size: 2rem; }

    .wrap {
      position: relative;
      z-index: 1;
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
      max-width: 400px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.22);
    }

    .card h2 {
      text-align: center;
      font-size: 1.4rem;
      color: #e63012;
      margin-bottom: 28px;
    }

    label {
      display: block;
      font-size: .875rem;
      font-weight: 600;
      color: #555;
      margin-bottom: 6px;
      margin-top: 16px;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 11px 14px;
      border: 2px solid #e0d8d0;
      border-radius: 8px;
      font-size: 1rem;
      transition: border-color .2s;
    }
    input:focus {
      outline: none;
      border-color: #e63012;
    }

    .erreur {
      background: #fdecea;
      color: #c0392b;
      border: 1px solid #f5c6cb;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: .9rem;
      margin-bottom: 8px;
      text-align: center;
    }

    .btn-login {
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
      transition: background .15s;
    }
    .btn-login:hover { background: #c4260e; }
    .btn-login:active { transform: scale(.98); }

    .hint {
      text-align: center;
      font-size: .8rem;
      color: #aaa;
      margin-top: 16px;
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
    <a href="forgot.php" style="display:block; text-align:center; margin-top:14px; color:#e63012; font-size:.85rem; text-decoration:none;">🔑 Mot de passe oublié ?</a>
  </div>
</div>

</body>
</html>
