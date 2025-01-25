<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
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
            
            $stmt = $pdo->prepare("INSERT INTO personne (comite_local) VALUES (?)");
            $stmt->execute([$nom]);
            
            header('Location: communautes.php?success=1');
            exit();
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $nom = $_POST['nom'];
            
            $stmt = $pdo->prepare("UPDATE personne SET comite_local = ? WHERE comite_local = ?");
            $stmt->execute([$nom, $id]);
            
            header('Location: communautes.php?success=2');
            exit();
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            $stmt = $pdo->prepare("UPDATE personne SET comite_local = NULL WHERE comite_local = ?");
            $stmt->execute([$id]);
            
            header('Location: communautes.php?success=3');
            exit();
        }
    }

    // Récupérer la liste des communautés avec leur nombre de membres
    $query = "SELECT DISTINCT 
                p.comite_local as nom,
                COUNT(*) as nombre_membres
              FROM personne p 
              WHERE p.comite_local IS NOT NULL 
              GROUP BY p.comite_local 
              ORDER BY p.comite_local";
              
    $stmt = $pdo->query($query);
    $communautes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si aucune communauté n'existe, initialiser un tableau vide
    if (!$communautes) {
        $communautes = [];
    }

} catch(PDOException $e) {
    $error = "Une erreur est survenue : " . $e->getMessage();
    $communautes = []; // Initialiser un tableau vide en cas d'erreur
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Communautés Locales - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/admin-style.css" rel="stylesheet">
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
        .members-list {
            max-height: 200px;
            overflow-y: auto;
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
            <a class="nav-link" href="entreprises.php">
                <i class="bi bi-building"></i> Gestion Entreprises
            </a>
            <a class="nav-link active" href="communautes.php">
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
            <h1 class="h4 mb-0">Gestion des Communautés Locales</h1>
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
                        echo "La communauté locale a été ajoutée avec succès.";
                        break;
                    case 2:
                        echo "La communauté locale a été modifiée avec succès.";
                        break;
                    case 3:
                        echo "La communauté locale a été supprimée avec succès.";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="alert alert-danger">
                Impossible de supprimer la communauté car elle a des membres associés.
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Liste des communautés locales</h3>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommunauteModal">
                    <i class="bi bi-plus"></i> Ajouter une communauté
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Nombre de membres</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($communautes as $communaute): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($communaute['nom']); ?></td>
                                <td>
                                    <button class="btn btn-link" onclick="viewMembers('<?php echo htmlspecialchars($communaute['nom']); ?>')">
                                        <?php echo $communaute['nombre_membres']; ?> membres
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editCommunaute(<?php echo htmlspecialchars(json_encode($communaute)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCommunaute('<?php echo htmlspecialchars($communaute['nom']); ?>', '<?php echo htmlspecialchars($communaute['nom']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Communauté -->
    <div class="modal fade" id="addCommunauteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une communauté locale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" required>
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

    <!-- Modal Modification Communauté -->
    <div class="modal fade" id="editCommunauteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la communauté locale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" id="edit_nom" required>
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

    <!-- Modal Suppression Communauté -->
    <div class="modal fade" id="deleteCommunauteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir supprimer la communauté <span id="delete_communaute_name"></span> ?
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

    <!-- Modal Liste des Membres -->
    <div class="modal fade" id="membersModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Membres de la communauté</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="membersList" class="members-list">
                        <!-- La liste des membres sera chargée ici via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCommunaute(communaute) {
            document.getElementById('edit_id').value = communaute.id;
            document.getElementById('edit_nom').value = communaute.nom;
            new bootstrap.Modal(document.getElementById('editCommunauteModal')).show();
        }

        function deleteCommunaute(id, nom) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_communaute_name').textContent = nom;
            new bootstrap.Modal(document.getElementById('deleteCommunauteModal')).show();
        }

        function viewMembers(communauteId) {
            const membersModal = new bootstrap.Modal(document.getElementById('membersModal'));
            const membersList = document.getElementById('membersList');
            membersList.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
            
            // Charger les membres via AJAX
            fetch(`get_members.php?comite=${communauteId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '<ul class="list-group">';
                        data.membres.forEach(membre => {
                            html += `<li class="list-group-item">
                                <div><strong>${membre.nom} ${membre.prenom}</strong></div>
                                <div><small>${membre.entreprise ? membre.entreprise : 'Aucune entreprise'} - ${membre.fonction}</small></div>
                                <div><small>Email: ${membre.email} | Tél: ${membre.telephone}</small></div>
                            </li>`;
                        });
                        html += '</ul>';
                        membersList.innerHTML = data.membres.length ? html : '<div class="alert alert-info">Aucun membre dans cette communauté.</div>';
                    } else {
                        membersList.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                    membersModal.show();
                })
                .catch(error => {
                    membersList.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des membres.</div>';
                    membersModal.show();
                });
        }
    </script>
</body>
</html>
