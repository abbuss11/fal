# MERISE Essentiel (FAL-PMS)

Ce dossier contient:
- `fal_pms_essential.sql` : base SQL minimale (entités essentielles).
- `generate_merise_essential.php` : générateur auto de diagrammes MERISE.

## Entités essentielles retenues
- `users`
- `clients`
- `projects`
- `project_user`
- `tasks`
- `task_subtasks`
- `task_comments`
- `project_files`
- `timesheets`

## Générer les diagrammes auto

```bash
php docs/merise_essential/generate_merise_essential.php
```

Fichiers générés dans `docs/merise_essential/out` :
- `mcd_essentiel.mmd`
- `mld_essentiel.mmd`
- `mpd_essentiel.sql`

## Générer aussi les SVG (si Mermaid CLI installé)

```bash
php docs/merise_essential/generate_merise_essential.php --svg
```

Sortie attendue:
- `mcd_essentiel.svg`
- `mld_essentiel.svg`

## Import SQL

```sql
SOURCE docs/merise_essential/fal_pms_essential.sql;
```

