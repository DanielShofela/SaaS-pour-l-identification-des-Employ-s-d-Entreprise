<?php
// Vérifier qu'aucune sortie n'a été envoyée
if (headers_sent($filename, $linenum)) {
    die("Des en-têtes ont déjà été envoyées par $filename à la ligne $linenum");
}

// Inclusion de la bibliothèque DOMPDF et de la configuration de la base de données
require_once '../lib/autoload.inc.php';
require_once '../config/database.php';
require_once '../config/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Activer la gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la mise en tampon de sortie
ob_start();

if (!isset($_GET['id'])) {
    die('ID non fourni');
}

try {
    // Connexion à la base de données
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT 
        p.*,
        p.id,
        p.matricule,
        p.code,
        p.nom,
        p.prenoms,
        p.datenaiss,
        p.lieunaiss,
        p.comite_local,
        p.fonction,
        p.num_cni_passeport,
        p.profession,
        p.group_sang,
        p.ville,
        p.commune,
        p.quartier,
        p.situ_matri,
        p.tel,
        p.email,
        p.num_wa,
        p.mob_money,
        p.diplome,
        p.niv_etude,
        p.qual_prof,
        p.en_activite,
        p.pernis,
        p.categorie,
        p.urg_nom_prenom,
        p.urg_ville_habite,
        p.urg_commune,
        p.urg_quartier,
        p.urg_tel1,
        p.urg_tel2,
        e.nom as nom_entreprise,
        p.photo
    FROM personne p 
    LEFT JOIN entreprise e ON p.entreprise_id = e.id 
    WHERE p.id = ?");
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die('Employé non trouvé');
    }

    // Création du contenu HTML pour le PDF
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>ETAT CIVIL</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 20px;
                font-size: 11pt;
            }
            .header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
                border-bottom: 1px solid #000;
                padding-bottom: 10px;
            }
            .matricule, .code {
                text-transform: uppercase;
                font-weight: bold;
                padding: 5px 10px;
                background-color: #f0f0f0;
                border: 1px solid #000;
            }
            .photo-box {
                float: right;
                width: 120px;
            }
            .photo-container {
                width: 100px;
                height: 120px;
                border: 1px solid #000;
                text-align: center;
                margin-bottom: 10px;
                overflow: hidden;
            }
            .photo-container img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .signature {
                width: 100px;
                height: 40px;
                border: 1px solid #000;
                text-align: center;
                font-size: 8pt;
                padding: 2px;
            }
            .main-content {
                margin-right: 130px;
            }
            .form-group {
                margin-bottom: 8px;
            }
            .form-group label {
                font-weight: bold;
            }
            .section-title {
                text-transform: uppercase;
                font-weight: bold;
                margin-top: 15px;
                margin-bottom: 10px;
                padding: 5px;
                background-color: #f0f0f0;
            }
            .checkbox-group {
                margin: 10px 0;
            }
            .checkbox-group label {
                font-weight: bold;
            }
            .checkbox {
                margin-right: 15px;
                border: 1px solid #000;
                padding: 2px 5px;
            }
            .address-group {
                margin-left: 20px;
            }
            .footer {
                margin-top: 30px;
                text-align: right;
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="matricule">MATRICULE : ' . htmlspecialchars($data['matricule']) . '</div>
            <div class="code">CODE : ' . htmlspecialchars($data['code']) . '</div>
        </div>

        <div class="photo-box">
            <div class="photo-container">
                ' . (!empty($data['photo']) ? '<img src="data:image/jpeg;base64,' . base64_encode($data['photo']) . '" alt="Photo">' : 'photo') . '
            </div>
            <div class="signature">signature du volontaire</div>
        </div>

        <div class="main-content">
            <div class="form-group">
                <label>NOM : </label>
                ' . htmlspecialchars($data['nom']) . '
            </div>
            <div class="form-group">
                <label>PRENOMS : </label>
                ' . htmlspecialchars($data['prenoms']) . '
            </div>
            <div class="form-group">
                <label>DATE et LIEU de NAISSANCE : </label>
                ' . htmlspecialchars($data['datenaiss']) . ' à ' . htmlspecialchars($data['lieunaiss']) . '
            </div>
            <div class="form-group">
                <label>COMITE LOCAL : </label>
                ' . htmlspecialchars($data['comite_local']) . '
            </div>
            <div class="form-group">
                <label>FONCTION CIVILE : </label>
                ' . htmlspecialchars($data['fonction']) . '
            </div>
            <div class="form-group">
                <label>N° CNI / PASSEPORT : </label>
                ' . htmlspecialchars($data['num_cni_passeport']) . '
            </div>
            <div class="form-group">
                <label>PROFESSION : </label>
                ' . htmlspecialchars($data['profession']) . '
            </div>
            <div class="form-group">
                <label>GROUPE SANGUIN : </label>
                ' . htmlspecialchars($data['group_sang']) . '
            </div>

            <div class="section-title">LIEU D\'HABITATION</div>
            <div class="address-group">
                <div class="form-group">
                    <label>VILLE : </label>
                    ' . htmlspecialchars($data['ville']) . '
                </div>
                <div class="form-group">
                    <label>COMMUNE : </label>
                    ' . htmlspecialchars($data['commune']) . '
                </div>
                <div class="form-group">
                    <label>QUARTIER : </label>
                    ' . htmlspecialchars($data['quartier']) . '
                </div>
            </div>

            <div class="section-title">SITUATION MATRIMONIALE</div>
            <div class="form-group" style="margin-left: 20px;">
                ' . htmlspecialchars($data['situ_matri']) . '
            </div>

            <div class="section-title">CONTACTS</div>
            <div class="address-group">
                <div class="form-group">
                    <label>TELEPHONE : </label>
                    ' . htmlspecialchars($data['tel']) . '
                </div>
                <div class="form-group">
                    <label>Email : </label>
                    ' . htmlspecialchars($data['email']) . '
                </div>
                <div class="form-group">
                    <label>NUMERO WHATSAPP : </label>
                    ' . htmlspecialchars($data['num_wa']) . '
                </div>
                <div class="form-group">
                    <label>MOBILE MONEY : </label>
                    ' . htmlspecialchars($data['mob_money']) . '
                </div>
            </div>

            <div class="section-title">INFOS PERSONNELLES</div>
            <div class="address-group">
                <div class="form-group">
                    <label>DIPLOME : </label>
                    ' . htmlspecialchars($data['diplome']) . '
                </div>
                <div class="form-group">
                    <label>NIVEAU D\'ETUDE : </label>
                    ' . htmlspecialchars($data['niv_etude']) . '
                </div>
                <div class="form-group">
                    <label>QUALIFICATION PROFESSIONNELLE : </label>
                    ' . htmlspecialchars($data['qual_prof']) . '
                </div>
                <div class="form-group">
                    <label>ETES-VOUS PRESENTEMENT EN ACTIVITE : </label>
                    ' . ($data['en_activite'] == '1' ? 'OUI' : 'NON') . '
                </div>
                <div class="form-group">
                    <label>AVEZ-VOUS UN PERMIS DE CONDUIRE : </label>
                    ' . ($data['pernis'] == '1' ? 'OUI' : 'NON') . '
                </div>
                <div class="form-group">
                    <label>CATEGORIE : </label>
                    ' . htmlspecialchars($data['categorie']) . '
                </div>
            </div>

            <div class="section-title">PERSONNE A CONTACTER EN CAS D\'URGENCE</div>
            <div class="address-group">
                <div class="form-group">
                    <label>NOM et PRENOMS : </label>
                    ' . htmlspecialchars($data['urg_nom_prenom']) . '
                </div>
                <div class="form-group">
                    <label>VILLE D\'HABITATION : </label>
                    ' . htmlspecialchars($data['urg_ville_habite']) . '
                </div>
                <div class="form-group">
                    <label>COMMUNE : </label>
                    ' . htmlspecialchars($data['urg_commune']) . '
                </div>
                <div class="form-group">
                    <label>QUARTIER : </label>
                    ' . htmlspecialchars($data['urg_quartier']) . '
                </div>
                <div class="form-group">
                    <label>TELEPHONE 1 : </label>
                    ' . htmlspecialchars($data['urg_tel1']) . '
                </div>
                <div class="form-group">
                    <label>TELEPHONE 2 : </label>
                    ' . htmlspecialchars($data['urg_tel2']) . '
                </div>
            </div>

            <div class="footer">
                <p>Fait à ABIDJAN, le ' . date('d/m/Y') . '</p>
            </div>
        </div>
    </body>
    </html>';

    // Nettoyer toute sortie mise en tampon
    ob_end_clean();

    // Configuration de DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    
    try {
        $dompdf->render();
        $output = $dompdf->output();
        
        // Envoyer les en-têtes appropriées
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private');
        header('Pragma: private');
        header('Expires: 0');
        
        // Envoyer le PDF
        echo $output;
        exit();
    } catch (Exception $e) {
        die("Erreur lors de la génération du PDF : " . $e->getMessage());
    }

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
} catch(Exception $e) {
    die("Erreur inattendue : " . $e->getMessage());
}
?>
