<?php
session_start();
if (isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$erreur = '';
$pseudo = '';
$pp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    $mdp_confirm = $_POST['mdp_confirm'] ?? '';
    $pp = trim($_POST['pp'] ?? '');

    $longueur_pseudo = strlen($pseudo);

    if ($longueur_pseudo < 3 || $longueur_pseudo > 15) {
        $erreur = "Le pseudo doit contenir entre 3 et 15 caractères.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $pseudo)) {
        $erreur = "Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.";
    } elseif (strlen($mdp) < 4) {
        $erreur = "Le mot de passe doit contenir au moins 4 caractères.";
    } elseif ($mdp !== $mdp_confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif ($pp !== '' && (strlen($pp) > 255 || (!filter_var($pp, FILTER_VALIDATE_URL) && strpos($pp, '/img/') !== 0))) {
        $erreur = "L'URL de la photo de profil est invalide.";
    } else {
        try {
            // Vérifier si le pseudo est déjà utilisé
            $stmt_check = $pdo->prepare("SELECT id_user FROM UTILISATEUR WHERE pseudo = :pseudo");
            $stmt_check->execute([':pseudo' => $pseudo]);

            if ($stmt_check->fetch()) {
                $erreur = "Ce pseudo est déjà pris.";
            } else {
                $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
                $pp_db = ($pp !== '') ? $pp : null;
                $credits_depart = 1000;

                $stmt_insert = $pdo->prepare("
                    INSERT INTO UTILISATEUR (pseudo, mdp, solde_credit, pp)
                    VALUES (:pseudo, :mdp, :solde, :pp)
                ");
                $params_insert = [
                    ':pseudo' => $pseudo,
                    ':mdp' => $mdp_hash,
                    ':solde' => $credits_depart,
                    ':pp' => $pp_db
                ];

                try {
                    $stmt_insert->execute($params_insert);
                } catch (PDOException $insertEx) {
                    if ($insertEx->getCode() === '42S22') {
                        ensure_mdp_column($pdo);
                        $stmt_insert->execute($params_insert);
                    } else {
                        throw $insertEx;
                    }
                }

                $nouvel_id = (int)$pdo->lastInsertId();

                // Connexion automatique après création du compte
                session_regenerate_id(true);
                $_SESSION['id_user'] = $nouvel_id;
                $_SESSION['pseudo'] = $pseudo;
                $_SESSION['solde_credit'] = $credits_depart;
                $_SESSION['pp'] = $pp_db;

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - TCG CS</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1b2838; color: white; text-align: center; padding-top: 80px; margin: 0; }
        .register-box { background: #2a475e; padding: 30px; width: 340px; margin: 0 auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); box-sizing: border-box; }
        .bonus-info { background: #171a21; color: #a1b4c4; font-size: 13px; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; }
        .bonus-info strong { color: #66c0f4; }
        input[type="text"], input[type="password"], input[type="url"] { width: 100%; padding: 10px; margin: 7px 0; border-radius: 5px; border: none; box-sizing: border-box; }
        button { background-color: #5c7e10; color: white; border: none; padding: 10px 20px; margin-top: 12px; font-size: 16px; border-radius: 5px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background-color: #79a317; }
        .error { color: #ff5252; margin-bottom: 10px; font-size: 14px; }
        .auth-link { margin-top: 18px; font-size: 14px; color: #a1b4c4; }
        .auth-link a { color: #66c0f4; text-decoration: none; font-weight: bold; }
        .auth-link a:hover { color: #ffffff; }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Créer un compte</h2>
        <div class="bonus-info">Bonus de bienvenue : <strong>1000 🪙</strong> offerts</div>
        <?php if ($erreur): ?>
            <div class="error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <input type="text" name="pseudo" placeholder="Pseudo (3 à 15 caractères)" value="<?= htmlspecialchars($pseudo) ?>" minlength="3" maxlength="15" required>
            <input type="password" name="mdp" placeholder="Mot de passe (4 caractères min.)" minlength="4" required>
            <input type="password" name="mdp_confirm" placeholder="Confirmer le mot de passe" minlength="4" required>
            <input type="text" name="pp" placeholder="URL photo de profil (optionnel)" value="<?= htmlspecialchars($pp) ?>" maxlength="255">
            <button type="submit">S'inscrire</button>
        </form>
        <div class="auth-link">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </div>
    </div>
</body>
</html>
