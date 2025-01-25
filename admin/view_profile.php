<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Vérifier si l'ID et le type sont fournis
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    header('Location: dashboard.php');
    exit();
}

$id = $_GET['id'];
$type = $_GET['type'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $data = null;
    if ($type === 'person') {
        // Récupérer les informations de la personne
        $stmt = $pdo->prepare("SELECT * FROM personne WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($type === 'company') {
        // Récupérer les informations de l'entreprise
        $stmt = $pdo->prepare("SELECT * FROM entreprise WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Récupérer la liste des employés de l'entreprise
        $stmt = $pdo->prepare("SELECT id, nom, prenoms, email, tel, profession FROM personne WHERE entreprise_id = ?");
        $stmt->execute([$id]);
        $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!$data) {
        header('Location: dashboard.php');
        exit();
    }

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour gérer l'affichage des images
function getImageUrl($photoData, $defaultImage = 'default.jpg') {
    if ($photoData) {
        $tempDir = '../temp_images/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        $tempFile = $tempDir . uniqid() . '.jpg';
        file_put_contents($tempFile, $photoData);
        return $tempFile;
    }
    return "../uploads/" . $defaultImage;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($data['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 5px solid #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .info-section {
            margin-bottom: 30px;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .info-section h3 {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
        .row.mb-3:last-child {
            margin-bottom: 0 !important;
        }
        .badge {
            font-size: 0.9em;
            padding: 8px 12px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="profile-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="text-primary">
                    <i class="bi bi-person-circle me-2"></i>
                    Profil <?php echo $type === 'person' ? 'Personnel' : 'Entreprise'; ?>
                </h1>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                    <a href="../logout.php" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if ($type === 'person'): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <!-- Bouton de téléchargement -->
                    <div class="text-end mb-4">
                        <a href="generate_pdf.php?type=person&id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="bi bi-file-earmark-pdf me-2"></i>
                            Télécharger la fiche d'identification
                        </a>
                    </div>
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <!-- Photo et informations principales -->
                    <div class="info-section text-center">
                        <img src="<?php echo getImageUrl($data['photo']); ?>" alt="Photo" class="profile-photo">
                        <h2 class="mb-3"><?php echo htmlspecialchars($data['nom'] . ' ' . $data['prenoms']); ?></h2>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($data['profession']); ?></p>
                        <div class="badge bg-primary mb-2"><?php echo htmlspecialchars($data['matricule']); ?></div>
                    </div>

                    <!-- Contact rapide -->
                    <div class="info-section">
                        <h3><i class="bi bi-telephone me-2"></i>Contact Rapide</h3>
                        <div class="d-grid gap-2">
                            <a href="tel:<?php echo htmlspecialchars($data['tel']); ?>" class="btn btn-outline-primary">
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($data['tel']); ?>
                            </a>
                            <a href="mailto:<?php echo htmlspecialchars($data['email']); ?>" class="btn btn-outline-primary">
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($data['email']); ?>
                            </a>
                            <a href="https://wa.me/<?php echo htmlspecialchars($data['num_wa']); ?>" class="btn btn-outline-success">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <!-- Informations personnelles -->
                    <div class="info-section">
                        <h3><i class="bi bi-person me-2"></i>Informations Personnelles</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-label">Date de naissance</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['datenaiss']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Lieu de naissance</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['lieunaiss']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Situation matrimoniale</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['situ_matri']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Groupe sanguin</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['group_sang']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">CNI/Passeport</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['num_cni_passeport']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Adresse -->
                    <div class="info-section">
                        <h3><i class="bi bi-geo-alt me-2"></i>Adresse</h3>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="info-label">Ville</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['ville']); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Commune</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['commune']); ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-label">Quartier</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['quartier']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations professionnelles -->
                    <div class="info-section">
                        <h3><i class="bi bi-briefcase me-2"></i>Informations Professionnelles</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-label">Niveau d'études</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['niv_etude']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Diplôme</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['diplome']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Qualification professionnelle</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['qual_prof']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Permis</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['pernis']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">En activité</div>
                                <div class="info-value">
                                    <span class="badge <?php echo $data['en_activite'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $data['en_activite'] ? 'Oui' : 'Non'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Comité local</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['comite_local']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Fonction</div>
                                <div class="info-value"><?php echo htmlspecialchars($data['fonction']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact d'urgence -->
                    <div class="info-section">
                        <h3><i class="bi bi-exclamation-triangle me-2"></i>Contact d'Urgence</h3>
                        
                        <!-- Personne à contacter -->
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">
                                <i class="bi bi-person-lines-fill me-2"></i>
                                <?php echo htmlspecialchars($data['urg_nom_prenom']); ?>
                            </h5>
                        </div>

                        <!-- Téléphones -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="d-grid">
                                    <a href="tel:<?php echo htmlspecialchars($data['urg_tel1']); ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="bi bi-telephone me-2"></i>
                                        <?php echo htmlspecialchars($data['urg_tel1']); ?>
                                        <small class="d-block">Principal</small>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid">
                                    <a href="tel:<?php echo htmlspecialchars($data['urg_tel2']); ?>" 
                                       class="btn btn-outline-secondary">
                                        <i class="bi bi-telephone me-2"></i>
                                        <?php echo htmlspecialchars($data['urg_tel2']); ?>
                                        <small class="d-block">Secondaire</small>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Adresse -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    Adresse
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Ville</div>
                                        <div class="info-value"><?php echo htmlspecialchars($data['urg_ville_habite']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Commune</div>
                                        <div class="info-value"><?php echo htmlspecialchars($data['urg_commune']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Quartier</div>
                                        <div class="info-value"><?php echo htmlspecialchars($data['urg_quartier']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Affichage des informations de l'entreprise -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="info-section">
                        <div class="text-center mb-4">
                            <img src="<?php echo getImageUrl($data['photo']); ?>" alt="Logo" class="profile-photo">
                            <h2 class="mt-3"><?php echo htmlspecialchars($data['nom']); ?></h2>
                            <p class="text-muted"><?php echo htmlspecialchars($data['libelle']); ?></p>
                        </div>
                    </div>

                    <!-- Liste des employés -->
                    <div class="info-section">
                        <h3><i class="bi bi-people me-2"></i>Liste des Employés</h3>
                        <?php if (empty($employes)): ?>
                            <p class="text-muted text-center">Aucun employé dans cette entreprise</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nom complet</th>
                                            <th>Profession</th>
                                            <th>Contact</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employes as $employe): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employe['nom'] . ' ' . $employe['prenoms']); ?></td>
                                                <td><?php echo htmlspecialchars($employe['profession']); ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <small>
                                                            <i class="bi bi-envelope"></i> 
                                                            <a href="mailto:<?php echo htmlspecialchars($employe['email']); ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($employe['email']); ?>
                                                            </a>
                                                        </small>
                                                        <small>
                                                            <i class="bi bi-telephone"></i>
                                                            <a href="tel:<?php echo htmlspecialchars($employe['tel']); ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($employe['tel']); ?>
                                                            </a>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="viewProfile('personne', <?php echo $employe['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewProfile(type, id) {
            const mappedType = type === 'personne' ? 'person' : 'company';
            window.location.href = `view_profile.php?type=${mappedType}&id=${id}`;
        }
    </script>
</body>
</html>
