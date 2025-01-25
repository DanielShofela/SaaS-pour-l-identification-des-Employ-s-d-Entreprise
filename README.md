# Système de Gestion du Personnel

Ce projet est une application web PHP permettant la gestion des fiches d'identification du personnel d'une entreprise.

## Fonctionnalités

- Système d'authentification administrateur
- Gestion des inscriptions du personnel
- Interface d'administration
- Gestion des entreprises
- Génération de documents PDF (utilisant DOMPDF)

## Structure du Projet

- `admin/` - Interface d'administration et fonctionnalités associées
- `config/` - Fichiers de configuration de l'application
- `dompdf/` - Bibliothèque pour la génération de PDF
- `entreprise/` - Gestion des données des entreprises
- `lib/` - Bibliothèques et fonctions utilitaires
- `temp_images/` - Stockage temporaire des images
- `index.php` - Point d'entrée de l'application
- `inscription.php` - Gestion des inscriptions
- `logout.php` - Déconnexion du système

## Prérequis

- PHP 7.0 ou supérieur
- Serveur Web (Apache recommandé)
- MySQL/MariaDB
- Extensions PHP requises :
  - PDO
  - GD (pour la manipulation d'images)
  - DOM (pour la génération PDF)

## Installation

1. Clonez le dépôt dans votre répertoire web
2. Configurez votre base de données dans le dossier `config/`
3. Assurez-vous que les permissions des dossiers sont correctement configurées

### Installation de DOMPDF

DOMPDF est nécessaire pour la génération des documents PDF. Voici les étapes d'installation :

1. **Via Composer (Recommandé)**
   ```bash
   composer require dompdf/dompdf
   ```

2. **Installation Manuelle**
   - Téléchargez DOMPDF depuis le dépôt officiel : https://github.com/dompdf/dompdf
   - Extrayez les fichiers dans le dossier `dompdf/` de votre projet
   - Assurez-vous que la structure suivante est respectée :
     ```
     dompdf/
     ├── lib/
     ├── src/
     ├── autoload.inc.php
     └── dompdf.php
     ```

### Configuration de DOMPDF

1. **Configuration du fichier PHP**
   - Augmentez la limite de mémoire dans votre `php.ini` :
     ```ini
     memory_limit = 128M
     max_execution_time = 300
     ```

2. **Dans votre code PHP**
   ```php
   require_once 'dompdf/autoload.inc.php';
   use Dompdf\Dompdf;
   use Dompdf\Options;

   // Configuration
   $options = new Options();
   $options->set('isHtml5ParserEnabled', true);
   $options->set('isPhpEnabled', true);
   $options->set('isRemoteEnabled', true);

   $dompdf = new Dompdf($options);
   ```

3. **Permissions des dossiers**
   - Donnez les permissions d'écriture au dossier temp de DOMPDF :
     ```bash
     chmod 777 dompdf/lib/fonts
     ```

4. **Vérification de l'installation**
   - Créez un fichier test.php avec ce code :
   ```php
   <?php
   require_once 'dompdf/autoload.inc.php';
   use Dompdf\Dompdf;
   
   $dompdf = new Dompdf();
   $dompdf->loadHtml('<h1>Test PDF</h1>');
   $dompdf->render();
   $dompdf->stream("test.pdf");
   ```

### Dépannage DOMPDF

- Si les polices ne s'affichent pas correctement :
  - Vérifiez que le dossier `dompdf/lib/fonts` est accessible en écriture
  - Exécutez le script de construction des polices : `php dompdf/load_font.php`
- Si les images ne s'affichent pas :
  - Vérifiez que `isRemoteEnabled` est activé dans les options
  - Utilisez des chemins absolus pour les images

## Sécurité

- Authentification requise pour l'accès administrateur
- Protection contre les injections SQL via PDO
- Validation des données utilisateur

## Maintenance

Pour maintenir l'application :
- Effectuez régulièrement des sauvegardes de la base de données
- Mettez à jour les bibliothèques (notamment DOMPDF)
- Vérifiez régulièrement les logs d'erreur

## Support

Pour toute question ou problème, veuillez contacter l'administrateur système.
