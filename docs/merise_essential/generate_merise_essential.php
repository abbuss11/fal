<?php

declare(strict_types=1);

/**
 * Générateur MERISE (noyau essentiel)
 *
 * Sorties:
 * - out/mcd_essentiel.mmd
 * - out/mld_essentiel.mmd
 * - out/mpd_essentiel.sql
 *
 * Optionnel:
 * --svg  => tente de générer out/*.svg via mmdc si disponible.
 */

$root = dirname(__DIR__, 2);
$baseDir = __DIR__;
$outDir = $baseDir . DIRECTORY_SEPARATOR . 'out';

if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    throw new RuntimeException("Impossible de créer le dossier de sortie: $outDir");
}

$schema = [
    'users' => [
        'concept' => 'UTILISATEUR',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'name', 'type' => 'VARCHAR(255)'],
            ['name' => 'email', 'type' => 'VARCHAR(255)'],
            ['name' => 'role', 'type' => 'VARCHAR(32)'],
            ['name' => 'is_active', 'type' => 'TINYINT(1)'],
        ],
        'description' => ['id_utilisateur', 'nom', 'email', 'role', 'etat_compte'],
        'fks' => [],
    ],
    'clients' => [
        'concept' => 'CLIENT',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'name', 'type' => 'VARCHAR(255)'],
            ['name' => 'email', 'type' => 'VARCHAR(255)'],
            ['name' => 'company', 'type' => 'VARCHAR(255)'],
        ],
        'description' => ['id_client', 'nom_client', 'email', 'organisation'],
        'fks' => [],
    ],
    'projects' => [
        'concept' => 'PROJET',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'owner_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'client_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'name', 'type' => 'VARCHAR(255)'],
            ['name' => 'status', 'type' => 'VARCHAR(32)'],
            ['name' => 'priority', 'type' => 'VARCHAR(20)'],
            ['name' => 'start_date', 'type' => 'DATE'],
            ['name' => 'due_date', 'type' => 'DATE'],
        ],
        'description' => ['id_projet', 'titre', 'statut', 'priorite', 'date_debut', 'date_fin'],
        'fks' => [
            ['column' => 'owner_id', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'client_id', 'ref_table' => 'clients', 'ref_column' => 'id', 'nullable' => true],
        ],
    ],
    'project_user' => [
        'concept' => 'AFFECTATION_PROJET',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'project_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'user_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'project_role', 'type' => 'VARCHAR(32)'],
            ['name' => 'is_active', 'type' => 'TINYINT(1)'],
        ],
        'description' => ['id_affectation', 'role_projet', 'actif'],
        'fks' => [
            ['column' => 'project_id', 'ref_table' => 'projects', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => false],
        ],
    ],
    'tasks' => [
        'concept' => 'TACHE',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'project_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'assigned_to', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'title', 'type' => 'VARCHAR(255)'],
            ['name' => 'status', 'type' => 'VARCHAR(32)'],
            ['name' => 'priority', 'type' => 'VARCHAR(20)'],
            ['name' => 'due_date', 'type' => 'DATETIME'],
        ],
        'description' => ['id_tache', 'titre', 'statut', 'priorite', 'date_echeance'],
        'fks' => [
            ['column' => 'project_id', 'ref_table' => 'projects', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'assigned_to', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => true],
        ],
    ],
    'task_subtasks' => [
        'concept' => 'SOUS_TACHE',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'task_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'title', 'type' => 'VARCHAR(255)'],
            ['name' => 'is_completed', 'type' => 'TINYINT(1)'],
            ['name' => 'completed_by', 'type' => 'BIGINT', 'fk' => true],
        ],
        'description' => ['id_sous_tache', 'libelle', 'est_terminee'],
        'fks' => [
            ['column' => 'task_id', 'ref_table' => 'tasks', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'completed_by', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => true],
        ],
    ],
    'task_comments' => [
        'concept' => 'COMMENTAIRE',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'task_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'user_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'content', 'type' => 'TEXT'],
        ],
        'description' => ['id_commentaire', 'contenu', 'date_commentaire'],
        'fks' => [
            ['column' => 'task_id', 'ref_table' => 'tasks', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => false],
        ],
    ],
    'project_files' => [
        'concept' => 'DOCUMENT',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'project_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'task_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'uploaded_by', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'logical_name', 'type' => 'VARCHAR(255)'],
            ['name' => 'file_path', 'type' => 'VARCHAR(255)'],
        ],
        'description' => ['id_document', 'nom_fichier', 'chemin_fichier'],
        'fks' => [
            ['column' => 'project_id', 'ref_table' => 'projects', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'task_id', 'ref_table' => 'tasks', 'ref_column' => 'id', 'nullable' => true],
            ['column' => 'uploaded_by', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => true],
        ],
    ],
    'timesheets' => [
        'concept' => 'FEUILLE_TEMPS',
        'columns' => [
            ['name' => 'id', 'type' => 'BIGINT', 'pk' => true],
            ['name' => 'user_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'project_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'task_id', 'type' => 'BIGINT', 'fk' => true],
            ['name' => 'hours', 'type' => 'DECIMAL(6,2)'],
            ['name' => 'work_date', 'type' => 'DATE'],
        ],
        'description' => ['id_timesheet', 'duree_heures', 'date_saisie'],
        'fks' => [
            ['column' => 'user_id', 'ref_table' => 'users', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'project_id', 'ref_table' => 'projects', 'ref_column' => 'id', 'nullable' => false],
            ['column' => 'task_id', 'ref_table' => 'tasks', 'ref_column' => 'id', 'nullable' => true],
        ],
    ],
];

/**
 * @param array<string, mixed> $schema
 */
function generateMcdMermaid(array $schema): string
{
    $lines = [];
    $lines[] = "erDiagram";

    foreach ($schema as $table => $meta) {
        $entity = (string) $meta['concept'];
        $lines[] = "    $entity {";
        foreach ($meta['description'] as $attr) {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $attr);
            $lines[] = "        string $safe";
        }
        $lines[] = "    }";
    }

    foreach ($schema as $table => $meta) {
        $entity = (string) $meta['concept'];
        foreach ($meta['fks'] as $fk) {
            $parent = (string) $schema[$fk['ref_table']]['concept'];
            $label = (string) $fk['column'];
            $lines[] = "    $parent ||--o{ $entity : \"$label\"";
        }
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

/**
 * @param array<string, mixed> $schema
 */
function generateMldMermaid(array $schema): string
{
    $lines = [];
    $lines[] = "erDiagram";

    foreach ($schema as $table => $meta) {
        $tableName = strtoupper($table);
        $lines[] = "    $tableName {";
        foreach ($meta['columns'] as $col) {
            $type = strtoupper((string) $col['type']);
            $name = (string) $col['name'];
            $suffix = [];
            if (!empty($col['pk'])) {
                $suffix[] = 'PK';
            }
            if (!empty($col['fk'])) {
                $suffix[] = 'FK';
            }
            $extra = $suffix ? (' ' . implode(',', $suffix)) : '';
            $lines[] = "        $type $name$extra";
        }
        $lines[] = "    }";
    }

    foreach ($schema as $table => $meta) {
        $child = strtoupper($table);
        foreach ($meta['fks'] as $fk) {
            $parent = strtoupper((string) $fk['ref_table']);
            $label = (string) $fk['column'];
            $lines[] = "    $parent ||--o{ $child : \"$label\"";
        }
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

/**
 * @param array<string, mixed> $schema
 */
function generateMpdSql(array $schema): string
{
    $dropOrder = array_reverse(array_keys($schema));
    $lines = [];
    $lines[] = "-- MPD Essentiel généré automatiquement";
    $lines[] = "SET NAMES utf8mb4;";
    $lines[] = "SET FOREIGN_KEY_CHECKS = 0;";
    foreach ($dropOrder as $table) {
        $lines[] = "DROP TABLE IF EXISTS $table;";
    }
    $lines[] = "SET FOREIGN_KEY_CHECKS = 1;";
    $lines[] = "";

    foreach ($schema as $table => $meta) {
        $lines[] = "CREATE TABLE $table (";
        $colLines = [];
        $pk = null;

        foreach ($meta['columns'] as $col) {
            $name = (string) $col['name'];
            $type = (string) $col['type'];
            $isPk = !empty($col['pk']);
            $isFk = !empty($col['fk']);
            $nullable = true;

            $sqlType = $type;
            if (preg_match('/^BIGINT$/i', $type) === 1) {
                $sqlType = 'BIGINT UNSIGNED';
            }

            if ($isPk) {
                $nullable = false;
                $definition = "  $name $sqlType NOT NULL AUTO_INCREMENT";
                $pk = $name;
            } else {
                foreach ($meta['fks'] as $fk) {
                    if ($fk['column'] === $name) {
                        $nullable = (bool) $fk['nullable'];
                        break;
                    }
                }
                $nullToken = $nullable ? "NULL" : "NOT NULL";
                $definition = "  $name $sqlType $nullToken";
            }

            if ($name === 'role') {
                $definition .= " DEFAULT 'member'";
            } elseif ($name === 'status') {
                $definition .= " DEFAULT 'todo'";
            } elseif ($name === 'priority') {
                $definition .= " DEFAULT 'medium'";
            } elseif ($name === 'is_active') {
                $definition .= " DEFAULT 1";
            } elseif ($name === 'is_completed') {
                $definition .= " DEFAULT 0";
            }

            $colLines[] = $definition;
            if ($isFk) {
                $colLines[] = "  KEY {$table}_{$name}_index ($name)";
            }
        }

        if ($pk !== null) {
            $colLines[] = "  PRIMARY KEY ($pk)";
        }

        foreach ($meta['fks'] as $fk) {
            $col = (string) $fk['column'];
            $refTable = (string) $fk['ref_table'];
            $refCol = (string) $fk['ref_column'];
            $onDelete = $fk['nullable'] ? 'SET NULL' : 'CASCADE';
            if ($table === 'projects' && $col === 'owner_id') {
                $onDelete = 'RESTRICT';
            }
            $colLines[] = "  CONSTRAINT {$table}_{$col}_fk FOREIGN KEY ($col) REFERENCES $refTable ($refCol) ON UPDATE CASCADE ON DELETE $onDelete";
        }

        $lines[] = implode("," . PHP_EOL, $colLines);
        $lines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $lines[] = "";
    }

    return implode(PHP_EOL, $lines);
}

function writeFile(string $path, string $content): void
{
    file_put_contents($path, $content);
    echo "OK: $path" . PHP_EOL;
}

function findMmdc(string $root): ?string
{
    $localWin = $root . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . 'mmdc.cmd';
    $localUnix = $root . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin' . DIRECTORY_SEPARATOR . 'mmdc';

    if (is_file($localWin)) {
        return $localWin;
    }
    if (is_file($localUnix)) {
        return $localUnix;
    }

    $cmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where mmdc' : 'command -v mmdc';
    $output = [];
    $code = 1;
    @exec($cmd, $output, $code);
    if ($code === 0 && !empty($output[0])) {
        return trim($output[0]);
    }

    return null;
}

function renderSvg(string $mmdc, string $in, string $out): bool
{
    $cmd = escapeshellarg($mmdc)
        . ' -i ' . escapeshellarg($in)
        . ' -o ' . escapeshellarg($out)
        . ' -b transparent';
    $output = [];
    $code = 1;
    @exec($cmd, $output, $code);

    return $code === 0;
}

$mcdPath = $outDir . DIRECTORY_SEPARATOR . 'mcd_essentiel.mmd';
$mldPath = $outDir . DIRECTORY_SEPARATOR . 'mld_essentiel.mmd';
$mpdPath = $outDir . DIRECTORY_SEPARATOR . 'mpd_essentiel.sql';

writeFile($mcdPath, generateMcdMermaid($schema));
writeFile($mldPath, generateMldMermaid($schema));
writeFile($mpdPath, generateMpdSql($schema));

$args = $argv ?? [];
$wantSvg = in_array('--svg', $args, true);

if ($wantSvg) {
    $mmdc = findMmdc($root);
    if ($mmdc === null) {
        echo "WARN: mmdc introuvable. Installe mermaid-cli puis relance avec --svg." . PHP_EOL;
        exit(0);
    }

    $okMcd = renderSvg($mmdc, $mcdPath, $outDir . DIRECTORY_SEPARATOR . 'mcd_essentiel.svg');
    $okMld = renderSvg($mmdc, $mldPath, $outDir . DIRECTORY_SEPARATOR . 'mld_essentiel.svg');

    echo $okMcd ? "OK: SVG MCD généré" . PHP_EOL : "WARN: Échec SVG MCD" . PHP_EOL;
    echo $okMld ? "OK: SVG MLD généré" . PHP_EOL : "WARN: Échec SVG MLD" . PHP_EOL;
}

echo PHP_EOL . "Terminé." . PHP_EOL;
