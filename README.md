Système de Gestion d'une Salle de Sport

Guide d'Installation et d'Utilisation du Système de Gestion de Salle de Sport
Installation

Prérequis

PHP 8.2 ou supérieur
Composer
MySQL ou SQLite
Node.js et npm
Laravel Herd (ou autre environnement de développement)

Étapes d'installation

Cloner le code source
bashgit clone <repository-url> GestionSalleSport
cd GestionSalleSport

Installer les dépendances PHP
bashcomposer install

Installer les dépendances JavaScript
bashnpm install
npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/interaction
npm install chart.js lodash

Configurer l'environnement
bashcp .env.example .env
php artisan key:generate

Configurer la base de données

Ouvrir le fichier .env et configurer les informations de connexion à la base de données :
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gestion_salle_sport
DB_USERNAME=root
DB_PASSWORD=



Exécuter les migrations et seeders
bashphp artisan migrate
php artisan db:seed

Créer le lien symbolique pour le stockage
bashphp artisan storage:link

Compiler les assets
bashnpm run dev

Démarrer le serveur de développement
bashphp artisan serve

Accéder à l'application

Ouvrez votre navigateur et accédez à http://localhost:8000
Connectez-vous avec les identifiants par défaut :

Admin : admin@example.com / password
Manager : manager@example.com / password
Coach : pierre@example.com / password





Structure du Projet
Le projet suit l'architecture MVC (Modèle-Vue-Contrôleur) de Laravel :

app/Models/ : Contient les modèles Eloquent pour interagir avec la base de données
app/Http/Controllers/API/ : Contient les contrôleurs API pour gérer les requêtes
app/Http/Middleware/ : Contient les middlewares, notamment pour la gestion des rôles
database/migrations/ : Contient les migrations de base de données
database/seeders/ : Contient les seeders pour générer des données de test
resources/js/ : Contient les composants Vue.js et la logique frontend
resources/css/ : Contient les styles CSS
routes/api.php : Définit les routes API

Fonctionnalités Principales
1. Authentification et Gestion des Utilisateurs

Connexion/déconnexion sécurisée
Gestion des rôles (admin, manager, coach)
Profil utilisateur avec modification des informations et mot de passe

2. Gestion des Membres

Liste des membres avec filtrage, tri et pagination
Création, visualisation et modification des membres
Suivi des statuts des membres (actif, inactif, en attente)
Visualisation des abonnements et paiements par membre

3. Gestion des Abonnements

Différents types d'abonnements (mensuel, trimestriel, semestriel, annuel)
Suivi des dates de début/fin et du statut des abonnements
Renouvellement automatique d'abonnement configurable

4. Gestion des Réservations

Vue calendrier interactif (jour, semaine, mois)
Création et modification des réservations
Assignation de coach et d'équipements
Suivi des statuts des réservations (planifiée, terminée, annulée, absence)

5. Gestion des Services

Configuration des services proposés par la salle
Paramétrage des prix, durées et capacités

6. Gestion des Équipements

Suivi des équipements disponibles
Planification des maintenances
Association des équipements aux réservations

7. Gestion des Paiements

Enregistrement des paiements
Génération de factures
Suivi des statuts des paiements (payé, en attente, annulé, remboursé)

8. Tableau de Bord

Statistiques en temps réel
Graphiques et indicateurs clés de performance
Affichage adapté selon le rôle de l'utilisateur

Guide d'Utilisation
Pour l'Administrateur

Se connecter en tant qu'admin

Utiliser admin@example.com / password


Gérer les utilisateurs

Accéder au menu "Utilisateurs"
Créer de nouveaux managers et coachs
Modifier ou supprimer des utilisateurs existants


Configurer les services

Accéder au menu "Services"
Paramétrer les services proposés par la salle
Définir les prix, durées et capacités


Configurer les équipements

Accéder au menu "Équipements"
Ajouter de nouveaux équipements
Planifier les maintenances


Consulter les statistiques globales

Voir le tableau de bord administrateur
Analyser les revenus, fréquentation, etc.



Pour le Manager

Se connecter en tant que manager

Utiliser manager@example.com / password


Gérer les membres

Ajouter de nouveaux membres
Mettre à jour les informations des membres
Suivre les statuts et abonnements


Gérer les abonnements

Créer des abonnements pour les membres
Renouveler les abonnements
Consulter les abonnements actifs/expirés


Gérer les réservations

Utiliser le calendrier pour visualiser les réservations
Créer de nouvelles réservations
Modifier ou annuler des réservations


Gérer les paiements

Enregistrer les paiements des membres
Générer des factures
Suivre les paiements en attente



Pour le Coach

Se connecter en tant que coach

Utiliser pierre@example.com / password


Consulter les réservations

Voir les sessions planifiées
Visualiser les membres inscrits
Voir les équipements réservés


Mettre à jour les statuts des réservations

Marquer les sessions comme terminées
Signaler les absences de membres



Maintenance et Support
Sauvegarde de la Base de Données
bashphp artisan backup:run
Mise à Jour du Système
bashgit pull
composer install
php artisan migrate
npm install
npm run build
Résolution des Problèmes Courants

Problème de connexion

Vérifiez les informations de connexion
Assurez-vous que l'utilisateur existe en base de données


Erreur lors de la création/modification de réservation

Vérifiez les disponibilités des coachs et équipements
Vérifiez les contraintes de validation des données


Problème d'affichage du calendrier

Rafraîchissez la page
Videz le cache du navigateur


Erreur lors des paiements

Vérifiez que le membre a un abonnement actif
Assurez-vous que les informations de paiement sont correctes



Conclusion
Ce système de gestion de salle de sport offre une solution complète pour gérer efficacement les membres, les abonnements, les réservations et les paiements de votre établissement. Profitez de ses fonctionnalités pour optimiser vos opérations quotidiennes et améliorer l'expérience de vos clients et employés.
