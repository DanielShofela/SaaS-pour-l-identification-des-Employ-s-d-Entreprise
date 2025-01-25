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

if (isset($_GET['id'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Vérifier si l'employé appartient bien à cette entreprise
        $stmt = $pdo->prepare("SELECT id FROM personne WHERE id = ? AND entreprise_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Employé non trouvé ou non autorisé.";
            header('Location: dashboard.php');
            exit();
        }

        // Supprimer l'employé
        $stmt = $pdo->prepare("DELETE FROM personne WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        $_SESSION['success'] = "Employé supprimé avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "ID de l'employé non spécifié.";
}

header('Location: dashboard.php');
exit();
?>
