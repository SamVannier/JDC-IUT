<?php
$host = 'servinfo-maria';
$dbname = 'DBo22503962';
$user = 'o22503962';
$pass = 'o22503962';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Active les erreurs PDO pour faciliter le debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Renvoie les résultats sous forme de tableau associatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = "Erreur de connexion BDD : " . $e->getMessage();
    die(json_encode(["success" => false, "message" => $msg, "error" => $msg]));
}

function ensure_mdp_column(PDO $pdo): void {
    $pdo->exec("ALTER TABLE UTILISATEUR ADD COLUMN mdp VARCHAR(255) NOT NULL DEFAULT '' AFTER pseudo");
    $stmt = $pdo->prepare("UPDATE UTILISATEUR SET mdp = :hash WHERE pseudo = 'Shoto' AND mdp = ''");
    $stmt->execute([':hash' => '$2y$10$33nSnF3EuqZAjwS8m6tFOO83.R8PbHAig64ZK49UBrMTUxbfpSFYS']);
}
?>