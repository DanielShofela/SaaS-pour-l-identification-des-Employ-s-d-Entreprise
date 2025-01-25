<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Fonction pour convertir le BLOB en image base64
function getImageBase64($blob) {
    if ($blob) {
        return 'data:image/jpeg;base64,' . base64_encode($blob);
    }
    return '../assets/img/default-company.png'; // Image par défaut
}

// Générer un avatar aléatoire pour l'admin
$seed = $_SESSION['user_id'] ?? rand(1, 1000);
$avatar_url = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $seed;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $nom = $_POST['nom'];
            $libelle = $_POST['libelle'];
            $password = md5($_POST['password']); // Utilisation de MD5
            
            // Traitement de l'image
            $photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
            }
            
            $stmt = $pdo->prepare("INSERT INTO entreprise (nom, libelle, photo, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $libelle, $photo, $password]);
            
            header('Location: entreprises.php?success=1');
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $nom = $_POST['nom'];
            $libelle = $_POST['libelle'];
            
            // Si un nouveau mot de passe est fourni
            if (!empty($_POST['password'])) {
                $password = md5($_POST['password']); // Utilisation de MD5
                
                // Traitement de l'image avec nouveau mot de passe
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo = file_get_contents($_FILES['photo']['tmp_name']);
                    $stmt = $pdo->prepare("UPDATE entreprise SET nom = ?, libelle = ?, photo = ?, password = ? WHERE id = ?");
                    $stmt->execute([$nom, $libelle, $photo, $password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE entreprise SET nom = ?, libelle = ?, password = ? WHERE id = ?");
                    $stmt->execute([$nom, $libelle, $password, $id]);
                }
            } else {
                // Sans changement de mot de passe
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo = file_get_contents($_FILES['photo']['tmp_name']);
                    $stmt = $pdo->prepare("UPDATE entreprise SET nom = ?, libelle = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$nom, $libelle, $photo, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE entreprise SET nom = ?, libelle = ? WHERE id = ?");
                    $stmt->execute([$nom, $libelle, $id]);
                }
            }
            
            header('Location: entreprises.php?success=2');
            exit();
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            // Vérifier si l'entreprise a des employés
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM personne WHERE entreprise_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                header('Location: entreprises.php?error=1');
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM entreprise WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: entreprises.php?success=3');
            exit();
        }
    }

    // Récupérer la liste des entreprises
    $stmt = $pdo->query("SELECT * FROM entreprise ORDER BY nom");
    $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Une erreur est survenue : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Entreprises - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }
        
        .sidebar {
            background-color: var(--primary-color);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: var(--accent-color);
            padding-left: 25px;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .top-bar {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 0;
        }
        .nav-link:hover {
            color: white;
        }
        .nav-link.active {
            color: white;
            font-weight: bold;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .company-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }
        .company-card {
            transition: transform 0.2s;
        }
        .company-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>
            <a class="nav-link active" href="entreprises.php">
                <i class="bi bi-building"></i> Gestion Entreprises
            </a>
            <a class="nav-link" href="communautes.php">
                <i class="bi bi-people"></i> Communautés Locales
            </a>
            <a class="nav-link text-danger" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </div>

    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Gestion des Entreprises</h1>
            <div class="user-info">
                <span>Admin</span>
                <img src="<?php echo $avatar_url; ?>" alt="Avatar" class="avatar">
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 1:
                        echo "L'entreprise a été ajoutée avec succès.";
                        break;
                    case 2:
                        echo "L'entreprise a été modifiée avec succès.";
                        break;
                    case 3:
                        echo "L'entreprise a été supprimée avec succès.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="alert alert-danger">
                Impossible de supprimer l'entreprise car elle a des employés associés.
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntrepriseModal">
                <i class="bi bi-plus"></i> Ajouter une entreprise
            </button>
        </div>

        <div class="row">
            <?php foreach ($entreprises as $entreprise): ?>
            <div class="col-md-4 mb-4">
                <div class="card company-card">
                    <div class="card-body text-center">
                        <img src="<?php echo getImageBase64($entreprise['photo']); ?>" alt="Logo <?php echo htmlspecialchars($entreprise['nom']); ?>" class="company-logo mb-3">
                        <h5 class="card-title"><?php echo htmlspecialchars($entreprise['nom']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($entreprise['libelle'] ?? ''); ?></p>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary" onclick='editEntreprise(<?php echo json_encode([
                                "id" => $entreprise['id'],
                                "nom" => $entreprise['nom'],
                                "libelle" => $entreprise['libelle'] ?? ""
                            ]); ?>)'>
                                <i class="bi bi-pencil"></i> Modifier
                            </button>
                            <button class="btn btn-sm btn-danger" onclick='deleteEntreprise(<?php echo $entreprise['id']; ?>, <?php echo json_encode($entreprise['nom']); ?>)'>
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Ajout Entreprise -->
    <div class="modal fade" id="addEntrepriseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une entreprise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'entreprise</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Libellé</label>
                            <input type="text" class="form-control" name="libelle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" name="password" required>
                            <small class="text-muted">Ce mot de passe sera utilisé pour la connexion de l'entreprise</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modification Entreprise -->
    <div class="modal fade" id="editEntrepriseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'entreprise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'entreprise</label>
                            <input type="text" class="form-control" name="nom" id="edit_nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Libellé</label>
                            <input type="text" class="form-control" name="libelle" id="edit_libelle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="password">
                            <small class="text-muted">Laissez vide pour conserver le mot de passe actuel</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                            <small class="text-muted">Laissez vide pour conserver l'image actuelle</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Suppression Entreprise -->
    <div class="modal fade" id="deleteEntrepriseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir supprimer l'entreprise <span id="delete_entreprise_name"></span> ?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEntreprise(entreprise) {
            document.getElementById('edit_id').value = entreprise.id;
            document.getElementById('edit_nom').value = entreprise.nom;
            document.getElementById('edit_libelle').value = entreprise.libelle;
            new bootstrap.Modal(document.getElementById('editEntrepriseModal')).show();
        }

        function deleteEntreprise(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_entreprise_name').textContent = nom;
            new bootstrap.Modal(document.getElementById('deleteEntrepriseModal')).show();
        }
    </script>
</body>
</html>
