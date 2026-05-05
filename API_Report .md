# Rapport Technique et Architecture: FAL-PMS

## Présentation Générale
FAL-PMS est un système de gestion de projet (Project Management System) développé avec Laravel. Il intègre une partie back-office via Filament PHP, et un espace client / front-office avec Blade, Tailwind CSS et AlpineJS. L'objectif de l'application est de faciliter la gestion collaborative de tâches, à la manière de Jira ou Trello, avec des fonctionnalités interactives comme des tableaux Kanban.

## Stack Technique
- **Backend:** Laravel 12.0 (PHP ^8.2)
- **Frontend Admin:** Filament 3.0 (TALL Stack: Tailwind, Alpine, Laravel, Livewire)
- **Frontend Client:** Moteur de template Blade, TailwindCSS pour le style, Alpine.js pour la réactivité, avec Vite comme bundler.
- **Base de données:** SQLite (par défaut, optimisé pour un déploiement et des tests rapides, mais compatible avec MySQL/PostgreSQL).
- **Temps réel / WebSockets:** Laravel Echo et Pusher (via `pusher/pusher-php-server`), gérés par des services de broadcast internes.
- **Génération de PDF:** Package `barryvdh/laravel-dompdf` pour les exports de rapports.

## Architecture Logicielle

L'architecture est structurée de manière standard selon les conventions Laravel (MVC), augmentée de patterns adaptés aux applications complexes :

### Modèles de Données (Models)
L'application repose sur les modèles principaux suivants :
- **User :** Représente les utilisateurs de l'application (Administrateurs, Managers, Membres). Il dispose d'un système de rôles et de drapeaux d'activité.
- **Project :** Les projets collaboratifs. Chaque projet a un "owner" (propriétaire) et des "members" avec une table pivot (`project_user`) gérant le rôle spécifique du membre dans le projet et son statut d'activation.
- **Task :** Les tâches liées aux projets. Elles gèrent un cycle de vie statutaire normalisé (ex: `todo`, `doing`, `done`), la position (pour le drag & drop) et sont assignables aux membres du projet.
- **TaskComment :** Permet la collaboration et la communication contextuelle autour des tâches.
- **ActivityLog :** Enregistre l'historique complet des actions (création de projet, modification de tâche, assignation, etc.) pour des besoins d'audit de sécurité et pour alimenter la chronologie (timeline).

### Gestion Événementielle (Observers et Notifications)
FAL-PMS utilise activement les Observers Laravel pour découpler et isoler la logique métier secondaire (comme la création d'historique ou l'envoi de notifications par e-mail) de la logique de contrôle principale :
- `ProjectObserver` : Gère les événements du cycle de vie d'un projet.
- `TaskObserver` : Logique métier critique de la tâche. Normalise le statut des tâches, gère le timestamp de complétion (`completed_at`), met à jour les positions, et déclenche de façon asynchrone les notifications par email (assignation, changement de statut, etc.) via le composant `Illuminate\Notifications`.
- `TaskCommentObserver` : Gère le flux d'activité généré par les nouveaux commentaires.

### Interfaces et Contrôleurs
L'application offre deux portails d'accès distincts :
- **Admin Portal (`/abba/*`) :** Ce portail d'administration est construit entièrement avec Filament PHP. Il gère de façon CRUD (Create, Read, Update, Delete) l'ensemble de l'écosystème : utilisateurs, projets et tâches avec des interfaces riches (tableaux de données, formulaires, widgets statistiques).
- **Client Portal (`/client/*`) :** Interface web orientée utilisateur, gérée par des contrôleurs standards (ex: `ProjectController`, `TaskController`) dans le namespace `App\Http\Controllers\Client`. Ce portail sert des vues Blade enrichies avec Alpine.js, permettant un rendu côté serveur rapide complété d'interactions côté client (modales, listes dynamiques).

### Gestion du Temps Réel
L'application est conçue pour être hautement collaborative et interactive. Elle utilise les websockets pour diffuser des événements (Events) en temps réel via Pusher. Ceci permet de mettre à jour le board Kanban, le tableau de bord et le fil des commentaires sans rafraîchissement de page, en utilisant deux broadcaster personnalisés : `DashboardBroadcaster` et `WorkspaceBroadcaster`.












# Documentation API et Endpoints : FAL-PMS

L'application FAL-PMS est principalement une application monolithique à rendu côté serveur (Server-Side Rendered), complétée par des endpoints AJAX internes nécessaires pour la réactivité de l'interface client (notamment pour les tableaux Kanban et le rafraîchissement en temps réel).

*Note : Les endpoints mentionnés ci-dessous nécessitent que l'utilisateur soit authentifié via la session web Laravel. Les réponses AJAX utilisent le format JSON.*

---

## 1. Authentification
*Basé sur Laravel Breeze.*
- `GET|POST /login` : Authentification utilisateur.
- `POST /logout` : Déconnexion.
- `GET|POST /register` : Inscription d'un nouvel utilisateur.
- `POST /forgot-password` / `POST /reset-password` : Flux de réinitialisation de mot de passe.

---

## 2. Portail Client (Endpoints Web & AJAX)
*Préfixe de route : `/client`*

### Tableau de Bord (Dashboard)
- `GET /client/dashboard`
  - **Description :** Affiche le tableau de bord principal de l'utilisateur.
- `GET /client/dashboard/snapshot`
  - **Description :** Endpoint AJAX retournant l'état actuel des données globales pour le rafraîchissement asynchrone du dashboard.
  - **Retour :** `JSON` (Version du snapshot, métriques, etc.)

### Gestion des Projets
- `GET /client/projects`
  - **Description :** Liste paginée des projets de l'utilisateur.
- `GET /client/projects/{project}`
  - **Description :** Vue détaillée d'un projet, incluant le tableau Kanban, la timeline et les informations générales.
- `GET /client/projects/{project}/snapshot`
  - **Description :** Endpoint AJAX interne appelé par AlpineJS/Echo pour synchroniser les données d'un projet en temps réel.
  - **Retour :** `JSON` contenant `version`, `status_counts`, `board_columns` (tâches), `stats`, `timeline`, et `recent_comments`.

### Gestion des Membres d'un Projet
Ces endpoints manipulent la table pivot des projets.
- `POST /client/projects/{project}/members`
  - **Description :** Ajouter un nouveau membre à un projet.
  - **Payload :** `user_id` (int), `role` (string), `is_active` (boolean).
- `PATCH /client/projects/{project}/members/{member}`
  - **Description :** Mettre à jour le rôle ou le statut de présence d'un membre.
- `DELETE /client/projects/{project}/members/{member}`
  - **Description :** Retirer un membre du projet.

### Gestion des Tâches et Board Kanban
- `GET /client/tasks`
  - **Description :** Liste ou vue de toutes les tâches assignées.
- `GET /client/tasks/calendar`
  - **Description :** Vue calendrier des tâches selon leurs dates d'échéance.
- `POST /client/tasks/{task}/comments`
  - **Description :** Soumettre un nouveau commentaire sur une tâche spécifique.
  - **Payload :** `body` (string).
- `POST /client/projects/{project}/tasks/{task}/move`
  - **Description :** Point d'entrée AJAX utilisé par l'interface drag & drop pour modifier l'état et la position d'une tâche.
  - **Payload attendu :** 
    ```json
    {
      "status": "todo|doing|done",
      "position": 1
    }
    ```
  - **Réponse :** 
    ```json
    {
      "ok": true,
      "task_id": 12,
      "status": "doing",
      "position": 1,
      "snapshot_version": "a3b2c..."
    }
    ```

### Rapports de Projet
- `GET /client/projects/{project}/report`
  - **Description :** Vue détaillée des statistiques et du rapport d'un projet.
- `GET /client/projects/{project}/report/download`
  - **Description :** Génère et télécharge le rapport au format Excel/CSV.
- `GET /client/projects/{project}/report/download/pdf`
  - **Description :** Génère et télécharge le rapport au format PDF via `dompdf`.

---

## 3. Portail Administration (Filament PHP)
*Préfixe de route : `/abba`*

L'ensemble de ces routes est généré automatiquement par le framework Filament pour gérer les opérations CRUD complètes :
- `GET /abba/admin-dashboard` : Tableau de bord d'administration global.
- `GET|POST /abba/projects/*` : Ressources complètes de gestion de projets (Index, Create, Edit, Report).
- `GET|POST /abba/tasks/*` : Ressources complètes des tâches (Index, Create, Edit, vues Kanban et Calendar).
- `GET|POST /abba/users/*` : Ressources complètes de gestion des utilisateurs.
