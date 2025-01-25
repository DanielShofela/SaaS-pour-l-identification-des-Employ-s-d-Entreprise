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
4. Accédez à l'application via votre navigateur

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
