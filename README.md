🍿 Bocal.app - Ne perdez plus 30 minutes à choisir un film
Bocal.app est une application web (Progressive Web App) conçue pour résoudre le dilemme classique des soirées cinéma. Elle permet de créer des listes de films collaboratives ("bocaux") et utilise une roulette intelligente pour tirer au sort le film parfait selon vos critères du moment.

✨ Fonctionnalités Principales
🎲 La Roulette Intelligente : Tirage au sort interactif parmi les films "À voir". Filtrage dynamique par genre, durée maximale, ou disponibilité sur les plateformes de streaming (Netflix, Prime, Canal+...).

👥 Listes Collaboratives : Création de groupes privés (bocaux), système d'invitation par codes uniques et gestion des droits partagés.

🎬 Intégration TMDB (The Movie Database) : Moteur de recherche natif avec autocomplétion, récupération automatique des affiches, synopsis, casting, et détection des plateformes de streaming françaises (via API REST).

📱 Progressive Web App (PWA) : Intégration d'un Manifest complet permettant l'installation de l'application sur mobile (iOS & Android) pour une expérience native.

⭐ Dimension Sociale : Système d'ajout d'amis, notation des films (sur 5 étoiles), gestion des profils et ajout de citations mémorables.

🛡️ Architecture & Bonnes Pratiques (Sécurité)
Ce projet a été restructuré avec une attention particulière portée aux standards de l'industrie et à la sécurité web :

Protection CSRF (Cross-Site Request Forgery) : Implémentation de jetons de sécurité cryptographiques sur toutes les requêtes sensibles et destructrices (suppression de films, départ d'une liste, modification de statuts) via hash_equals.

Isolation des Secrets : Les identifiants de base de données et les clés d'API sont strictement isolés dans un fichier config.php protégé et ignoré par Git (.gitignore). Un modèle config.example.php est fourni pour le déploiement.

Principe DRY (Don't Repeat Yourself) : Centralisation de la configuration et exposition sécurisée des variables globales (ex: TMDB_API_KEY) pour un code maintenable et évolutif côté serveur comme côté client.

Prévention des Injections SQL : Utilisation systématique de requêtes préparées via l'objet PDO de PHP.

UX/UI Moderne : Interface adaptative (Responsive Design) avec gestion de trois thèmes (Sombre, Clair, Néon) sauvegardés en cookies.

🛠️ Stack Technique
Backend : PHP (Architecture optimisée et sécurisée)

Base de données : MySQL / MariaDB (Interface PDO)

Frontend : HTML5, CSS3 (Variables CSS natives), JavaScript Vanilla (ES6+, Fetch API, animations fluides)

API Externe : TheMovieDB API v3

🚀 Installation & Déploiement en Local
Prérequis
Un serveur web local (XAMPP, MAMP, Laragon...)

PHP 7.4 ou supérieur

Une clé API gratuite TheMovieDB

Étapes d'installation
Cloner le dépôt

Bash
git clone https://github.com/votre-nom/bocal-app.git
cd bocal-app
Configuration de l'environnement sécurisé

Copiez le fichier config.example.php et renommez-le en config.php.

Renseignez vos identifiants MySQL et votre clé API TMDB dans ce nouveau fichier. Ce fichier ne sera jamais poussé sur GitHub.

Base de données

Créez une base de données MySQL vide.

Importez la structure des tables (le fichier .sql de sauvegarde de votre base).

Lancement

Accédez au dossier du projet via votre serveur local (ex: http://localhost/bocal-app).

Développé avec passion pour mettre fin aux débats interminables de soirées ciné. 🍿