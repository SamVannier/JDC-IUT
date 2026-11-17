<?php
session_start();
require_once 'includes/db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo']);
    
    // Recherche de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM UTILISATEUR WHERE pseudo = :pseudo");
    $stmt->execute([':pseudo' => $pseudo]);
    $user = $stmt->fetch();

    if ($user) {
        // Enregistrement des données dans la session serveur
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['solde_credit'] = $user['solde_credit'];
        $_SESSION['pp'] = $user['pp'];
        
        header('Location: index.php'); // Redirection vers l'ouverture de boosters
        exit;
    } else {
        $erreur = "Pseudo introuvable. (Essaie 'Shoto')";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - TCG CS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1b2838; color: white; text-align: center; padding-top: 100px; }
        .login-box { background: #2a475e; padding: 30px; width: 300px; margin: 0 auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
        input[type="text"] { width: 90%; padding: 10px; margin: 10px 0; border-radius: 5px; border: none; }
        button { background-color: #66c0f4; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 5px; cursor: pointer; width: 100%; }
        button:hover { background-color: #4199c8; }
        .error { color: #ff5252; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Connexion</h2>
        <?php if ($erreur): ?>
            <div class="error"><?= $erreur ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="pseudo" placeholder="Entrez votre pseudo" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>