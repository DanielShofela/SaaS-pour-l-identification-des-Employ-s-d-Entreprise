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

// Fonction pour convertir le BLOB en image base64
function getImageBase64($blob) {
    if ($blob) {
        return 'data:image/jpeg;base64,' . base64_encode($blob);
    }
    return null;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Récupérer les filtres
    $entreprise_filter = $_GET['entreprise'] ?? '';
    $ville_filter = $_GET['ville'] ?? '';
    $situ_matri_filter = $_GET['situ_matri'] ?? '';
    $comite_filter = $_GET['comite'] ?? '';

    // Construire la requête avec les filtres
    $sql = "SELECT p.*, e.nom as nom_entreprise 
            FROM personne p 
            LEFT JOIN entreprise e ON p.entreprise_id = e.id 
            WHERE 1=1";
    
    if ($entreprise_filter) {
        $sql .= " AND e.nom LIKE :entreprise";
    }
    if ($ville_filter) {
        $sql .= " AND p.ville LIKE :ville";
    }
    if ($situ_matri_filter) {
        $sql .= " AND p.situ_matri LIKE :situ_matri";
    }
    if ($comite_filter) {
        $sql .= " AND p.comite_local LIKE :comite";
    }
    
    $sql .= " ORDER BY p.nom, p.prenoms";
    
    $stmt = $pdo->prepare($sql);
    
    // Lier les paramètres si nécessaire
    if ($entreprise_filter) {
        $stmt->bindValue(':entreprise', "%$entreprise_filter%", PDO::PARAM_STR);
    }
    if ($ville_filter) {
        $stmt->bindValue(':ville', "%$ville_filter%", PDO::PARAM_STR);
    }
    if ($situ_matri_filter) {
        $stmt->bindValue(':situ_matri', "%$situ_matri_filter%", PDO::PARAM_STR);
    }
    if ($comite_filter) {
        $stmt->bindValue(':comite', "%$comite_filter%", PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $personnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les listes pour les filtres
    $stmt = $pdo->query("SELECT DISTINCT nom FROM entreprise ORDER BY nom");
    $entreprises = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT ville FROM personne WHERE ville IS NOT NULL ORDER BY ville");
    $villes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT situ_matri FROM personne WHERE situ_matri IS NOT NULL ORDER BY situ_matri");
    $situations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->query("SELECT DISTINCT comite_local FROM personne WHERE comite_local IS NOT NULL ORDER BY comite_local");
    $comites = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .top-bar {
            background-color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .table {
            margin: 0;
        }

        .table th {
            border-top: none;
            background-color: #f8f9fa;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--accent-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info {
            text-align: right;
            color: var(--secondary-color);
        }

        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .stats-card h4 {
            font-size: 1rem;
            margin: 0;
            opacity: 0.9;
        }

        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>
            <a class="nav-link" href="entreprises.php">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Tableau de bord Administrateur</h1>
            <div class="user-info">
                <span class="me-3">Bienvenue, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Profile" class="profile-img">
            </div>
        </div>

        <!-- Liste complète avec filtres -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3>Liste des personnes</h3>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <form method="GET" class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Entreprise</label>
                            <select name="entreprise" class="form-select">
                                <option value="">Toutes les entreprises</option>
                                <?php foreach ($entreprises as $entreprise): ?>
                                    <option value="<?= htmlspecialchars($entreprise) ?>" 
                                            <?= $entreprise_filter === $entreprise ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($entreprise) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ville</label>
                            <select name="ville" class="form-select">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $ville): ?>
                                    <option value="<?= htmlspecialchars($ville) ?>"
                                            <?= $ville_filter === $ville ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ville) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Situation matrimoniale</label>
                            <select name="situ_matri" class="form-select">
                                <option value="">Toutes les situations</option>
                                <?php foreach ($situations as $situation): ?>
                                    <option value="<?= htmlspecialchars($situation) ?>"
                                            <?= $situ_matri_filter === $situation ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($situation) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Comité local</label>
                            <select name="comite" class="form-select">
                                <option value="">Tous les comités</option>
                                <?php foreach ($comites as $comite): ?>
                                    <option value="<?= htmlspecialchars($comite) ?>"
                                            <?= $comite_filter === $comite ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($comite) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                            <a href="dashboard.php" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </form>

                    <!-- Tableau des résultats -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Nom</th>
                                    <th>Prénoms</th>
                                    <th>Entreprise</th>
                                    <th>Ville</th>
                                    <th>Situation matrimoniale</th>
                                    <th>Comité local</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personnes as $personne): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $photo = $personne['photo'];
                                        $photo_src = '';
                                        
                                        if ($photo) {
                                            $photo_src = getImageBase64($photo);
                                        }
                                        
                                        if (empty($photo_src)) {
                                            $photo_src = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $personne['id'];
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($photo_src) ?>" 
                                             alt="Photo de profil" 
                                             class="rounded-circle"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?= htmlspecialchars($personne['nom']) ?></td>
                                    <td><?= htmlspecialchars($personne['prenoms']) ?></td>
                                    <td><?= htmlspecialchars($personne['nom_entreprise'] ?? 'Non assigné') ?></td>
                                    <td><?= htmlspecialchars($personne['ville']) ?></td>
                                    <td><?= htmlspecialchars($personne['situ_matri']) ?></td>
                                    <td><?= htmlspecialchars($personne['comite_local']) ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="viewProfile('personne', <?= $personne['id'] ?>)">
                                            Voir profil
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProfile(type, id) {
            const mappedType = type === 'personne' ? 'person' : 'company';
            window.location.href = `view_profile.php?type=${mappedType}&id=${id}`;
        }

        function deleteProfile(type, id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                window.location.href = `delete_profile.php?type=${type}&id=${id}`;
            }
        }
    </script>
</body>
</html>
