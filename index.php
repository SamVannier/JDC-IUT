<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCG Counter-Strike - Ouverture</title>
    <style>
        body { font-family: Georgia, sans-serif; background-color: #1b2838; color: white; margin: 0; padding: 20px; }
        
        /* Navbar */
        .nav { display: flex; justify-content: space-between; align-items: center; background: #171a21; padding: 15px 30px; border-radius: 8px; margin-bottom: 30px; }
        .nav a { color: #66c0f4; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .nav a:hover { color: #ffffff; }

        /* Conteneur principal */
        .container { text-align: center; }
        .booster-container { margin: 20px auto; padding: 20px; border: 2px dashed #66c0f4; width: 300px; border-radius: 10px; }
        button { background-color: #5c7e10; color: white; border: none; padding: 15px 30px; font-size: 18px; cursor: pointer; border-radius: 5px; }
        button:hover { background-color: #79a317; }
        .card-result { margin: 20px auto; padding: 15px; background: #2a475e; border-radius: 10px; width: 300px; display: none; }
        .card-result img { max-width: 100%; height: auto; }
        .stat-badge { display: inline-block; background: #171a21; padding: 5px 10px; margin: 5px; border-radius: 5px; font-size: 14px; }
    </style>
</head>
<body>

    <div class="nav">
        <div><strong>TCG CS</strong></div>
        <div>
            <span style="margin-right: 15px; color: #a1b4c4;">Solde: <span id="solde-affichage"><?= htmlspecialchars((string)($_SESSION['solde_credit'] ?? 0)) ?></span> 🪙</span>
            <a href="profil.php">Mon Profil</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <h1>Ouverture de Booster</h1>

        <div class="booster-container">
            <button id="btn-ouvrir">Ouvrir (100 crédits)</button>
        </div>

        <div id="resultat" class="card-result">
            <h2 id="carte-nom">...</h2>
            <img id="carte-img" src="" alt="Image de la carte">
            
            <div>
                <span class="stat-badge" id="carte-type"></span>
                <span class="stat-badge" id="carte-variante"></span>
            </div>
            
            <div id="zone-skin" style="display: none;">
                <span class="stat-badge" id="carte-etat"></span>
                <span class="stat-badge">Float: <span id="carte-float"></span></span>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('btn-ouvrir').addEventListener('click', async () => {
            const btn = document.getElementById('btn-ouvrir');
            const resultDiv = document.getElementById('resultat');
            
            btn.disabled = true;
            btn.innerText = "Ouverture en cours...";

            try {
                const response = await fetch('api_ouvrir.php');
                const data = await response.json();

                if (data.success) {
                    const details = data.details;
                    document.getElementById('carte-nom').innerText = details.nom;
                    document.getElementById('carte-img').src = 'bd' + details.image;
                    document.getElementById('carte-type').innerText = "Type: " + details.type;
                    document.getElementById('carte-variante').innerText = details.variante;

                    const zoneSkin = document.getElementById('zone-skin');
                    if (details.type === 'SKIN') {
                        document.getElementById('carte-etat').innerText = "État: " + details.etat;
                        document.getElementById('carte-float').innerText = parseFloat(details.float_value).toFixed(10);
                        zoneSkin.style.display = 'block';
                    } else {
                        zoneSkin.style.display = 'none';
                    }

                    resultDiv.style.display = 'block';
                    if (data.nouveau_solde !== undefined) {
                        document.getElementById('solde-affichage').innerText = data.nouveau_solde;
                    }
                } else {
                    alert("Erreur : " + data.message);
                }
            } catch (error) {
                console.error("Erreur :", error);
                alert("Impossible de joindre le serveur.");
            } finally {
                btn.disabled = false;
                btn.innerText = "Ouvrir (100 crédits)";
            }
        });
    </script>
</body>
</html>