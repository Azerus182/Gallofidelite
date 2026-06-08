<?php
// ============================================================
//  API REST — Fidélité Pizza
//  Requiert PHP 7.4+ et l'extension PDO MySQL
// ============================================================

require_once 'db.php';

// ---- Headers CORS + JSON ----
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ---- Routeur simple ----
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ("$method:$action") {

        // GET /api.php?action=liste
        case 'GET:liste':
            $rows = getDB()
                ->query('SELECT id, prenom, nom, ville, telephone, points, dernier_passage FROM clients ORDER BY nom, prenom')
                ->fetchAll();
            foreach ($rows as &$r) {
                $r['id']     = (int)$r['id'];
                $r['points'] = (int)$r['points'];
            }
            json_ok($rows);
            break;

        // POST /api.php?action=ajouter   body: { prenom, nom, ville, telephone }
        case 'POST:ajouter':
            $body      = json_body();
            $prenom    = trim($body['prenom']    ?? '');
            $nom       = trim($body['nom']       ?? '');
            $ville     = trim($body['ville']     ?? '');
            $telephone = trim($body['telephone'] ?? '');
            if ($prenom === '' || $nom === '') json_err('Prénom et nom requis.', 400);

            $stmt = getDB()->prepare(
                'INSERT INTO clients (prenom, nom, ville, telephone) VALUES (:prenom, :nom, :ville, :telephone)'
            );
            $stmt->execute([':prenom' => $prenom, ':nom' => $nom, ':ville' => $ville, ':telephone' => $telephone]);
            $id = (int)getDB()->lastInsertId();
            json_ok(['id' => $id, 'prenom' => $prenom, 'nom' => $nom, 'ville' => $ville, 'telephone' => $telephone, 'points' => 0, 'dernier_passage' => null]);
            break;

        // POST /api.php?action=achat   body: { id }
        case 'POST:achat':
            $body = json_body();
            $id   = (int)($body['id'] ?? 0);
            if ($id <= 0) json_err('ID invalide.', 400);

            $db  = getDB();
            $now = date('Y-m-d H:i:s');
            $db->beginTransaction();

            $db->prepare('UPDATE clients SET points = points + 1, dernier_passage = :now WHERE id = :id')
               ->execute([':now' => $now, ':id' => $id]);

            $db->prepare("INSERT INTO transactions (client_id, type) VALUES (:id, 'achat')")
               ->execute([':id' => $id]);

            $points = (int)$db->query("SELECT points FROM clients WHERE id = $id")->fetchColumn();
            $db->commit();

            json_ok(['id' => $id, 'points' => $points, 'dernier_passage' => $now]);
            break;

        // POST /api.php?action=offerte   body: { id }
        case 'POST:offerte':
            $body = json_body();
            $id   = (int)($body['id'] ?? 0);
            if ($id <= 0) json_err('ID invalide.', 400);

            $db  = getDB();
            $now = date('Y-m-d H:i:s');
            $db->beginTransaction();

            $points = (int)$db->query("SELECT points FROM clients WHERE id = $id FOR UPDATE")
                               ->fetchColumn();
            if ($points < 10) json_err('Pas assez de points.', 400);

            $db->prepare('UPDATE clients SET points = points - 10, dernier_passage = :now WHERE id = :id')
               ->execute([':now' => $now, ':id' => $id]);

            $db->prepare("INSERT INTO transactions (client_id, type) VALUES (:id, 'offerte')")
               ->execute([':id' => $id]);

            $newPoints = $points - 10;
            $db->commit();

            json_ok(['id' => $id, 'points' => $newPoints, 'dernier_passage' => $now]);
            break;

        // POST /api.php?action=modifier-points   body: { id, points }
        case 'POST:modifier-points':
            $body   = json_body();
            $id     = (int)($body['id'] ?? 0);
            $points = (int)($body['points'] ?? 0);
            if ($id <= 0) json_err('ID invalide.', 400);

            $db  = getDB();
            $now = date('Y-m-d H:i:s');
            $db->beginTransaction();

            $current = (int)$db->query("SELECT points FROM clients WHERE id = $id")->fetchColumn();
            $newPts  = max(0, $current + $points);  // pas de points négatifs

            $db->prepare('UPDATE clients SET points = :pts, dernier_passage = :now WHERE id = :id')
               ->execute([':pts' => $newPts, ':now' => $now, ':id' => $id]);

            $db->prepare("INSERT INTO transactions (client_id, type) VALUES (:id, 'achat')")
               ->execute([':id' => $id]);

            $db->commit();

            json_ok(['id' => $id, 'points' => $newPts, 'dernier_passage' => $now]);
            break;

        // DELETE /api.php?action=supprimer&id=X
        case 'DELETE:supprimer':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_err('ID invalide.', 400);

            getDB()->prepare('DELETE FROM clients WHERE id = :id')
                   ->execute([':id' => $id]);
            json_ok(['supprime' => true]);
            break;

        default:
            json_err('Action inconnue.', 404);
    }

} catch (PDOException $e) {
    // Ne pas exposer le message brut en production
    error_log($e->getMessage());
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        json_err('Ce client existe déjà.', 409);
    }
    json_err('Erreur base de données.', 500);
}

// ---- Helpers ----
function json_ok(mixed $data): never {
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw ?: '{}', true) ?? [];
}
