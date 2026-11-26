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

if (!$user_info) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Mise à jour de la session au cas où les crédits auraient changé
$_SESSION['solde_credit'] = $user_info['solde_credit'];
$_SESSION['pp'] = $user_info['pp'];

// Traitement de l'URL de la PP (ajout du dossier bd/ si c'est un chemin relatif)
$default_pp = "data:image/svg+xml;utf8," . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="#171a21"/><circle cx="50" cy="38" r="18" fill="#66c0f4"/><path d="M20 88c0-17 13-28 30-28s30 11 30 28" fill="#66c0f4"/></svg>');
$pp_url = !empty($user_info['pp']) ? $user_info['pp'] : $default_pp;
if (strpos($pp_url, '/img/') === 0) {
    $local_pp = 'bd' . $pp_url;
    $pp_url = is_file(__DIR__ . '/' . $local_pp) ? $local_pp : $default_pp;
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
$doublons = identifier_doublons($cartes);
$est_doublon_map = array_flip($doublons['ids_instances']);
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

        /* En-tête Inventaire + bouton doublons */
        .inventaire-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
        .inventaire-header h2 { margin: 0; }
        .btn-doublons { background-color: #e67e22; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; }
        .btn-doublons:hover:not(:disabled) { background-color: #d35400; }
        .btn-doublons:disabled { background-color: #3c4f5e; color: #8fa3b3; cursor: not-allowed; }

        /* Grille d'inventaire */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .card { background: #2a475e; padding: 15px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.4); border: 2px solid #171a21; }
        .card img { max-width: 100%; height: 150px; object-fit: contain; margin-bottom: 10px; }
        .badge { display: inline-block; background: #171a21; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 2px; }
        .badge-doublon { background: #e67e22; color: white; font-weight: bold; }
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
                <span>Solde : <strong class="solde-affichage"><?= htmlspecialchars((string)$user_info['solde_credit']) ?> 🪙</strong></span> &nbsp;|&nbsp; 
                <span>Cartes possédées : <strong id="total-cartes"><?= $total_cartes ?></strong></span>
            </div>
        </div>
    </div>

    <div class="inventaire-header">
        <h2>Mon Inventaire</h2>
        <button id="btn-vendre-doublons" class="btn-doublons"
                onclick="vendreDoublons()"
                data-nb="<?= $doublons['nb_doublons'] ?>"
                data-gain="<?= $doublons['gain_total'] ?>"
                <?= $doublons['nb_doublons'] === 0 ? 'disabled' : '' ?>>
            <?php if ($doublons['nb_doublons'] > 0): ?>
                Vendre les doublons (<?= $doublons['nb_doublons'] ?> — +<?= $doublons['gain_total'] ?> 🪙)
            <?php else: ?>
                Aucun doublon à vendre
            <?php endif; ?>
        </button>
    </div>

    <div class="grid">
        <?php foreach ($cartes as $carte): ?>
            <?php 
                // Calcul du prix pour l'affichage
                $prix = calculer_prix_vente($carte['type_c'], $carte['variante_cosmetique'], $carte['etat'], $carte['rarete']);
                $id_inst = (int)$carte['id_instance'];
                $est_doublon = isset($est_doublon_map[$id_inst]);
                $groupe_cle = $carte['id_ref'] . '|' . $carte['variante_cosmetique'];
            ?>
            <!-- CORRECTION : Une seule div "card" propre -->
            <div class="card"
                 id="carte-<?= $id_inst ?>"
                 data-instance="<?= $id_inst ?>"
                 data-group="<?= htmlspecialchars($groupe_cle) ?>"
                 data-prix="<?= $prix ?>"
                 data-float="<?= $carte['float_value'] !== null ? htmlspecialchars((string)$carte['float_value']) : '' ?>">
                <img src="<?= htmlspecialchars('bd' . $carte['image_url']) ?>" alt="Image carte">
                <h4><?= htmlspecialchars($carte['nom']) ?></h4>
                
                <div>
                    <span class="badge"><?= htmlspecialchars($carte['type_c']) ?></span>
                    <?php if ($carte['variante_cosmetique'] !== 'Normal'): ?>
                        <span class="badge" style="color: gold;"><?= htmlspecialchars($carte['variante_cosmetique']) ?></span>
                    <?php endif; ?>
                    <span class="badge badge-doublon" style="<?= $est_doublon ? '' : 'display: none;' ?>">Doublon</span>
                </div>

                <?php if (strtoupper($carte['type_c']) === 'SKIN' && $carte['etat']): ?>
                    <span class="badge" style="background: #3c3d3e; margin-top: 8px;">
                        <?= htmlspecialchars($carte['etat']) ?>
                    </span>
                    <span class="float-text">Float: <?= number_format((float)$carte['float_value'], 10) ?></span>
                <?php endif; ?>

                <!-- Bouton Vendre -->
                <div style="margin-top: 15px;">
                    <button class="btn-vendre" style="background-color: #d9534f; border: none; color: white; padding: 8px; border-radius: 4px; cursor: pointer; width: 100%;" 
                            onclick="vendreCarte(<?= $id_inst ?>, <?= htmlspecialchars(json_encode($carte['nom']), ENT_QUOTES, 'UTF-8') ?>)">
                        Vendre (<?= $prix ?> 🪙)
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- AJOUT : Le script JavaScript qui gère la vente sans recharger la page -->
    <script>
        function recalculerDoublonsUI() {
            const groupes = {};
            document.querySelectorAll('.card').forEach(el => {
                const grp = el.dataset.group;
                if (!groupes[grp]) groupes[grp] = [];
                groupes[grp].push({
                    el,
                    instance: Number(el.dataset.instance),
                    prix: Number(el.dataset.prix),
                    floatVal: el.dataset.float !== '' ? Number(el.dataset.float) : null
                });
            });

            let nbDoublons = 0;
            let gainTotal = 0;

            Object.values(groupes).forEach(liste => {
                liste.sort((a, b) => {
                    if (b.prix !== a.prix) return b.prix - a.prix;
                    if (a.floatVal !== null && b.floatVal !== null && a.floatVal !== b.floatVal) {
                        return a.floatVal - b.floatVal;
                    }
                    return a.instance - b.instance;
                });

                liste.forEach((item, index) => {
                    const badge = item.el.querySelector('.badge-doublon');
                    if (index === 0) {
                        if (badge) badge.style.display = 'none';
                    } else {
                        if (badge) badge.style.display = 'inline-block';
                        nbDoublons++;
                        gainTotal += item.prix;
                    }
                });
            });

            const btnDoublons = document.getElementById('btn-vendre-doublons');
            if (btnDoublons) {
                btnDoublons.dataset.nb = nbDoublons;
                btnDoublons.dataset.gain = gainTotal;
                if (nbDoublons > 0) {
                    btnDoublons.disabled = false;
                    btnDoublons.textContent = `Vendre les doublons (${nbDoublons} — +${gainTotal} 🪙)`;
                } else {
                    btnDoublons.disabled = true;
                    btnDoublons.textContent = 'Aucun doublon à vendre';
                }
            }
        }

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
                    // Supprime la carte du DOM et met à jour le compteur
                    const carteEl = document.getElementById('carte-' + idInstance);
                    if (carteEl) {
                        carteEl.remove();
                    }
                    const totalEl = document.getElementById('total-cartes');
                    if (totalEl) {
                        totalEl.textContent = document.querySelectorAll('.card').length;
                    }
                    
                    // Met à jour l'affichage des crédits et l'état des doublons
                    document.querySelectorAll('.solde-affichage').forEach(el => {
                        el.textContent = data.nouveau_solde + ' 🪙';
                    });
                    recalculerDoublonsUI();
                } else {
                    alert("Erreur : " + data.message);
                }
            } catch (error) {
                console.error(error);
                alert("Erreur de connexion au serveur.");
            }
        }

        async function vendreDoublons() {
            const btn = document.getElementById('btn-vendre-doublons');
            const nb = Number(btn.dataset.nb || 0);
            const gain = Number(btn.dataset.gain || 0);
            if (nb <= 0) return;

            if (!confirm(`Es-tu sûr de vouloir vendre ${nb} doublon(s) pour +${gain} 🪙 ?\n(Le meilleur exemplaire de chaque carte + variante sera conservé.)`)) {
                return;
            }

            btn.disabled = true;

            try {
                const response = await fetch('api_vendre_doublons.php', {
                    method: 'POST'
                });
                const data = await response.json();

                if (data.success) {
                    (data.ids_vendus || []).forEach(idInstance => {
                        const carteEl = document.getElementById('carte-' + idInstance);
                        if (carteEl) {
                            carteEl.remove();
                        }
                    });

                    const totalEl = document.getElementById('total-cartes');
                    if (totalEl) {
                        totalEl.textContent = document.querySelectorAll('.card').length;
                    }

                    document.querySelectorAll('.solde-affichage').forEach(el => {
                        el.textContent = data.nouveau_solde + ' 🪙';
                    });

                    recalculerDoublonsUI();
                } else {
                    alert("Erreur : " + data.message);
                    recalculerDoublonsUI();
                }
            } catch (error) {
                console.error(error);
                alert("Erreur de connexion au serveur.");
                recalculerDoublonsUI();
            }
        }
    </script>
</body>
</html>