<?php
session_start();
header('Content-Type: application/json');

require_once 'includes/db.php';
require_once 'includes/algo.php';

if (!isset($_SESSION['id_user'])) {
    echo json_encode(["success" => false, "message" => "Non connecté."]);
    exit;
}

$id_user = $_SESSION['id_user'];
$data = json_decode(file_get_contents("php://input"));
$id_instance = $data->id_instance ?? null;

if (!$id_instance) {
    echo json_encode(["success" => false, "message" => "ID de carte manquant."]);
    exit;
}

try {
    // 1. Vérifier que la carte appartient bien au joueur et récupérer ses stats avec un LEFT JOIN sur CARTE_SKIN
    $stmt = $pdo->prepare("
        SELECT i.*, c.type_c, cs.rarete 
        FROM INVENTAIRE i
        JOIN CARTE c ON i.id_ref = c.id_ref
        LEFT JOIN CARTE_SKIN cs ON c.id_ref = cs.id_ref
        WHERE i.id_instance = :id_instance AND i.id_user = :id_user
    ");
    $stmt->execute([
        ':id_instance' => $id_instance, 
        ':id_user' => $id_user
    ]);
    $carte = $stmt->fetch();

    if (!$carte) {
        echo json_encode(["success" => false, "message" => "Cette carte ne vous appartient pas ou n'existe plus."]);
        exit;
    }

    // 2. Calculer sa valeur serveur (Sécurité)
    $prix_vente = calculer_prix_vente($carte['type_c'], $carte['variante_cosmetique'], $carte['etat'], $carte['rarete']);

    // 3. Transaction : Vendre la carte = Supprimer de l'inventaire + Ajouter les crédits
    $pdo->beginTransaction();

    $stmt_delete = $pdo->prepare("DELETE FROM INVENTAIRE WHERE id_instance = :id_instance");
    $stmt_delete->execute([':id_instance' => $id_instance]);

    $stmt_credit = $pdo->prepare("UPDATE UTILISATEUR SET solde_credit = solde_credit + :prix WHERE id_user = :id_user");
    $stmt_credit->execute([':prix' => $prix_vente, ':id_user' => $id_user]);

    $pdo->commit();

    // Mettre à jour la session
    $_SESSION['solde_credit'] += $prix_vente;

    echo json_encode([
        "success" => true, 
        "nouveau_solde" => $_SESSION['solde_credit'],
        "prix_vendu" => $prix_vente
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "message" => "Erreur BDD : " . $e->getMessage()]);
}
?>