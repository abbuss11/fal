# Guide de deploiement VPS + passage SQLite vers MySQL/MariaDB

Projet cible: `FAL PMS` (Laravel 12, PHP 8.2+).

## 1) Objectif

Mettre l'application en ligne sur un VPS Linux avec:
- Nginx
- PHP-FPM
- MySQL ou MariaDB
- Queue worker (Supervisor)
- SSL (Let's Encrypt)

Et remplacer SQLite par une base SQL (MySQL/MariaDB).

## 2) Prerequis

- VPS Ubuntu 22.04/24.04
- Acces SSH avec sudo
- Nom de domaine pointe vers l'IP du VPS
- Depot Git (ou archive du projet)

## 3) Installation des paquets serveur

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server unzip git curl supervisor
sudo apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql \
php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-intl
```

Installer Composer:

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

Installer Node.js 20 (pour build front):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 4) Preparation dossier application

```bash
sudo mkdir -p /var/www/fal-pms
sudo chown -R $USER:www-data /var/www/fal-pms
cd /var/www/fal-pms
```

Deployer le code (exemple Git):

```bash
git clone <URL_DU_DEPOT> .
```

## 5) Installation applicative Laravel

```bash
cd /var/www/fal-pms
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cp .env.example .env
php artisan key:generate --force
```

## 6) Creation base SQL (MySQL/MariaDB)

Ouvrir MySQL:

```bash
sudo mysql
```

Executer:

```sql
CREATE DATABASE fal_pms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fal_pms_user'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON fal_pms.* TO 'fal_pms_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

## 7) Configuration `.env` production

Adapter `/var/www/fal-pms/.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pms.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fal_pms
DB_USERNAME=fal_pms_user
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

## 8) Passage SQLite -> SQL (MySQL/MariaDB)

Tu etais en SQLite (`DB_CONNECTION=sqlite`). Pour passer en SQL:

1. Sauvegarde l'existant SQLite:

```bash
cp database/database.sqlite database/database.sqlite.bak
```

2. Mets bien `DB_CONNECTION=mysql` dans `.env` (section precedente).

3. Choisis une strategie:

### Strategie A (recommandee): base propre via migrations

Si tu peux repartir d'une base vide:

```bash
php artisan migrate --force
php artisan db:seed --force
```

### Strategie B: import schema SQL deja genere

Le projet contient `DATABASE_FAL_PMS_COMPLETE.sql` (schema MySQL/MariaDB).

```bash
cd /var/www/fal-pms
mysql -u fal_pms_user -p fal_pms < DATABASE_FAL_PMS_COMPLETE.sql
```

Ensuite applique les migrations eventuellement ajoutees apres ce dump:

```bash
php artisan migrate --force
```

Important:
- Le fichier SQL contient `CREATE DATABASE ...` et `USE fal_pms`.
- Si ton nom de base est different, edite ce fichier avant import.
- Si ton utilisateur MySQL n'a pas le droit `CREATE DATABASE`, retire aussi cette instruction du fichier SQL avant import.

### Strategie C: conserver les donnees SQLite

Si tu dois garder les donnees actuelles, fais une migration de data table par table (ETL).  
Le plus propre est de:
- creer d'abord le schema MySQL avec `php artisan migrate --force`
- puis copier les enregistrements depuis SQLite vers MySQL avec un script de transfert (commande Artisan dediee)

Si tu veux, je peux te generer cette commande de migration de donnees dans le projet.

## 9) Permissions et cache Laravel

```bash
cd /var/www/fal-pms
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10) Configuration Nginx

Creer `/etc/nginx/sites-available/fal-pms`:

```nginx
server {
    listen 80;
    server_name pms.example.com;
    root /var/www/fal-pms/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activer le site:

```bash
sudo ln -s /etc/nginx/sites-available/fal-pms /etc/nginx/sites-enabled/fal-pms
sudo nginx -t
sudo systemctl reload nginx
```

## 11) Queue worker (Supervisor)

Creer `/etc/supervisor/conf.d/fal-pms-worker.conf`:

```ini
[program:fal-pms-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/fal-pms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/fal-pms/storage/logs/worker.log
stopwaitsecs=3600
```

Appliquer:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start fal-pms-worker:*
```

## 12) Cron Laravel Scheduler

```bash
crontab -e
```

Ajouter:

```cron
* * * * * cd /var/www/fal-pms && php artisan schedule:run >> /dev/null 2>&1
```

## 13) SSL Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d pms.example.com
```

## 14) Verification post-deploiement

```bash
cd /var/www/fal-pms
php artisan about
php artisan migrate:status
php artisan queue:monitor default
```

Verifier aussi:
- ouverture de `https://pms.example.com`
- login fonctionnel
- creation d'une tache/projet
- notification + queue
- logs: `storage/logs/laravel.log`

## 15) Procedure de mise a jour (deploy suivant)

```bash
cd /var/www/fal-pms
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

## 16) Notes projet FAL PMS

- Le projet inclut deja un schema SQL complet:
  - `DATABASE_FAL_PMS_COMPLETE.sql`
  - `DATABASE_FAL_PMS_COMPLETE.md`
- Les tables Laravel `sessions`, `cache`, `jobs` existent: garde `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` si tu restes sur la config actuelle.
