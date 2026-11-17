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
    die(json_encode(["error" => "Erreur de connexion BDD : " . $e->getMessage()]));
}
?>