<?php
// ============================================================
//  Connexion PDO partagée (login.php + api.php)
// ============================================================

// Code secret pour réinitialiser un mot de passe depuis la page de connexion
define('RECOVERY_CODE', 'pizza2026!');

// ---- Config Gmail pour envoi de mail ----
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'ccovindumez@gmail.com');
define('SMTP_PASS', 'glysntjypmntepca');      // mot de passe d'application Google
define('MAIL_DEST', 'cacdcapij@gmail.com');   // adresse qui reçoit les liens de reset

// ---- Envoi mail via Gmail SMTP (sans librairie externe) ----
function envoyerMail(string $sujet, string $corps): bool {
    $socket = @fsockopen('tcp://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 15);
    if (!$socket) return false;

    $lire = fn() => fgets($socket, 515);
    $ecrire = fn(string $cmd) => fputs($socket, $cmd . "\r\n");

    $lire(); // greeting
    $ecrire('EHLO localhost');
    while ($l = $lire()) { if ($l[3] === ' ') break; }

    $ecrire('STARTTLS');
    $lire();
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    $ecrire('EHLO localhost');
    while ($l = $lire()) { if ($l[3] === ' ') break; }

    $ecrire('AUTH LOGIN');
    $lire();
    $ecrire(base64_encode(SMTP_USER));
    $lire();
    $ecrire(base64_encode(SMTP_PASS));
    $rep = $lire();
    if (substr($rep, 0, 3) !== '235') { fclose($socket); return false; }

    $ecrire('MAIL FROM:<' . SMTP_USER . '>');
    $lire();
    $ecrire('RCPT TO:<' . MAIL_DEST . '>');
    $lire();
    $ecrire('DATA');
    $lire();

    $msg  = 'From: Fidélité Pizza <' . SMTP_USER . ">\r\n";
    $msg .= 'To: ' . MAIL_DEST . "\r\n";
    $msg .= 'Subject: =?UTF-8?B?' . base64_encode($sujet) . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "\r\n" . $corps . "\r\n.\r\n";
    fputs($socket, $msg);
    $lire();

    $ecrire('QUIT');
    fclose($socket);
    return true;
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'pizza_fidelite');
define('DB_USER', 'root');
define('DB_PASS', 'Acdc2702xy??2002');
define('DB_PORT', '3306');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
