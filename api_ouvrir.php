<?php
session_start();
header('Content-Type: application/json');

require_once 'includes/db.php';
require_once 'includes/algo.php';

// Vérification que l'utilisateur est bien connecté
if (!isset($_SESSION['id_user'])) {
    echo json_encode(["success" => false, "message" => "Non connecté."]);
    exit;
}

$id_user = $_SESSION['id_user'];

try {
    // 1. Tirer une carte aléatoire dans la table CARTE
    $stmt = $pdo->query("SELECT id_ref, type_c, nom, image_url FROM CARTE ORDER BY RAND() LIMIT 1");
    $carte_tiree = $stmt->fetch();

    if (!$carte_tiree) {
        echo json_encode(["success" => false, "message" => "Aucune carte dans la base."]);
        exit;
    }

    // 2. Générer les statistiques
    $type_carte = strtoupper($carte_tiree['type_c']); // S'assure que c'est bien SKIN, JOUEUR ou EQUIPE
    $variante = generer_variante($type_carte);
    
    $float_value = null;
    $etat = null;

    if ($type_carte === 'SKIN') {
        $float_value = mt_rand() / mt_getrandmax();
        $etat = determiner_etat($float_value);
    }

    // 3. Insérer la carte dans l'inventaire
    $sql_insert = "INSERT INTO INVENTAIRE (id_user, id_ref, variante_cosmetique, etat, float_value) 
                   VALUES (:id_user, :id_ref, :variante, :etat, :float_value)";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([
        ':id_user' => $id_user,
        ':id_ref' => $carte_tiree['id_ref'],
        ':variante' => $variante,
        ':etat' => $etat,
        ':float_value' => $float_value
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Vous avez obtenu : " . $carte_tiree['nom'],
        "details" => [
            "id_ref" => $carte_tiree['id_ref'],
            "nom" => $carte_tiree['nom'],
            "image" => $carte_tiree['image_url'],
            "type" => $type_carte,
            "variante" => $variante,
            "etat" => $etat,
            "float_value" => $float_value
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur SQL : " . $e->getMessage()]);
}
?>