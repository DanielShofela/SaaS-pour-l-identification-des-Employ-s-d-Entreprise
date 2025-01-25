<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est une entreprise
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entreprise') {
    header('Location: ../index.php');
    exit();
}

// Vérifier si un ID est fourni
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de l'employé non spécifié";
    header('Location: dashboard.php');
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Récupérer les informations de l'employé
    $stmt = $pdo->prepare("SELECT * FROM personne WHERE id = ? AND entreprise_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $employe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employe) {
        $_SESSION['error'] = "Employé non trouvé ou vous n'avez pas les droits pour le modifier";
        header('Location: dashboard.php');
        exit();
    }

    // Si le formulaire est soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Validation des données
            $matricule = trim($_POST['matricule']);
            $datenaiss = trim($_POST['datenaiss']);
            $lieunaiss = trim($_POST['lieunaiss']); 
            // Traitement de la photo
            $photo = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && !empty($_FILES['photo']['tmp_name'])) {
                $photo = file_get_contents($_FILES['photo']['tmp_name']);
            }
            $nom = trim($_POST['nom']);
            $comite_local = trim($_POST['comite_local']);
            $fonction = trim($_POST['fonction']);
            $num_cni_passeport = trim($_POST['num_cni_passeport']);
            $group_sang = trim($_POST['group_sang']);
            $profession = trim($_POST['profession']);
            $situ_matri = trim($_POST['situ_matri']);
            $commune = trim($_POST['commune']);
            $quartier = trim($_POST['quartier']);
            $mob_money = trim($_POST['mob_money']);
            $num_wa = trim($_POST['num_wa']);
            $niv_etude = trim($_POST['niv_etude']);
            $pernis = trim($_POST['pernis']);
            $qual_prof = trim($_POST['qual_prof']);
            $en_activite = trim($_POST['en_activite']);
            $urg_nom_prenom = trim($_POST['urg_nom_prenom']);
            $urg_commune = trim($_POST['urg_commune']);
            $urg_ville_habite = trim($_POST['urg_ville_habite']);
            $urg_quartier = trim($_POST['urg_quartier']);
            $urg_tel1 = trim($_POST['urg_tel1']);
            $urg_tel2 = trim($_POST['urg_tel2']);                                   
            $prenoms = trim($_POST['prenoms']);
            $email = trim($_POST['email']);
            $tel = trim($_POST['tel']);
            $code = trim($_POST['code']);
            $ville = trim($_POST['ville']);
            $diplome = trim($_POST['diplome']);
            $ville_habite = trim($_POST['ville_habite']);

            // Mise à jour des données
            $sql = "UPDATE personne SET 
                    nom = ?, 
                    prenoms = ?, 
                    email = ?, 
                    tel = ?,
                    code = ?,
                    ville = ?,
                    diplome = ?,
                    urg_ville_habite = ?
                    WHERE id = ? AND entreprise_id = ?";
        
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nom, 
                $prenoms, 
                $email, 
                $tel,
                $code,
                $ville,
                $diplome,
                $ville_habite,
                $_GET['id'],
                $_SESSION['user_id']
            ]);

            // Traitement de la photo si une nouvelle est uploadée
            if ($photo) {
                $stmt = $pdo->prepare("UPDATE personne SET photo = ? WHERE id = ? AND entreprise_id = ?");
                $stmt->execute([$photo, $_GET['id'], $_SESSION['user_id']]);
            }

            $_SESSION['success'] = "Les modifications ont été enregistrées avec succès";
            header('Location: dashboard.php');
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
            header('Location: dashboard.php');
            exit();
        }
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
    header('Location: dashboard.php');
    exit();
}

// Fonction pour gérer l'affichage des images
function getImageSrc($photoData, $defaultImage = 'default.jpg') {
    if ($photoData) {
        return 'data:image/jpeg;base64,' . base64_encode($photoData);
    }
    return "../uploads/" . $defaultImage;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un employé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Modifier l'employé</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="<?php echo getImageSrc($employe['photo']); ?>" 
                                 alt="Photo de l'employé" 
                                 class="profile-img"
                                 id="currentPhoto">
                            <div id="imagePreview"></div>
                        </div>

                        <form action="edit_employee.php?id=<?php echo $_GET['id']; ?>" method="POST" enctype="multipart/form-data">
                            <h5 class="mb-3">ETAT CIVIL</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CODE</label>
                                    <input type="text" class="form-control" name="code" required maxlength="20" value="<?php echo htmlspecialchars($employe['code']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">MATRICULE</label>
                                    <input type="text" class="form-control" name="matricule" required maxlength="15" value="<?php echo htmlspecialchars($employe['matricule']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NOM</label>
                                    <input type="text" class="form-control" name="nom" required maxlength="50" value="<?php echo htmlspecialchars($employe['nom']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PRENOMS</label>
                                    <input type="text" class="form-control" name="prenoms" required maxlength="50" value="<?php echo htmlspecialchars($employe['prenoms']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">DATE DE NAISSANCE</label>
                                    <input type="date" class="form-control" name="datenaiss" required value="<?php echo htmlspecialchars($employe['datenaiss']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">LIEU DE NAISSANCE</label>
                                    <input type="text" class="form-control" name="lieunaiss" required maxlength="50" value="<?php echo htmlspecialchars($employe['lieunaiss']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">COMITÉ LOCAL</label>
                                    <input type="text" class="form-control" name="comite_local" required maxlength="50" value="<?php echo htmlspecialchars($employe['comite_local']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">FONCTION CL/CR</label>
                                    <input type="text" class="form-control" name="fonction" required maxlength="25" value="<?php echo htmlspecialchars($employe['fonction']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">N° CNI / PASSEPORT</label>
                                    <input type="number" class="form-control" name="num_cni_passeport" required value="<?php echo htmlspecialchars($employe['num_cni_passeport']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PROFESSION</label>
                                    <input type="text" class="form-control" name="profession" required maxlength="50" value="<?php echo htmlspecialchars($employe['profession']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">GROUPE SANGUIN</label>
                                    <select class="form-select" name="group_sang" required>
                                        <option value="">Sélectionner</option>
                                        <option value="A+" <?php echo $employe['group_sang'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo $employe['group_sang'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo $employe['group_sang'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo $employe['group_sang'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo $employe['group_sang'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo $employe['group_sang'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo $employe['group_sang'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo $employe['group_sang'] === 'O-' ? 'selected' : ''; ?>>O-</option>
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
                                    <input type="text" class="form-control" name="ville" required maxlength="30" value="<?php echo htmlspecialchars($employe['ville']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">COMMUNE</label>
                                    <input type="text" class="form-control" name="commune" required maxlength="30" value="<?php echo htmlspecialchars($employe['commune']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">QUARTIER</label>
                                    <input type="text" class="form-control" name="quartier" required maxlength="30" value="<?php echo htmlspecialchars($employe['quartier']); ?>">
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">SITUATION MATRIMONIALE</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="situ_matri" id="celibataire" value="Célibataire" <?php echo $employe['situ_matri'] === 'Célibataire' ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="celibataire">CELIBATAIRE</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="situ_matri" id="marie" value="Marié(e)" <?php echo $employe['situ_matri'] === 'Marié(e)' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="marie">MARIE(E)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="situ_matri" id="veuf" value="Veuf(ve)" <?php echo $employe['situ_matri'] === 'Veuf(ve)' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="veuf">VEUF(VE)</label>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">ADRESSE</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TELEPHONE</label>
                                    <input type="text" class="form-control" name="tel" required maxlength="14" value="<?php echo htmlspecialchars($employe['tel']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required maxlength="50" value="<?php echo htmlspecialchars($employe['email']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NUMERO WHATSAPP</label>
                                    <input type="text" class="form-control" name="num_wa" required maxlength="14" value="<?php echo htmlspecialchars($employe['num_wa']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">MOBILE MONEY</label>
                                    <input type="text" class="form-control" name="mob_money" required maxlength="14" value="<?php echo htmlspecialchars($employe['mob_money']); ?>">
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">INFOS PERSONNELLES</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">DIPLÔME</label>
                                    <select class="form-select" name="diplome" required>
                                        <option value="">Sélectionner</option>
                                        <option value="BEPC" <?php echo $employe['diplome'] === 'BEPC' ? 'selected' : ''; ?>>BEPC</option>
                                        <option value="BAC" <?php echo $employe['diplome'] === 'BAC' ? 'selected' : ''; ?>>BAC</option>
                                        <option value="BTS" <?php echo $employe['diplome'] === 'BTS' ? 'selected' : ''; ?>>BTS</option>
                                        <option value="Licence" <?php echo $employe['diplome'] === 'Licence' ? 'selected' : ''; ?>>Licence</option>
                                        <option value="Master" <?php echo $employe['diplome'] === 'Master' ? 'selected' : ''; ?>>Master</option>
                                        <option value="Doctorat" <?php echo $employe['diplome'] === 'Doctorat' ? 'selected' : ''; ?>>Doctorat</option>
                                        <option value="Autre" <?php echo $employe['diplome'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NIVEAU D'ETUDE</label>
                                    <input type="text" class="form-control" name="niv_etude" required maxlength="15" value="<?php echo htmlspecialchars($employe['niv_etude']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">QUALIFICATION PROFESSIONNELLE</label>
                                    <input type="text" class="form-control" name="qual_prof" required maxlength="30" value="<?php echo htmlspecialchars($employe['qual_prof']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ETES VOUS PRESENTEMENT EN ACTIVITE</label>
                                    <select class="form-select" name="en_activite" required>
                                        <option value="1" <?php echo $employe['en_activite'] == '1' ? 'selected' : ''; ?>>Oui</option>
                                        <option value="0" <?php echo $employe['en_activite'] == '0' ? 'selected' : ''; ?>>Non</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">AVEZ-VOUS UN PERMIS DE CONDUIRE</label>
                                    <select class="form-select" name="pernis" required onchange="toggleCategorie(this.value)">
                                        <option value="1" <?php echo $employe['pernis'] == '1' ? 'selected' : ''; ?>>Oui</option>
                                        <option value="0" <?php echo $employe['pernis'] == '0' ? 'selected' : ''; ?>>Non</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3" id="categoriePermis" style="display: none;">
                                    <label class="form-label">CATEGORIE</label>
                                    <select class="form-select" name="categorie">
                                        <option value="">Sélectionner</option>
                                        <option value="A" <?php echo $employe['categorie'] === 'A' ? 'selected' : ''; ?>>A - Motocyclettes</option>
                                        <option value="A1" <?php echo $employe['categorie'] === 'A1' ? 'selected' : ''; ?>>A1 - Motocyclettes légères</option>
                                        <option value="B" <?php echo $employe['categorie'] === 'B' ? 'selected' : ''; ?>>B - Véhicules légers</option>
                                        <option value="C" <?php echo $employe['categorie'] === 'C' ? 'selected' : ''; ?>>C - Poids lourds</option>
                                        <option value="D" <?php echo $employe['categorie'] === 'D' ? 'selected' : ''; ?>>D - Transport en commun</option>
                                        <option value="E" <?php echo $employe['categorie'] === 'E' ? 'selected' : ''; ?>>E - Remorques</option>
                                        <option value="F" <?php echo $employe['categorie'] === 'F' ? 'selected' : ''; ?>>F - Véhicules agricoles</option>
                                    </select>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">PERSONNE A CONTACTER EN CAS D'URGENCE</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">NOM et PRENOMS</label>
                                    <input type="text" class="form-control" name="urg_nom_prenom" required maxlength="50" value="<?php echo htmlspecialchars($employe['urg_nom_prenom']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">VILLE D'HABITATION</label>
                                    <input type="text" class="form-control" name="urg_ville_habite" required maxlength="50" value="<?php echo htmlspecialchars($employe['urg_ville_habite']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">COMMUNE</label>
                                    <input type="text" class="form-control" name="urg_commune" required maxlength="30" value="<?php echo htmlspecialchars($employe['urg_commune']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">QUARTIER</label>
                                    <input type="text" class="form-control" name="urg_quartier" required maxlength="30" value="<?php echo htmlspecialchars($employe['urg_quartier']); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TELEPHONE 1</label>
                                    <input type="text" class="form-control" name="urg_tel1" required maxlength="14" value="<?php echo htmlspecialchars($employe['urg_tel1']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TELEPHONE 2</label>
                                    <input type="text" class="form-control" name="urg_tel2" required maxlength="14" value="<?php echo htmlspecialchars($employe['urg_tel2']); ?>">
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                </div>
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
            const currentPhoto = document.getElementById('currentPhoto');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    currentPhoto.style.display = 'none';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'profile-img';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                currentPhoto.style.display = 'block';
            }
        }

        // Ajouter l'écouteur d'événement pour la prévisualisation de l'image
        document.querySelector('input[name="photo"]').addEventListener('change', function() {
            previewImage(this);
        });
    </script>
</body>
</html>
