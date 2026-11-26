<?php
session_start();
if (isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$erreur = '';
$pseudo_saisi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo_saisi = trim($_POST['pseudo'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    
    // Recherche de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM UTILISATEUR WHERE pseudo = :pseudo");
    $stmt->execute([':pseudo' => $pseudo_saisi]);
    $user = $stmt->fetch();

    if ($user && !array_key_exists('mdp', $user)) {
        ensure_mdp_column($pdo);
        $stmt->execute([':pseudo' => $pseudo_saisi]);
        $user = $stmt->fetch();
    }

    if ($user && password_verify($mdp, $user['mdp'])) {
        session_regenerate_id(true);
        // Enregistrement des données dans la session serveur
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['solde_credit'] = $user['solde_credit'];
        $_SESSION['pp'] = $user['pp'];
        
        header('Location: index.php'); // Redirection vers l'ouverture de boosters
        exit;
    } else {
        $erreur = "Identifiants incorrects. (Compte test : Shoto / shoto123)";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TCG CS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1b2838; color: white; text-align: center; padding-top: 100px; margin: 0; }
        .login-box { background: #2a475e; padding: 30px; width: 320px; margin: 0 auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); box-sizing: border-box; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: none; box-sizing: border-box; }
        button { background-color: #66c0f4; color: white; border: none; padding: 10px 20px; margin-top: 10px; font-size: 16px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background-color: #4199c8; }
        .error { color: #ff5252; margin-bottom: 10px; font-size: 14px; }
        .auth-link { margin-top: 18px; font-size: 14px; color: #a1b4c4; }
        .auth-link a { color: #66c0f4; text-decoration: none; font-weight: bold; }
        .auth-link a:hover { color: #ffffff; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Connexion</h2>
        <?php if ($erreur): ?>
            <div class="error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="pseudo" placeholder="Entrez votre pseudo" value="<?= htmlspecialchars($pseudo_saisi) ?>" maxlength="15" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <div class="auth-link">
            Pas encore de compte ? <a href="register.php">Créer un compte</a>
        </div>
    </div>
</body>
</html>