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
$cout_booster = 100;

try {
    $pdo->beginTransaction();

    // 1. Vérifier le solde du joueur (verrouillage de la ligne)
    $stmt_user = $pdo->prepare("SELECT solde_credit FROM UTILISATEUR WHERE id_user = :id_user FOR UPDATE");
    $stmt_user->execute([':id_user' => $id_user]);
    $user = $stmt_user->fetch();

    if (!$user) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Utilisateur introuvable."]);
        exit;
    }

    if ($user['solde_credit'] < $cout_booster) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Crédits insuffisants (100 crédits requis)."]);
        exit;
    }

    // 2. Tirer une carte aléatoire dans la table CARTE
    $stmt = $pdo->query("SELECT id_ref, type_c, nom, image_url FROM CARTE ORDER BY RAND() LIMIT 1");
    $carte_tiree = $stmt->fetch();

    if (!$carte_tiree) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Aucune carte dans la base."]);
        exit;
    }

    // 3. Déduire le coût du booster
    $stmt_debit = $pdo->prepare("UPDATE UTILISATEUR SET solde_credit = solde_credit - :cout WHERE id_user = :id_user");
    $stmt_debit->execute([
        ':cout' => $cout_booster,
        ':id_user' => $id_user
    ]);
    $nouveau_solde = (int)$user['solde_credit'] - $cout_booster;

    // 4. Générer les statistiques
    $type_carte = strtoupper($carte_tiree['type_c']); // S'assure que c'est bien SKIN, JOUEUR ou EQUIPE
    $variante = generer_variante($type_carte);
    
    $float_value = null;
    $etat = null;

    if ($type_carte === 'SKIN') {
        $float_value = mt_rand() / mt_getrandmax();
        $etat = determiner_etat($float_value);
    }

    // 5. Insérer la carte dans l'inventaire
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

    $pdo->commit();

    $_SESSION['solde_credit'] = $nouveau_solde;

    echo json_encode([
        "success" => true,
        "message" => "Vous avez obtenu : " . $carte_tiree['nom'],
        "nouveau_solde" => $nouveau_solde,
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Erreur SQL : " . $e->getMessage()]);
}
?>