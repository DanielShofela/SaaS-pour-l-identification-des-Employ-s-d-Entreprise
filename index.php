<?php
// Inclusion de la classe Database avec chemin absolu
require_once __DIR__ . '/config/database.php';

// Configuration de la durée de session
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
session_set_cookie_params(1800);

session_start();

// Vérifier si la session a expiré
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: index.php?expired=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Protection contre les tentatives de connexion multiples
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

// Réinitialiser le compteur si demandé
if (isset($_GET['reset_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
    header('Location: index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si l'utilisateur est bloqué
    $block_time = 300; // 5 minutes de blocage
    if ($_SESSION['login_attempts'] >= 3 && (time() - $_SESSION['last_attempt']) < $block_time) {
        $remaining_time = ceil(($block_time - (time() - $_SESSION['last_attempt'])) / 60);
        $error = "Trop de tentatives de connexion. Veuillez réessayer dans " . $remaining_time . " minutes ou <a href='index.php?reset_attempts=1' class='alert-link'>cliquez ici pour réinitialiser</a>.";
    } else {
        if ((time() - $_SESSION['last_attempt']) >= $block_time) {
            $_SESSION['login_attempts'] = 0;
        }
        
        if (isset($_POST['nom']) && isset($_POST['password'])) {
            $nom = $_POST['nom'];
            $password = $_POST['password'];

            try {
                $database = new Database();
                $pdo = $database->getConnection();
                
                $user = null;
                $userType = null;
                
                // Chercher d'abord dans la table entreprise
                $stmt = $pdo->prepare("SELECT * FROM entreprise WHERE nom = ?");
                $stmt->execute([$nom]);
                $user = $stmt->fetch();
                if ($user) {
                    $userType = 'entreprise';
                    // Les variables sont déjà correctement nommées dans la table entreprise
                }
                
                // Si non trouvé, chercher dans la table personne
                if (!$user) {
                    $stmt = $pdo->prepare("SELECT * FROM personne WHERE nom = ?");
                    $stmt->execute([$nom]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $userType = 'personne';
                    }
                }
                
                // Si toujours non trouvé, chercher dans la table admin
                if (!$user) {
                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE nom = ?");
                    $stmt->execute([$nom]);
                    $user = $stmt->fetch();
                    if ($user) {
                        $userType = 'admin';
                    }
                }
                
                if ($user && isset($user['password'])) {
                    $passwordMatch = false;
                    if ($userType === 'admin' || $userType === 'entreprise') {
                        // Pour admin et entreprise, utiliser MD5
                        $passwordMatch = (md5($password) === $user['password']);
                    } else {
                        // Pour les autres utilisateurs, utiliser password_verify
                        $passwordMatch = password_verify($password, $user['password']);
                    }
                    
                    if ($passwordMatch) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_type'] = $userType;
                        $_SESSION['nom'] = $user['nom'];
                        
                        // Redirection selon le type d'utilisateur
                        switch($userType) {
                            case 'admin':
                                header("Location: admin/dashboard.php");
                                break;
                            case 'entreprise':
                                header("Location: entreprise/");
                                break;
                            case 'personne':
                                header("Location: dashboard.php");
                                break;
                            default:
                                $error = "Type d'utilisateur non reconnu";
                                break;
                        }
                        exit();
                    } else {
                        $error = "Nom ou mot de passe incorrect";
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt'] = time();
                    }
                } else {
                    $error = "Nom ou mot de passe incorrect";
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                }
            } catch(PDOException $e) {
                $error = "Erreur de connexion: " . $e->getMessage();
            }
        } else {
            $error = "Veuillez saisir votre nom et votre mot de passe";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Connexion</h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Inscription réussie ! Vous pouvez maintenant vous connecter.</div>
            <?php endif; ?>

            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning">Votre session a expiré. Veuillez vous reconnecter.</div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>

            <div class="mt-3 text-center">
                <a href="inscription.php">Pas encore inscrit ? Créer un compte</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
