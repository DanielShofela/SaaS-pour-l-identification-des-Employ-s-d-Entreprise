<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Fonction pour gérer l'affichage des images
function getImageSrc($photoData, $defaultImage = 'default_company.jpg') {
    if ($photoData) {
        return 'data:image/jpeg;base64,' . base64_encode($photoData);
    }
    return "../uploads/" . $defaultImage;
}

// Vérifier si l'utilisateur est connecté et est une entreprise
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entreprise') {
    header('Location: ../index.php');
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Récupérer les informations de l'entreprise
    $stmt = $pdo->prepare("SELECT * FROM entreprise WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entreprise) {
        die("Entreprise non trouvée");
    }
    
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Entreprise - <?php echo htmlspecialchars($entreprise['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
        .employee-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo htmlspecialchars($entreprise['nom']); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#employeesList">
                            <i class="bi bi-people"></i> Liste des employés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#addEmployee">
                            <i class="bi bi-person-plus"></i> Ajouter un employé
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo getImageSrc($entreprise['photo']); ?>" 
                             alt="Profile" 
                             class="profile-img mb-3"
                             onerror="this.src='../uploads/default_company.jpg'">
                        <h4><?php echo htmlspecialchars($entreprise['nom']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($entreprise['libelle']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0" id="addEmployee">Ajouter un employé</h5>
                    </div>
                    <div class="card-body">
                        <form action="add_employee.php" method="POST" enctype="multipart/form-data">
                            <h5 class="mb-3">ETAT CIVIL</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CODE</label>
                                    <input type="text" class="form-control" name="code" required maxlength="20">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">MATRICULE</label>
                                    <input type="text" class="form-control" name="matricule" required maxlength="15">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NOM</label>
                                    <input type="text" class="form-control" name="nom" required maxlength="50">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PRENOMS</label>
                                    <input type="text" class="form-control" name="prenoms" required maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">DATE DE NAISSANCE</label>
                                    <input type="date" class="form-control" name="datenaiss" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">LIEU DE NAISSANCE</label>
                                    <input type="text" class="form-control" name="lieunaiss" required maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">COMITÉ LOCAL</label>
                                    <input type="text" class="form-control" name="comite_local" required maxlength="50">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">FONCTION CL/CR</label>
                                    <input type="text" class="form-control" name="fonction" required maxlength="25">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">N° CNI / PASSEPORT</label>
                                    <input type="number" class="form-control" name="num_cni_passeport" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PROFESSION</label>
                                    <input type="text" class="form-control" name="profession" required maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">GROUPE SANGUIN</label>
                                    <select class="form-select" name="group_sang" required>
                                        <option value="">Sélectionner</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PHOTO DE PROFIL</label>
                                    <input type="file" class="form-control" name="photo" accept="image/*">
                                    <div id="imagePreview" class="mt-2"></div>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">LIEU D'HABITATION</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">VILLE</label>
                                    <input type="text" class="form-control" name="ville" required maxlength="30">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">COMMUNE</label>
                                    <input type="text" class="form-control" name="commune" required maxlength="30">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">QUARTIER</label>
                                    <input type="text" class="form-control" name="quartier" required maxlength="30">
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">SITUATION MATRIMONIALE</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="situ_matri" id="celibataire" value="Célibataire" required>
                                        <label class="form-check-label" for="celibataire">CELIBATAIRE</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="situ_matri" id="marie" value="Marié(e)">
                                        <label class="form-check-label" for="marie">MARIE(E)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="situ_matri" id="veuf" value="Veuf(ve)">
                                        <label class="form-check-label" for="veuf">VEUF(VE)</label>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">ADRESSE</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TELEPHONE</label>
                                    <input type="text" class="form-control" name="tel" required maxlength="14">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NUMERO WHATSAPP</label>
                                    <input type="text" class="form-control" name="num_wa" required maxlength="14">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">MOBILE MONEY</label>
                                    <input type="text" class="form-control" name="mob_money" required maxlength="14">
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">INFOS PERSONNELLES</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">DIPLÔME</label>
                                    <select class="form-select" name="diplome" required>
                                        <option value="">Sélectionner</option>
                                        <option value="BEPC">BEPC</option>
                                        <option value="BAC">BAC</option>
                                        <option value="BTS">BTS</option>
                                        <option value="Licence">Licence</option>
                                        <option value="Master">Master</option>
                                        <option value="Doctorat">Doctorat</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NIVEAU D'ETUDE</label>
                                    <input type="text" class="form-control" name="niv_etude" required maxlength="15">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">QUALIFICATION PROFESSIONNELLE</label>
                                    <input type="text" class="form-control" name="qual_prof" required maxlength="30">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ETES VOUS PRESENTEMENT EN ACTIVITE</label>
                                    <select class="form-select" name="en_activite" required>
                                        <option value="1">Oui</option>
                                        <option value="0">Non</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">AVEZ-VOUS UN PERMIS DE CONDUIRE</label>
                                    <select class="form-select" name="pernis" required onchange="toggleCategorie(this.value)">
                                        <option value="1">Oui</option>
                                        <option value="0" selected>Non</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3" id="categoriePermis" style="display: none;">
                                    <label class="form-label">CATEGORIE</label>
                                    <select class="form-select" name="categorie">
                                        <option value="">Sélectionner</option>
                                        <option value="A">A - Motocyclettes</option>
                                        <option value="A1">A1 - Motocyclettes légères</option>
                                        <option value="B">B - Véhicules légers</option>
                                        <option value="C">C - Poids lourds</option>
                                        <option value="D">D - Transport en commun</option>
                                        <option value="E">E - Remorques</option>
                                        <option value="F">F - Véhicules agricoles</option>
                                    </select>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">PERSONNE A CONTACTER EN CAS D'URGENCE</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">NOM et PRENOMS</label>
                                    <input type="text" class="form-control" name="urg_nom_prenom" required maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">VILLE D'HABITATION</label>
                                    <input type="text" class="form-control" name="urg_ville_habite" required maxlength="50">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">COMMUNE</label>
                                    <input type="text" class="form-control" name="urg_commune" required maxlength="30">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">QUARTIER</label>
                                    <input type="text" class="form-control" name="urg_quartier" required maxlength="30">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TELEPHONE 1</label>
                                    <input type="text" class="form-control" name="urg_tel1" required maxlength="14">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TELEPHONE 2</label>
                                    <input type="text" class="form-control" name="urg_tel2" required maxlength="14">
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Ajouter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0" id="employeesList">Liste des employés</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM personne WHERE entreprise_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    while($employe = $stmt->fetch()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo getImageSrc($employe['photo'], 'default.jpg'); ?>" 
                                                 alt="Employee" 
                                                 class="employee-img"
                                                 onerror="this.src='../uploads/default.jpg'">
                                        </td>
                                        <td><?php echo htmlspecialchars($employe['nom'] . ' ' . $employe['prenoms']); ?></td>
                                        <td><?php echo htmlspecialchars($employe['email']); ?></td>
                                        <td><?php echo htmlspecialchars($employe['tel']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewProfile('personne', <?php echo $employe['id']; ?>)">
                                                <i class="bi bi-eye"></i> Voir
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="editEmployee(<?php echo $employe['id']; ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $employe['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEmployee(id) {
            window.location.href = 'edit_employee.php?id=' + id;
        }

        function deleteEmployee(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')) {
                window.location.href = 'delete_employee.php?id=' + id;
            }
        }

        function viewProfile(type, id) {
            const mappedType = type === 'personne' ? 'person' : 'company';
            window.location.href = `view_profile.php?type=${mappedType}&id=${id}`;
        }


        function toggleCategorie(value) {
            const categorieDiv = document.getElementById('categoriePermis');
            const categorieSelect = categorieDiv.querySelector('select');
            
            if (value === '1') {
                categorieDiv.style.display = 'block';
                categorieSelect.required = true;
            } else {
                categorieDiv.style.display = 'none';
                categorieSelect.required = false;
                categorieSelect.value = '';
            }
        }

        // Initialiser l'état au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            const permisSelect = document.querySelector('select[name="pernis"]');
            toggleCategorie(permisSelect.value);
        });

        // Prévisualisation de l'image
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '200px';
                    img.className = 'img-thumbnail';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Ajouter l'écouteur d'événement pour la prévisualisation de l'image
        document.querySelector('input[name="photo"]').addEventListener('change', function() {
            previewImage(this);
        });
    </script>
</body>
</html>
