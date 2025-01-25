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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM personne WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Cet email est déjà utilisé.";
            header('Location: dashboard.php');
            exit();
        }

        // Traiter la photo si elle existe
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        }

        // Préparer la requête d'insertion avec tous les champs
        $sql = "INSERT INTO personne (
            code, matricule, nom, prenoms, email, datenaiss, 
            lieunaiss, photo, comite_local, fonction, num_cni_passeport, 
            group_sang, ville, commune, quartier, situ_matri, tel, 
            profession, mob_money, num_wa, diplome, qual_prof, en_activite, 
            niv_etude, pernis, categorie, urg_nom_prenom, urg_ville_habite, 
            urg_commune, urg_quartier, urg_tel1, urg_tel2, entreprise_nom
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['code'],
            $_POST['matricule'],
            $_POST['nom'],
            $_POST['prenoms'],
            $_POST['email'],
            $_POST['datenaiss'],
            $_POST['lieunaiss'],
            $photo,
            $_POST['comite_local'],
            $_POST['fonction'],
            $_POST['num_cni_passeport'],
            $_POST['group_sang'],
            $_POST['ville'],
            $_POST['commune'],
            $_POST['quartier'],
            $_POST['situ_matri'],
            $_POST['tel'],
            $_POST['profession'],
            $_POST['mob_money'],
            $_POST['num_wa'],
            $_POST['diplome'],
            $_POST['qual_prof'],
            $_POST['en_activite'],
            $_POST['niv_etude'],
            $_POST['pernis'],
            $_POST['pernis'] == '1' ? $_POST['categorie'] : null,
            $_POST['urg_nom_prenom'],
            $_POST['urg_ville_habite'],
            $_POST['urg_commune'],
            $_POST['urg_quartier'],
            $_POST['urg_tel1'],
            $_POST['urg_tel2'],
            $_SESSION['user_id']
        ]);

        $_SESSION['success'] = "Employé ajouté avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'employé : " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Méthode non autorisée.";
}

header('Location: dashboard.php');
exit();
?>
