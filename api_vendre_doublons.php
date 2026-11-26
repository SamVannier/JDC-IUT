<?php
session_start();
header('Content-Type: application/json');

require_once 'includes/db.php';
require_once 'includes/algo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
    exit;
}

if (!isset($_SESSION['id_user'])) {
    echo json_encode(["success" => false, "message" => "Non connecté."]);
    exit;
}

$id_user = $_SESSION['id_user'];

try {
    $pdo->beginTransaction();

    // 1. Récupérer et verrouiller tout l'inventaire du joueur
    $stmt = $pdo->prepare("
        SELECT i.*, c.type_c, cs.rarete
        FROM INVENTAIRE i
        JOIN CARTE c ON i.id_ref = c.id_ref
        LEFT JOIN CARTE_SKIN cs ON c.id_ref = cs.id_ref
        WHERE i.id_user = :id_user
        FOR UPDATE
    ");
    $stmt->execute([':id_user' => $id_user]);
    $cartes = $stmt->fetchAll();

    // 2. Identifier les doublons (même carte + même variante cosmétique)
    $doublons = identifier_doublons($cartes);

    if ($doublons['nb_doublons'] === 0) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Aucun doublon à vendre."]);
        exit;
    }

    $ids_a_supprimer = $doublons['ids_instances'];
    $gain_total = $doublons['gain_total'];

    // 3. Supprimer les doublons et créditer l'utilisateur
    $placeholders = implode(',', array_fill(0, count($ids_a_supprimer), '?'));
    $stmt_delete = $pdo->prepare("DELETE FROM INVENTAIRE WHERE id_user = ? AND id_instance IN ($placeholders)");
    $stmt_delete->execute(array_merge([$id_user], $ids_a_supprimer));

    $stmt_credit = $pdo->prepare("UPDATE UTILISATEUR SET solde_credit = solde_credit + :gain WHERE id_user = :id_user");
    $stmt_credit->execute([
        ':gain' => $gain_total,
        ':id_user' => $id_user
    ]);

    $stmt_solde = $pdo->prepare("SELECT solde_credit FROM UTILISATEUR WHERE id_user = :id_user");
    $stmt_solde->execute([':id_user' => $id_user]);
    $nouveau_solde = (int)$stmt_solde->fetchColumn();

    $pdo->commit();

    $_SESSION['solde_credit'] = $nouveau_solde;

    echo json_encode([
        "success" => true,
        "nb_vendues" => $doublons['nb_doublons'],
        "gain_total" => $gain_total,
        "nouveau_solde" => $nouveau_solde,
        "ids_vendus" => $ids_a_supprimer
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Erreur BDD : " . $e->getMessage()]);
}
?>
