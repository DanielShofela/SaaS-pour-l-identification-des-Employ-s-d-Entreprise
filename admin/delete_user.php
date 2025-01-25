<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

require_once '../config/database.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($type) || empty($id) || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=personneldb", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier que la table est valide
    if ($type !== 'personne' && $type !== 'entreprise') {
        throw new Exception('Type invalide');
    }

    $stmt = $pdo->prepare("DELETE FROM $type WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
