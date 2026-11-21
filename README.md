<div align="center">
  <h1>🍿 Bocal.app</h1>
  <p><em>Ne perdez plus 30 minutes à choisir un film.</em></p>
  
  ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
  ![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
  ![PWA](https://img.shields.io/badge/PWA-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)
  ![TMDB](https://img.shields.io/badge/TMDB_API-01B4E4?style=for-the-badge&logo=themoviedb&logoColor=white)
</div>

---

**Bocal.app** est une application web (Progressive Web App) conçue pour résoudre le dilemme classique des soirées cinéma. Elle permet de créer des listes de films collaboratives ("bocaux") et utilise une **roulette intelligente** pour tirer au sort le film parfait selon vos critères du moment.

## 🔑 Tester l'application

L'application est en ligne : **[URL_DU_SITE](URL_DU_SITE)**

Un compte de démonstration est disponible pour la tester sans inscription :

| | |
| :--- | :--- |
| **Identifiant** | `demo@bocal.app` |
| **Mot de passe** | `1234` |

## ✨ Fonctionnalités Principales

| Fonctionnalité | Description |
| :--- | :--- |
| **🎲 Roulette Intelligente** | Tirage au sort interactif parmi les films "À voir". Filtrage dynamique par genre, durée maximale ou plateforme de streaming (Netflix, Prime, Canal+...). |
| **👥 Listes Collaboratives** | Création de groupes privés (bocaux), système d'invitation par codes uniques et gestion des droits partagés. |
| **🎬 Intégration TMDB** | Moteur de recherche natif avec autocomplétion, récupération automatique des affiches, synopsis, casting et détection des plateformes de streaming françaises. |
| **📱 PWA Ready** | Intégration d'un Manifest complet permettant l'installation de l'application sur mobile (iOS & Android) pour une expérience native. |
| **⭐ Dimension Sociale** | Système d'ajout d'amis, notation des films (sur 5 étoiles), gestion des profils et ajout de citations mémorables. |

## 🛡️ Architecture & Sécurité

Ce projet est structuré selon les standards de l'industrie avec une attention particulière portée à la sécurité web :

*   **Protection CSRF :** Implémentation de jetons de sécurité cryptographiques sur toutes les requêtes sensibles et destructrices (suppression de films, départ d'une liste, actions diverses) validés via `hash_equals`.
*   **Isolation des Secrets :** Les identifiants de base de données et les clés d'API sont strictement isolés dans un fichier `config.php` non versionné (via `.gitignore`). Un modèle `config.example.php` est fourni pour faciliter le déploiement.
*   **Principe DRY (Don't Repeat Yourself) :** Centralisation de la configuration et exposition sécurisée des variables globales pour un code maintenable côté serveur et client.
*   **Prévention SQLi :** Utilisation systématique de requêtes préparées via l'interface PDO de PHP.
*   **UX/UI Moderne :** Interface adaptative avec gestion de trois thèmes (Sombre, Clair, Néon) sauvegardés en cookies.

## 🚀 Installation & Déploiement

**1. Cloner le dépôt**
```bash
git clone https://github.com/Emile-Dumont/BocalDuFun.git
cd BocalDuFun
```

**2. Configuration de l'environnement**
* Dupliquez le fichier `config.example.php` à la racine et renommez-le en `config.php`.
* Renseignez vos identifiants MySQL et votre clé API TMDB à l'intérieur.

**3. Base de données**
* Importez la structure SQL de votre sauvegarde dans une base de données MySQL ou MariaDB vide.

**4. Lancement**
* Accédez au dossier du projet via votre serveur web local (ex: XAMPP, Laragon) ou déployez sur votre espace d'hébergement.

---
*Développé avec passion pour mettre fin aux débats interminables de soirées ciné.* 🍿

