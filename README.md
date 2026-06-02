# FAL PMS (Futuristic Africa Lab - Project Manager)

Application web de gestion de projets collaboratifs avec separation claire en 2 espaces:

- `Client` pour equipes projet
- `Admin Filament` pour administration complete

## 1) Cartographie des 17 modules (adaptation FAL-PMS)

Legende:
- `Livre` = deja disponible dans le code
- `Partiel` = disponible en partie, extension recommandee
- `Roadmap` = non implemente pour le moment

| # | Module | Etat | Adaptation FAL-PMS |
|---|---|---|---|
| 1 | Authentification & utilisateurs | Partiel | Login/register/reset/email verify, RBAC, profil (avatar, phone, bio), `is_active` + `last_seen_at`, roles `admin/manager/project_manager/member/client`. A ajouter: Google OAuth, 2FA. |
| 2 | Dashboard | Livre | Vue globale projets/taches, retards, notifications, velocite, charge projet, tableaux KPI, refresh temps reel via snapshot + websocket. |
| 3 | Gestion des projets | Partiel | Creation/edition, owner, membres, priorite, budget, dates, statut, rapport projet, archivage/desarchivage, duplication, support template (`is_template`) et liaison client. A ajouter: cycle template avance et gouvernance contractuelle complete. |
| 4 | Gestion des taches | Partiel | CRUD admin + suivi client, sous-taches, assignation, priorites, echeances, commentaires, lane review, estimation heures, tags/labels, dependances. A ajouter: temps reel passe par tache et checklist enrichie. |
| 5 | Kanban Board | Partiel | Drag & drop live, colonnes `todo/doing/review/done`, filtre texte, mode Scrum/Kanban. A ajouter: WIP limits strictes, swimlanes, personnalisation avancee des colonnes. |
| 6 | Agile / Scrum | Partiel | Mode Scrum visuel, backlog/sprint counters, courbes de velocite. A ajouter: sprint planning, story points, retro, release management formel. |
| 7 | Calendrier & planning | Partiel | Vue calendrier des taches + deadlines et agenda projet. A ajouter: sync Google Calendar, rappels auto, disponibilites equipe. |
| 8 | Gantt Chart | Roadmap | Timeline existe, mais pas encore de diagramme Gantt avec dependances, jalons, chemin critique. |
| 9 | Collaboration & communication | Partiel | Commentaires taches, mentions `@user`, chat projet, historique, partage de fichiers versionnes. A ajouter: reactions emoji, threads imbriques complets. |
| 10 | Notifications | Partiel | Notifications multi-canaux `mail + database + broadcast + mobile push` + preferences utilisateur (email/realtime/push). A ajouter: preferences fines par type d evenement. |
| 11 | Gestion documentaire | Partiel | Upload, versioning par `logical_name`, telechargement securise, liaison projet/tache. A ajouter: preview PDF/image, recherche documentaire avancee, dossiers hierarchiques. |
| 12 | Time tracking | Partiel | Timesheets web + API (create/update/delete), heures agregees dans reporting. A ajouter: chronometre start/stop, heures facturables, rapport de productivite avance. |
| 13 | Rapports & analytics | Partiel | Rapport detaille + export JSON/PDF, KPI, charge membres, performance equipe, timeline activites, velocite hebdo. A ajouter: export CSV/Excel, heatmaps, analytics timeline avancees. |
| 14 | Gestion des equipes | Partiel | Membres par projet, role projet, actif/inactif, controle d acces. A ajouter: departements, organigramme, groupes transverses. |
| 15 | Gestion des clients | Partiel | Entite `client` et rattachement projet disponibles (admin/filament). A ajouter: espace client externe, validation livrables, facturation, support/tickets. |
| 16 | Fichiers & medias | Partiel | Gestion fichiers projet, metadata mime/size/version. A ajouter: compression auto, CDN, streaming media natif. |
| 17 | Recherche intelligente | Partiel | Filtres locaux + recherche par tag sur les taches. A ajouter: recherche globale full-text multi-modules + suggestions intelligentes. |

## 2) Fonctionnalites cle deja en place

### Espace client (`/client`)
- Dashboard analytics avec KPI
- Gestion projets, taches et sous-taches
- Workspace projet a onglets:
  - Vue globale
  - Timeline
  - Board Scrum/Kanban
  - Calendar
  - Team
  - Commentaires
  - Chat interne
  - Fichiers versionnes
  - Sous-taches
  - Rapport
- Drag & drop Kanban temps reel
- Gestion membres projet (roles + actif/inactif)
- Calendrier taches
- Timesheet (suivi du temps)
- Mentions `@user`
- Notifications multi-canaux
- Rapport projet (stats + export JSON/PDF)

### Espace admin Filament (`/abba` ou `/admin`)
- Dashboard admin
- CRUD utilisateurs / projets / taches
- Kanban admin drag & drop
- Vue calendrier admin
- Rapport projet admin (JSON/PDF)
- Journal d activite + commentaires

### Notifications declenchees
- assignation de tache
- changement de statut
- commentaire ajoute
- modification de tache
- modification de projet
- mention utilisateur
- nouveau message interne
- partage de fichier

### API REST & Flutter
- Auth token: `POST /api/v1/auth/register`, `POST /api/v1/auth/login`, `POST /api/v1/auth/logout`, `GET /api/v1/auth/me`
- Projets: `GET /api/v1/projects`, `GET /api/v1/projects/{id}`
- Taches: `GET /api/v1/tasks`, `PATCH /api/v1/tasks/{id}/status`
- Notifications: `GET /api/v1/notifications`, `PATCH /api/v1/notifications/{notificationId}/read`
- Device token push: `POST /api/v1/mobile/device-token`, `POST /api/v1/mobile/device-token/revoke`
- Timesheets: `GET /api/v1/timesheets`, `POST /api/v1/timesheets`, `PATCH /api/v1/timesheets/{id}`, `DELETE /api/v1/timesheets/{id}`

## 3) Roadmap recommandee (priorites)

### Phase 1 - Identite & securite
- Ajouter role `manager`
- Ajouter role `client`
- OAuth Google
- 2FA (TOTP + recovery codes)
- Preferences de notifications par utilisateur

### Phase 2 - Delivery agile
- Sprint entity (dates, goal, status)
- Story points + backlog priorise
- Burndown chart et velocity sprint
- WIP limits parametres par colonne

### Phase 3 - Planification avancee
- Gantt interactif
- Dependances taches
- Milestones projet
- Alertes retard automatiques

### Phase 4 - Client & data
- Espace client dedie (consultation + validation livrables)
- Facturation / suivi contractuel
- Export CSV/Excel
- Recherche globale full-text

## 4) Stack
- PHP 8.2+
- Laravel 12
- Filament 3
- MySQL/MariaDB (ou SQLite)
- Tailwind + Alpine + Vite
- Laravel Echo + Pusher protocol (Pusher ou Soketi)
- DomPDF (export PDF)

## Guide de deploiement VPS + SQL

Voir `docs/DEPLOIEMENT_VPS_SQL.md` pour:
- deploiement VPS production (Nginx + PHP-FPM + Supervisor + SSL)
- passage de SQLite vers MySQL/MariaDB
- checklist post-deploiement

## 5) Installation rapide

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
```

Dev mode:

```bash
composer run dev
```

## 6) Configuration env minimale

```env
APP_NAME="FAL PMS"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fal_pms
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@fal-pms.test"
MAIL_FROM_NAME="${APP_NAME}"

PROJECT_FILES_DISK=local
MOBILE_PUSH_ENABLED=false
FCM_ENDPOINT=https://fcm.googleapis.com/fcm/send
FCM_SERVER_KEY=
```

## 7) Configuration WebSocket (Pusher ou Soketi)

Mettre dans `.env`:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

Puis:

```bash
php artisan optimize:clear
npm run dev
```

Notes:
- Pusher Cloud: utilisez vos cles Pusher
- Soketi local: conserver `PUSHER_SCHEME=http`, `PUSHER_HOST=127.0.0.1`, `PUSHER_PORT=6001`

## 8) Identifiants de connexion (seed)

Apres `php artisan migrate:fresh --seed`:

| Role | Email | Password | Acces |
|---|---|---|---|
| Admin | `admin@fal-pms.test` | `password` | Client + Filament |
| Manager | `manager@fal-pms.test` | `password` | Client |
| Chef de projet | `pm@fal-pms.test` | `password` | Client |
| Membre equipe | `member@fal-pms.test` | `password` | Client |
| Client | `client@fal-pms.test` | `password` | Client (lecture) |

## 9) URLs utiles

- Landing: `/`
- Dashboard redirect: `/dashboard`
- Client:
  - `/client/dashboard`
  - `/client/projects`
  - `/client/tasks`
  - `/client/tasks/calendar`
- Admin:
  - `/admin` (redirect vers Filament)
  - `/abba`

## 10) Export rapport

- JSON: `/client/projects/{project}/report/download`
- PDF: `/client/projects/{project}/report/download/pdf`

## 11) Tests et commandes

```bash
php artisan test
php artisan migrate:fresh --seed
npm run build
```

## 12) Architecture (resume)

- `app/Http/Controllers/Client`: logique client
- `app/Filament`: ressources/pages admin
- `app/Events`: evenements websocket
- `app/Support/Realtime`: broadcaster helpers
- `app/Services/ProjectReportService.php`: generation rapport
- `app/Observers` + `app/Notifications`: activity + notifications
- `resources/views/client`: UI client
- `resources/views/filament`: UI admin custom
