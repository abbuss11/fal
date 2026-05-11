<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Projet - {{ $report['project']['name'] }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.35;
        }
        h1, h2, h3 {
            margin: 0;
        }
        .title {
            border-bottom: 2px solid #0f6d93;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .muted {
            color: #64748b;
            font-size: 11px;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .grid th, .grid td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        .grid th {
            background: #f8fafc;
            font-weight: 700;
            color: #334155;
        }
        .section {
            margin-top: 14px;
        }
        .kpi {
            display: inline-block;
            width: 24%;
            margin-right: 1%;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            box-sizing: border-box;
        }
        .kpi strong {
            display: block;
            font-size: 18px;
            margin-top: 4px;
        }
        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>
    @php
        $stats = $report['stats'];
    @endphp

    <div class="title">
        <h1>Rapport Complet Projet</h1>
        <p class="muted">{{ $report['project']['name'] }} | Genere le {{ $report['generated_at'] }}</p>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th>Projet</th>
                <th>Statut</th>
                <th>Priorite</th>
                <th>Chef</th>
                <th>Debut</th>
                <th>Echeance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $report['project']['name'] }}</td>
                <td>{{ \App\Models\Project::statusOptions()[$report['project']['status']] ?? $report['project']['status'] }}</td>
                <td>{{ \App\Models\Project::priorityOptions()[$report['project']['priority']] ?? $report['project']['priority'] }}</td>
                <td>{{ $report['project']['owner'] ?? 'N/A' }}</td>
                <td>{{ $report['project']['start_date'] ?? 'N/A' }}</td>
                <td>{{ $report['project']['due_date'] ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section">
        <div class="kpi">
            Progression
            <strong>{{ $stats['progress_rate'] }}%</strong>
        </div>
        <div class="kpi">
            Taches totales
            <strong>{{ $stats['tasks_total'] }}</strong>
        </div>
        <div class="kpi">
            Membres actifs
            <strong>{{ $stats['members_active'] }}</strong>
        </div>
        <div class="kpi">
            Taches en retard
            <strong>{{ $stats['tasks_overdue'] }}</strong>
        </div>
    </div>

    <div class="section">
        <h3>Analyse par statut</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th>Statut</th>
                    <th>Volume</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statusLabels as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $report['status_breakdown'][$key] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Analyse par priorite</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th>Priorite</th>
                    <th>Volume</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($priorityLabels as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td>{{ $report['priority_breakdown'][$key] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Membres et charge</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th>Membre</th>
                    <th>Role</th>
                    <th>Actif</th>
                    <th>Assignees</th>
                    <th>Ouverte</th>
                    <th>Acheve</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['member_workload'] as $member)
                    <tr>
                        <td>{{ $member['name'] }}</td>
                        <td>{{ $roleLabels[$member['project_role']] ?? $member['project_role'] }}</td>
                        <td>{{ $member['active'] ? 'Oui' : 'Non' }}</td>
                        <td>{{ $member['tasks_assigned'] }}</td>
                        <td>{{ $member['tasks_open'] }}</td>
                        <td>{{ $member['tasks_done'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Timeline recente</h3>
        <table class="grid">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Acteur</th>
                    <th>Tache</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['timeline'] as $event)
                    <tr>
                        <td>{{ $event['date'] ?? 'N/A' }}</td>
                        <td>{{ $event['action'] }}</td>
                        <td>{{ $event['actor'] }}</td>
                        <td>{{ $event['task'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted small">Document exporte depuis Futuristic Africa Lab Project Manager.</p>
    </div>
</body>
</html>
