<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['comite'])) {
        throw new Exception('Le paramètre comite est requis');
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $comite = $_GET['comite'];

    // Récupérer les membres de la communauté avec leurs informations
    $query = "SELECT 
                p.*,
                e.nom as nom_entreprise,
                e.libelle as libelle_entreprise
              FROM personne p
              LEFT JOIN entreprise e ON p.entreprise_id = e.id
              WHERE p.comite_local = ?
              ORDER BY p.nom";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$comite]);
    $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'membres' => array_map(function($membre) {
            return [
                'nom' => $membre['nom'],
                'prenom' => isset($membre['prenom']) ? $membre['prenom'] : '',
                'email' => isset($membre['email']) ? $membre['email'] : '',
                'telephone' => isset($membre['telephone']) ? $membre['telephone'] : '',
                'entreprise' => isset($membre['nom_entreprise']) ? $membre['nom_entreprise'] : '',
                'fonction' => isset($membre['fonction']) ? $membre['fonction'] : ''
            ];
        }, $membres)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
