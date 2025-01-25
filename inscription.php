<?php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'personne';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Validation des champs communs
        if (empty($_POST['password']) || empty($_POST['confirm_password'])) {
            throw new Exception("Tous les champs sont obligatoires");
        }
        
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }
        
        // Traitement de l'image
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
                throw new Exception("Le type de fichier n'est pas autorisé. Utilisez JPG, PNG ou GIF.");
            }
            
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        }
        
        // Hash du mot de passe
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        if ($_POST['type'] === 'personne') {
            // Validation des champs spécifiques à la personne
            if (empty($_POST['nom']) || empty($_POST['prenoms']) || empty($_POST['email'])) {
                throw new Exception("Tous les champs sont obligatoires");
            }
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM personne WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Cette adresse email est déjà utilisée");
            }
            
            // Insertion dans la table personne
            $stmt = $pdo->prepare("INSERT INTO personne (nom, prenoms, email, password, photo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nom'],
                $_POST['prenoms'],
                $_POST['email'],
                $hashedPassword,
                $photo
            ]);
            
        } else {
            // Validation des champs spécifiques à l'entreprise
            if (empty($_POST['nom']) || empty($_POST['libelle'])) {
                throw new Exception("Tous les champs sont obligatoires");
            }
            
            // Vérifier si le nom existe déjà
            $stmt = $pdo->prepare("SELECT id FROM entreprise WHERE nom = ?");
            $stmt->execute([$_POST['nom']]);
            if ($stmt->fetch()) {
                throw new Exception("Ce nom d'entreprise est déjà utilisé");
            }
            
            // Insertion dans la table entreprise
            $stmt = $pdo->prepare("INSERT INTO entreprise (nom, libelle, password, photo) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nom'],
                $_POST['libelle'],
                $hashedPassword,
                $photo
            ]);
        }
        
        $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Inscription <?php echo $type === 'personne' ? 'Personnelle' : 'd\'Entreprise'; ?></h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                                <br>
                                <a href="index.php" class="alert-link">Cliquez ici pour vous connecter</a>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3 text-center">
                            <a href="?type=personne" class="btn <?php echo $type === 'personne' ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">Personne</a>
                            <a href="?type=entreprise" class="btn <?php echo $type === 'entreprise' ? 'btn-primary' : 'btn-outline-primary'; ?>">Entreprise</a>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                            
                            <?php if ($type === 'personne'): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label required">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="prenoms" class="form-label required">Prénoms</label>
                                        <input type="text" class="form-control" id="prenoms" name="prenoms" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label required">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="nom" class="form-label required">Nom de l'entreprise</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                </div>
                                <div class="mb-3">
                                    <label for="libelle" class="form-label required">Libellé</label>
                                    <input type="text" class="form-control" id="libelle" name="libelle" required>
                                </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label required">Mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label required">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Photo de profil</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*" onchange="previewImage(this)">
                                <div id="imagePreview"></div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">S'inscrire</button>
                                <a href="index.php" class="btn btn-outline-secondary">Retour à la connexion</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prévisualisation de l'image
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('preview-image');
                    preview.appendChild(img);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Validation du formulaire
        document.querySelector('form').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });

        // Vérification des mots de passe
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
