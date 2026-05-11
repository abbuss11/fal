# Rapport Technique et Cartographie Fonctionnelle: FAL-PMS

## 1) Presentation generale

FAL-PMS est un systeme de gestion de projet collaboratif construit avec Laravel 12.
Il expose:

- un portail `Client` pour le travail quotidien des equipes projet
- un portail `Admin` base sur Filament pour la gouvernance et l administration
- une API REST `v1` pour clients mobiles (ex: Flutter)

Le projet couvre deja une grande partie des besoins type Jira/Trello, avec une architecture orientee collaboration temps reel.

## 2) Stack technique

- Backend: Laravel 12 (PHP ^8.2)
- Admin UI: Filament 3 (TALL stack)
- Client UI: Blade + Tailwind + Alpine + Vite
- Data: MySQL/MariaDB ou SQLite
- Realtime: Laravel Echo + Pusher protocol (Pusher/Soketi)
- Reporting PDF: `barryvdh/laravel-dompdf`
- Notifications push mobiles: FCM via canal custom

## 3) Architecture logicielle

### 3.1 Modeles principaux

- `User`: roles globaux, permissions directes, profil et activite (`is_active`, `last_seen_at`)
- `Project`: owner, membres, budget, statut, priorite, dates, objectif
- `Task`: status, priorite, lane review (`is_in_review`), estimation, position board
- `TaskSubtask`: sous-taches ordonnees avec suivi completion
- `TaskComment` + `CommentMention`: collaboration contextuelle et mentions `@user`
- `ProjectMessage`: chat interne projet
- `ProjectFile`: gestion documentaire avec versioning
- `Timesheet`: suivi du temps par utilisateur/projet/tache
- `ActivityLog`: audit et timeline
- `ApiToken` + `MobileDeviceToken`: auth API et push mobile

### 3.2 RBAC et securite

- Roles globaux actuellement definis:
  - `admin`
  - `project_manager`
  - `member`
- Permissions fines via table `permissions` + pivot `permission_user`
- Middleware `permission:*` pour proteger les routes sensibles
- Auth session web (Breeze) + email verification
- Auth API token (`Bearer`) pour mobile

### 3.3 Temps reel et diffusion

- Broadcasters applicatifs:
  - `DashboardBroadcaster`
  - `WorkspaceBroadcaster`
- Evenements principaux:
  - `UserDashboardUpdated`
  - `ProjectWorkspaceUpdated`
- Usage:
  - refresh dashboard sans reload
  - sync board kanban
  - sync timeline/commentaires/messages

### 3.4 Notifications

Canaux actifs:

- `mail`
- `database`
- `broadcast`
- `MobilePushChannel` (FCM)

Triggers couverts:

- assignation tache
- changement statut tache
- commentaire tache
- mise a jour tache/projet
- partage fichier
- message projet
- mention utilisateur

## 4) Cartographie des 17 modules (etat mai 2026)

Legende:
- `Livre`: implemente et exploitable
- `Partiel`: present mais incomplet
- `Roadmap`: non implemente

| # | Module | Etat | Couverture actuelle / adaptation |
|---|---|---|---|
| 1 | Authentification & gestion utilisateurs | Partiel | Login/register/reset/email verify + RBAC + profil + activite + roles `manager` et `client`. A ajouter: Google OAuth, 2FA. |
| 2 | Dashboard | Livre | KPIs projet/tache, retards, notifications, velocite, charge, snapshots realtime. |
| 3 | Gestion des projets | Partiel | CRUD complet, membres, statut/priorite/budget/dates, rapport, archivage, duplication, template, liaison client. A ajouter: cycle contractuel client complet. |
| 4 | Gestion des taches | Partiel | CRUD, sous-taches, assignation, priorite, echeance, commentaires, estimation, dependances et tags. A ajouter: temps passe par tache et checklist avancee. |
| 5 | Kanban Board | Partiel | Drag & drop realtime, lanes Todo/Doing/Review/Done, filtres. A ajouter: WIP limit, swimlanes, colonnes custom administrees. |
| 6 | Agile / Scrum | Partiel | Mode Scrum UI + metriques backlog/sprint + velocite hebdo. A ajouter: sprint planning, story points, burndown, retrospectives, releases. |
| 7 | Calendrier & planning | Partiel | Calendrier taches + deadlines. A ajouter: sync Google Calendar, rappels auto, disponibilites equipe. |
| 8 | Gantt chart | Roadmap | Timeline textuelle existe mais pas de Gantt interactif ni chemin critique. |
| 9 | Collaboration & communication | Partiel | Commentaires, mentions, chat projet, historique, partage fichiers. A ajouter: reactions emoji, threads profonds. |
| 10 | Notifications | Partiel | Temps reel + email + push + inbox database + preferences utilisateur (email/realtime/push). A ajouter: preferences par type d evenement. |
| 11 | Gestion documentaire | Partiel | Upload + versioning + download securise. A ajouter: preview PDF/image, recherche documentaire, dossiers. |
| 12 | Time tracking | Partiel | Timesheets web/API, aggregation dans rapports. A ajouter: timer live, facturable/non facturable, productivite avancee. |
| 13 | Rapports & analytics | Partiel | Rapport detaille + JSON/PDF, KPI de charge/perf/progress. A ajouter: CSV/Excel, heatmaps, analytics timeline avancees. |
| 14 | Gestion des equipes | Partiel | Membres projet, role projet, actif/inactif. A ajouter: departements, org chart, groupes transverses. |
| 15 | Gestion des clients | Partiel | Entite client et affectation aux projets disponibles. A ajouter: portail client externe, validation livrables, facturation, support. |
| 16 | Systeme fichiers & medias | Partiel | Fichiers versionnes avec metadata. A ajouter: CDN, compression auto, streaming media. |
| 17 | Recherche intelligente | Partiel | Recherche locale par ecran + filtre tag sur taches. A ajouter: moteur global full-text + suggestions. |

## 5) Documentation API (etat actuel)

Base prefix: `/api/v1`

### 5.1 Auth API token

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me` (auth token)
- `POST /auth/logout` (auth token)

### 5.2 Projets

- `GET /projects`
- `GET /projects/{project}`

### 5.3 Taches

- `GET /tasks`
- `PATCH /tasks/{task}/status`

### 5.4 Notifications

- `GET /notifications`
- `PATCH /notifications/{notificationId}/read`

### 5.5 Push mobile

- `POST /mobile/device-token`
- `POST /mobile/device-token/revoke`

### 5.6 Timesheets

- `GET /timesheets`
- `POST /timesheets`
- `PATCH /timesheets/{timesheet}`
- `DELETE /timesheets/{timesheet}`

## 6) Endpoints web client (principaux)

Prefix: `/client`

- Dashboard:
  - `GET /dashboard`
  - `GET /dashboard/snapshot`
- Projets:
  - `GET /projects`
  - `GET /projects/{project}`
  - `GET /projects/{project}/snapshot`
  - `POST /projects/{project}/tasks/{task}/move`
  - `POST /projects/{project}/members`
  - `PATCH /projects/{project}/members/{member}`
  - `DELETE /projects/{project}/members/{member}`
- Communication:
  - `POST /projects/{project}/messages`
  - `POST /projects/{project}/files`
  - `GET /projects/{project}/files/{projectFile}/download`
- Taches:
  - `GET /tasks`
  - `GET /tasks/calendar`
  - `POST /tasks/{task}/comments`
  - `POST /tasks/{task}/subtasks`
  - `PATCH /tasks/{task}/subtasks/{subtask}`
- Rapports:
  - `GET /projects/{project}/report`
  - `GET /projects/{project}/report/download` (JSON)
  - `GET /projects/{project}/report/download/pdf`
- Timesheets:
  - `GET /timesheets`
  - `POST /timesheets`
  - `PATCH /timesheets/{timesheet}`
  - `DELETE /timesheets/{timesheet}`

## 7) Roadmap technique conseillee

### Phase A - Identite et acces

- Ajouter roles `manager` et `client` (global + projet)
- OAuth Google (Socialite)
- 2FA TOTP + recovery codes
- Preferences de notifications par utilisateur

### Phase B - Agile avance

- Entite `sprints` (planning, objectif, statut)
- Entite `stories` (points, priorite, sprint)
- Burndown chart et velocity par sprint
- WIP limits parametrables

### Phase C - Planification et pilotage

- Gantt interactif
- Dependances taches
- Milestones
- Alerting retard et chemin critique

### Phase D - Experience client externe

- Entite `clients` + portail dedie
- Validation livrables
- Facturation et suivi support
- Exports CSV/Excel et recherche globale full-text
