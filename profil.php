<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/db.php';
require_once 'includes/algo.php';

// 1. Récupération des infos utilisateur à jour
$stmt_user = $pdo->prepare("SELECT * FROM UTILISATEUR WHERE id_user = :id");
$stmt_user->execute([':id' => $_SESSION['id_user']]);
$user_info = $stmt_user->fetch();

// Mise à jour de la session au cas où les crédits auraient changé
$_SESSION['solde_credit'] = $user_info['solde_credit'];
$_SESSION['pp'] = $user_info['pp'];

// Traitement de l'URL de la PP (ajout du dossier bd/ si c'est un chemin relatif)
$pp_url = !empty($user_info['pp']) ? $user_info['pp'] : 'https://via.placeholder.com/100';
if (strpos($pp_url, '/img/') === 0) {
    $pp_url = 'bd' . $pp_url;
}

// 2. Récupération de l'inventaire
$stmt_inv = $pdo->prepare("
    SELECT i.*, c.nom, c.image_url, c.type_c, cs.rarete
    FROM INVENTAIRE i
    JOIN CARTE c ON i.id_ref = c.id_ref
    LEFT JOIN CARTE_SKIN cs ON c.id_ref = cs.id_ref
    WHERE i.id_user = :id_user
    ORDER BY i.date_obtention DESC
");
$stmt_inv->execute([':id_user' => $_SESSION['id_user']]);
$cartes = $stmt_inv->fetchAll();
$total_cartes = count($cartes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil de <?= htmlspecialchars($user_info['pseudo']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1b2838; color: white; margin: 0; padding: 20px; }
        
        /* Navbar */
        .nav { display: flex; justify-content: space-between; align-items: center; background: #171a21; padding: 15px 30px; border-radius: 8px; margin-bottom: 30px; }
        .nav a { color: #66c0f4; text-decoration: none; font-weight: bold; margin-left: 15px;}
        .nav a:hover { color: #ffffff; }
        
        /* En-tête Profil */
        .profil-header { display: flex; align-items: center; background: #2a475e; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.4); }
        .profil-pp { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #66c0f4; margin-right: 20px; background: #171a21; }
        .profil-info h1 { margin: 0 0 10px 0; color: #66c0f4; }
        .profil-stats { color: #a1b4c4; font-size: 16px; }
        .profil-stats strong { color: white; }

        /* Grille d'inventaire */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .card { background: #2a475e; padding: 15px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.4); border: 2px solid #171a21; }
        .card img { max-width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
        .badge { display: inline-block; background: #171a21; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 2px; }
        .float-text { font-size: 11px; color: #a1b4c4; margin-top: 8px; display: block; }
    </style>
</head>
<body>
    <div class="nav">
        <div><strong>TCG CS</strong></div>
        <div>
            <a href="index.php">Ouvrir des boosters</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <!-- En-tête du profil -->
    <div class="profil-header">
        <img src="<?= htmlspecialchars($pp_url) ?>" alt="Photo de profil" class="profil-pp">
        <div class="profil-info">
            <h1><?= htmlspecialchars($user_info['pseudo']) ?></h1>
            <div class="profil-stats">
                <!-- AJOUT : de la class="solde-affichage" pour le JS -->
                <span>Solde : <strong class="solde-affichage"><?= htmlspecialchars($user_info['solde_credit']) ?> 🪙</strong></span> &nbsp;|&nbsp; 
                <span>Cartes possédées : <strong><?= $total_cartes ?></strong></span>
            </div>
        </div>
    </div>

    <h2>Mon Inventaire</h2>
    <div class="grid">
        <?php foreach ($cartes as $carte): ?>
            <?php 
                // Calcul du prix pour l'affichage
                $prix = calculer_prix_vente($carte['type_c'], $carte['variante_cosmetique'], $carte['etat'], $carte['rarete']); 
            ?>
            <!-- CORRECTION : Une seule div "card" propre -->
            <div class="card" id="carte-<?= $carte['id_instance'] ?>">
                <img src="<?= htmlspecialchars('bd' . $carte['image_url']) ?>" alt="Image carte">
                <h4><?= htmlspecialchars($carte['nom']) ?></h4>
                
                <div>
                    <span class="badge"><?= htmlspecialchars($carte['type_c']) ?></span>
                    <?php if ($carte['variante_cosmetique'] !== 'Normal'): ?>
                        <span class="badge" style="color: gold;"><?= htmlspecialchars($carte['variante_cosmetique']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($carte['type_c'] === 'Skin' && $carte['etat']): ?>
                    <span class="badge" style="background: #3c3d3e; margin-top: 8px;">
                        <?= htmlspecialchars($carte['etat']) ?>
                    </span>
                    <span class="float-text">Float: <?= number_format($carte['float_value'], 10) ?></span>
                <?php endif; ?>

                <!-- Bouton Vendre -->
                <div style="margin-top: 15px;">
                    <button class="btn-vendre" style="background-color: #d9534f; border: none; color: white; padding: 8px; border-radius: 4px; cursor: pointer; width: 100%;" 
                            onclick="vendreCarte(<?= $carte['id_instance'] ?>, '<?= htmlspecialchars(addslashes($carte['nom']), ENT_QUOTES) ?>')"
                        Vendre (<?= $prix ?> 🪙)
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- AJOUT : Le script JavaScript qui gère la vente sans recharger la page -->
    <script>
        async function vendreCarte(idInstance, nomCarte) {
            if (!confirm(`Es-tu sûr de vouloir vendre ${nomCarte} ?`)) return;

            try {
                const response = await fetch('api_vendre.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_instance: idInstance })
                });
                const data = await response.json();

                if (data.success) {
                    // Fait disparaître la carte
                    document.getElementById('carte-' + idInstance).style.display = 'none';
                    
                    // Met à jour l'affichage des crédits
                    document.querySelectorAll('.solde-affichage').forEach(el => {
                        el.innerHTML = data.nouveau_solde + ' 🪙';
                    });
                } else {
                    alert("Erreur : " + data.message);
                }
            } catch (error) {
                console.error(error);
                alert("Erreur de connexion au serveur.");
            }
        }
    </script>
</body>
</html>