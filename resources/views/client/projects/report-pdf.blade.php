<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Projet - {{ $report['project']['name'] }}</title>
    <style>
        @page {
            margin: 18mm 14mm 18mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.42;
            margin: 0;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 9px;
        }

        .header-box {
            border: 1px solid #c9d7e8;
            border-radius: 6px;
            background: #f8fbff;
            padding: 10px;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 118px;
            text-align: center;
            padding-right: 8px;
        }

        .logo-mark svg {
            width: 96px;
            height: auto;
        }

        .brand-kicker {
            font-size: 9px;
            color: #0f5f91;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-weight: 700;
        }

        .brand-title {
            font-size: 17px;
            line-height: 1.1;
            color: #102a43;
            margin-top: 3px;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }

        .pill {
            display: inline-block;
            border: 1px solid #b3c8df;
            border-radius: 20px;
            font-size: 9px;
            color: #0f4d79;
            padding: 2px 8px;
            margin-top: 6px;
            background: #edf5ff;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .meta-grid td {
            border: 1px solid #d6e1ee;
            padding: 5px 7px;
            vertical-align: top;
            width: 50%;
        }

        .meta-label {
            display: block;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 10px;
            color: #111827;
            font-weight: 600;
        }

        .signal-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 6px -8px 0;
        }

        .signal-grid td {
            border: 1px solid #d6e1ee;
            border-radius: 5px;
            background: #ffffff;
            padding: 6px 8px;
            width: 33.33%;
        }

        .signal-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 3px;
        }

        .signal-value {
            font-size: 16px;
            color: #0f3254;
            font-weight: 700;
            line-height: 1.05;
        }

        .signal-help {
            margin-top: 2px;
            font-size: 9px;
            color: #6b7280;
        }

        .risk-ok {
            color: #0f766e;
        }

        .risk-watch {
            color: #b45309;
        }

        .risk-crit {
            color: #b91c1c;
        }

        .section {
            margin-top: 10px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #0f3254;
            background: #eef5ff;
            border: 1px solid #cfe0f3;
            border-radius: 4px;
            padding: 5px 7px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .summary-box {
            border: 1px solid #d8e2ef;
            border-radius: 5px;
            background: #ffffff;
            padding: 8px;
        }

        .summary-text {
            font-size: 10px;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .bullet-list {
            margin: 0;
            padding-left: 16px;
        }

        .bullet-list li {
            margin-bottom: 3px;
            color: #374151;
            font-size: 10px;
        }

        .kpi-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 6px;
            margin: -6px;
        }

        .kpi-grid td {
            width: 25%;
            border: 1px solid #d7e3ef;
            border-radius: 5px;
            background: #fff;
            padding: 7px;
            vertical-align: top;
        }

        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .kpi-value {
            margin-top: 2px;
            font-size: 18px;
            font-weight: 700;
            color: #0f3254;
            line-height: 1.1;
        }

        .kpi-help {
            margin-top: 2px;
            color: #6b7280;
            font-size: 9px;
        }

        .two-col {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 0 -7px;
        }

        .two-col td {
            width: 50%;
            vertical-align: top;
        }

        .panel {
            border: 1px solid #d8e2ef;
            border-radius: 5px;
            background: #ffffff;
            padding: 8px;
        }

        .chart-row {
            margin-bottom: 7px;
        }

        .chart-header {
            margin-bottom: 2px;
            font-size: 10px;
            color: #334155;
        }

        .chart-header strong {
            font-size: 10px;
            color: #0f172a;
        }

        .bar-track {
            border: 1px solid #d1dbe8;
            height: 10px;
            border-radius: 20px;
            background: #f3f7fb;
            overflow: hidden;
        }

        .bar-fill {
            height: 10px;
            border-radius: 20px;
        }

        .fill-todo {
            background: #94a3b8;
        }

        .fill-doing {
            background: #0ea5e9;
        }

        .fill-done {
            background: #10b981;
        }

        .stacked-bar {
            margin-top: 5px;
            border: 1px solid #d1dbe8;
            height: 13px;
            border-radius: 20px;
            overflow: hidden;
            font-size: 0;
            background: #f3f7fb;
        }

        .segment {
            display: inline-block;
            height: 13px;
        }

        .priority-low {
            background: #10b981;
        }

        .priority-medium {
            background: #0ea5e9;
        }

        .priority-high {
            background: #f59e0b;
        }

        .priority-urgent {
            background: #ef4444;
        }

        .legend-grid {
            margin-top: 6px;
            width: 100%;
            border-collapse: collapse;
        }

        .legend-grid td {
            width: 50%;
            padding: 2px 0;
            font-size: 9px;
            color: #475569;
        }

        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .workflow-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .workflow-grid td {
            vertical-align: middle;
            text-align: center;
            padding: 3px 2px;
        }

        .workflow-step {
            border: 1px solid #d6e1ee;
            border-radius: 4px;
            padding: 6px 4px;
            background: #f8fbff;
        }

        .workflow-count {
            font-size: 16px;
            font-weight: 700;
            color: #0f3254;
            line-height: 1.05;
        }

        .workflow-name {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-top: 2px;
        }

        .workflow-percent {
            margin-top: 1px;
            font-size: 9px;
            color: #334155;
        }

        .workflow-arrow {
            font-size: 16px;
            color: #94a3b8;
            width: 18px;
        }

        .velocity-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .velocity-grid td {
            text-align: center;
            vertical-align: bottom;
            width: 16.66%;
            padding: 0 3px;
        }

        .velocity-canvas {
            height: 88px;
            border-bottom: 1px solid #d6e1ee;
            vertical-align: bottom;
        }

        .velocity-bar {
            display: inline-block;
            width: 22px;
            min-height: 2px;
            background: #0ea5e9;
            border-radius: 3px 3px 0 0;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            line-height: 12px;
            vertical-align: bottom;
        }

        .velocity-label {
            font-size: 8px;
            color: #64748b;
            margin-top: 3px;
        }

        .table-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .table-grid th,
        .table-grid td {
            border: 1px solid #d7e2ef;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
            font-size: 9.5px;
        }

        .table-grid th {
            background: #f3f7fb;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 8px;
        }

        .eff-track {
            border: 1px solid #d6e1ee;
            height: 8px;
            border-radius: 20px;
            background: #f4f7fa;
            overflow: hidden;
        }

        .eff-fill {
            height: 8px;
            background: #22c55e;
            border-radius: 20px;
        }

        .timeline-card {
            border: 1px solid #d8e2ef;
            border-radius: 5px;
            padding: 7px;
            background: #fff;
        }

        .timeline-item {
            border-left: 2px solid #93c5fd;
            padding-left: 8px;
            margin-bottom: 7px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-title {
            font-size: 10px;
            color: #0f172a;
            font-weight: 600;
        }

        .timeline-meta {
            font-size: 9px;
            color: #6b7280;
            margin-top: 1px;
        }

        .note-box {
            border: 1px solid #d8e2ef;
            border-radius: 5px;
            background: #fff;
            padding: 8px;
            margin-top: 8px;
        }

        .note-box p {
            font-size: 9px;
            color: #475569;
        }

        .recommendation-list {
            margin: 0;
            padding-left: 16px;
        }

        .recommendation-list li {
            margin-bottom: 3px;
            font-size: 10px;
            color: #374151;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -8mm;
            font-size: 9px;
            color: #64748b;
            text-align: right;
            border-top: 1px solid #d6e1ee;
            padding-top: 3px;
        }

        .footer .page::after {
            content: counter(page);
        }
    </style>
</head>
<body>
    @php
        $projectData = $report['project'] ?? [];
        $stats = $report['stats'] ?? [];
        $statusBreakdown = $report['status_breakdown'] ?? [];
        $priorityBreakdown = $report['priority_breakdown'] ?? [];
        $memberWorkload = $report['member_workload'] ?? [];
        $teamPerformance = $report['team_performance'] ?? [];
        $velocitySeries = array_values($report['velocity_last_weeks'] ?? []);
        $timeline = array_values($report['timeline'] ?? []);
        $timelinePreview = array_slice($timeline, 0, 10);
        $tagsUsed = array_values($report['tags_used'] ?? []);
        $tagsPreview = implode(', ', array_slice($tagsUsed, 0, 8));

        $generatedAt = $report['generated_at'] ?? now()->toDateTimeString();
        $tasksTotal = (int) ($stats['tasks_total'] ?? 0);
        $tasksDone = (int) ($stats['tasks_done'] ?? 0);
        $tasksOverdue = (int) ($stats['tasks_overdue'] ?? 0);
        $openTasks = max($tasksTotal - $tasksDone, 0);
        $membersTotal = (int) ($stats['members_total'] ?? count($memberWorkload));
        $membersActive = (int) ($stats['members_active'] ?? 0);
        $progressRate = (float) ($stats['progress_rate'] ?? 0.0);
        $completionRate = $tasksTotal > 0 ? round(($tasksDone / $tasksTotal) * 100, 1) : 0.0;
        $loggedHours = (float) ($stats['logged_hours'] ?? 0.0);
        $avgCompletionHours = (float) ($stats['average_completion_hours'] ?? 0.0);
        $commentsTotal = (int) ($stats['comments_total'] ?? 0);
        $dependenciesTotal = (int) ($stats['dependencies_total'] ?? 0);
        $tagsTotal = (int) ($stats['tags_total'] ?? count($tagsUsed));

        $activeRate = $membersTotal > 0 ? round(($membersActive / $membersTotal) * 100, 1) : 0.0;
        $scheduleRate = $tasksTotal > 0 ? round((($tasksTotal - $tasksOverdue) / $tasksTotal) * 100, 1) : 100.0;
        $healthScore = (int) round(min(100, max(0, ($completionRate * 0.55) + ($activeRate * 0.20) + ($scheduleRate * 0.25))));

        if ($healthScore >= 80) {
            $healthLabel = 'Sain';
        } elseif ($healthScore >= 60) {
            $healthLabel = 'Sous controle';
        } else {
            $healthLabel = 'Sous tension';
        }

        if ($tasksOverdue >= max(1, (int) ceil($tasksTotal * 0.25))) {
            $riskLabel = 'Critique';
            $riskClass = 'risk-crit';
        } elseif ($tasksOverdue > 0) {
            $riskLabel = 'A surveiller';
            $riskClass = 'risk-watch';
        } else {
            $riskLabel = 'Faible';
            $riskClass = 'risk-ok';
        }

        $statusColorMap = [
            'todo' => 'fill-todo',
            'doing' => 'fill-doing',
            'done' => 'fill-done',
        ];

        $priorityColorMap = [
            'low' => 'priority-low',
            'medium' => 'priority-medium',
            'high' => 'priority-high',
            'urgent' => 'priority-urgent',
        ];

        $statusSeries = [];
        foreach ($statusLabels as $key => $label) {
            $count = (int) ($statusBreakdown[$key] ?? 0);
            $percent = $tasksTotal > 0 ? round(($count / $tasksTotal) * 100, 1) : 0.0;
            $statusSeries[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'percent' => $percent,
                'color' => $statusColorMap[$key] ?? 'fill-todo',
            ];
        }

        $prioritySeries = [];
        foreach ($priorityLabels as $key => $label) {
            $count = (int) ($priorityBreakdown[$key] ?? 0);
            $percent = $tasksTotal > 0 ? round(($count / $tasksTotal) * 100, 1) : 0.0;
            $prioritySeries[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'percent' => $percent,
                'color' => $priorityColorMap[$key] ?? 'priority-medium',
            ];
        }

        $maxVelocity = 1;
        foreach ($velocitySeries as $velocityPoint) {
            $maxVelocity = max($maxVelocity, (int) ($velocityPoint['done'] ?? 0));
        }

        $maxEfficiency = 1.0;
        foreach ($teamPerformance as $performanceRow) {
            $maxEfficiency = max($maxEfficiency, (float) ($performanceRow['efficiency'] ?? 0.0));
        }

        $teamPerformancePreview = array_slice($teamPerformance, 0, 8);

        $recommendations = [];
        if ($tasksOverdue > 0) {
            $recommendations[] = 'Mettre en place un point d arbitrage hebdomadaire dedie aux taches en retard.';
        }
        if ($openTasks > $tasksDone && $tasksTotal > 0) {
            $recommendations[] = 'Reequilibrer la charge des membres ayant plus de 40% de backlog ouvert.';
        }
        if ($membersTotal > 0 && $membersActive < $membersTotal) {
            $recommendations[] = 'Relancer les membres inactifs et valider les disponibilites reellement engagees.';
        }
        if ($avgCompletionHours > 72) {
            $recommendations[] = 'Redecouper les livrables volumineux pour reduire le cycle moyen de completion.';
        }
        if (count($recommendations) === 0) {
            $recommendations[] = 'Maintenir le rythme de livraison actuel et consolider les standards de qualite.';
            $recommendations[] = 'Capitaliser les bonnes pratiques de planification dans un template reutilisable.';
        }

        $executiveSummary = 'Le projet presente une progression de '.$progressRate.'% avec '.$tasksDone.' taches finalisees sur '.$tasksTotal.'. Le score de sante atteint '.$healthScore.'/100 ('.$healthLabel.') et le risque de derapage est classe '.$riskLabel.'.';
        $projectPeriod = ($projectData['start_date'] ?? 'N/A').' au '.($projectData['due_date'] ?? 'N/A');
        $projectStatusLabel = \App\Models\Project::statusOptions()[$projectData['status'] ?? ''] ?? ($projectData['status'] ?? 'N/A');
        $projectPriorityLabel = \App\Models\Project::priorityOptions()[$projectData['priority'] ?? ''] ?? ($projectData['priority'] ?? 'N/A');
    @endphp

    <div class="header-box">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <div class="logo-mark">
                        @include('components.application-logo')
                    </div>
                </td>
                <td>
                    <p class="brand-kicker">Cabinet FAL</p>
                    <h1 class="brand-title">Rapport de Pilotage Projet</h1>
                    <p class="brand-subtitle">{{ $projectData['name'] ?? 'Projet non defini' }}</p>
                    <span class="pill">Document professionnel - usage interne</span>
                </td>
            </tr>
        </table>

        <table class="meta-grid">
            <tr>
                <td>
                    <span class="meta-label">Projet</span>
                    <span class="meta-value">{{ $projectData['name'] ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="meta-label">Client</span>
                    <span class="meta-value">{{ $projectData['client'] ?? 'N/A' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="meta-label">Chef de projet</span>
                    <span class="meta-value">{{ $projectData['owner'] ?? 'N/A' }}</span>
                </td>
                <td>
                    <span class="meta-label">Periode</span>
                    <span class="meta-value">{{ $projectPeriod }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="meta-label">Statut / Priorite</span>
                    <span class="meta-value">{{ $projectStatusLabel }} / {{ $projectPriorityLabel }}</span>
                </td>
                <td>
                    <span class="meta-label">Date de generation</span>
                    <span class="meta-value">{{ $generatedAt }}</span>
                </td>
            </tr>
        </table>

        <table class="signal-grid">
            <tr>
                <td>
                    <div class="signal-label">Score de sante</div>
                    <div class="signal-value">{{ $healthScore }}/100</div>
                    <div class="signal-help">{{ $healthLabel }}</div>
                </td>
                <td>
                    <div class="signal-label">Niveau de risque</div>
                    <div class="signal-value {{ $riskClass }}">{{ $riskLabel }}</div>
                    <div class="signal-help">{{ $tasksOverdue }} retard(s) actif(s)</div>
                </td>
                <td>
                    <div class="signal-label">Cadence livraison</div>
                    <div class="signal-value">{{ $completionRate }}%</div>
                    <div class="signal-help">{{ $tasksDone }} taches cloturees</div>
                </td>
            </tr>
        </table>
    </div>

    <section class="section">
        <div class="section-title">Synthese executive</div>
        <div class="summary-box">
            <p class="summary-text">{{ $executiveSummary }}</p>
            <ul class="bullet-list">
                <li>Charge active equipe: {{ $membersActive }} membre(s) actif(s) sur {{ $membersTotal }} ({{ $activeRate }}%).</li>
                <li>Cycle moyen de completion: {{ $avgCompletionHours }}h par tache finalisee.</li>
                <li>Capitalisation: {{ $commentsTotal }} commentaires, {{ $dependenciesTotal }} dependances, {{ $tagsTotal }} tags utilises.</li>
                <li>Temps declare total: {{ $loggedHours }} heure(s) sur le projet.</li>
            </ul>
        </div>
    </section>

    <section class="section">
        <div class="section-title">Indicateurs cles</div>
        <table class="kpi-grid">
            <tr>
                <td>
                    <div class="kpi-label">Progression globale</div>
                    <div class="kpi-value">{{ $progressRate }}%</div>
                    <div class="kpi-help">{{ $tasksDone }} / {{ $tasksTotal }} taches finalisees</div>
                </td>
                <td>
                    <div class="kpi-label">Backlog ouvert</div>
                    <div class="kpi-value">{{ $openTasks }}</div>
                    <div class="kpi-help">Taches encore en flux de production</div>
                </td>
                <td>
                    <div class="kpi-label">Retards actifs</div>
                    <div class="kpi-value">{{ $tasksOverdue }}</div>
                    <div class="kpi-help">Point de vigilance planning</div>
                </td>
                <td>
                    <div class="kpi-label">Membres analyses</div>
                    <div class="kpi-value">{{ count($memberWorkload) }}</div>
                    <div class="kpi-help">Ressources avec charge projet</div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <div class="section-title">Graphiques et diagrammes</div>
        <table class="two-col">
            <tr>
                <td>
                    <div class="panel">
                        <p class="chart-header"><strong>Repartition des statuts</strong></p>
                        @foreach ($statusSeries as $statusRow)
                            @php
                                $barWidth = $statusRow['percent'] > 0 ? max($statusRow['percent'], 3) : 0;
                            @endphp
                            <div class="chart-row">
                                <div class="chart-header">{{ $statusRow['label'] }} - <strong>{{ $statusRow['count'] }}</strong> ({{ $statusRow['percent'] }}%)</div>
                                <div class="bar-track">
                                    <div class="bar-fill {{ $statusRow['color'] }}" style="width: {{ $barWidth }}%;"></div>
                                </div>
                            </div>
                        @endforeach

                        <p class="chart-header" style="margin-top: 8px;"><strong>Diagramme de flux</strong></p>
                        <table class="workflow-grid">
                            <tr>
                                @foreach ($statusSeries as $index => $statusRow)
                                    <td>
                                        <div class="workflow-step">
                                            <div class="workflow-count">{{ $statusRow['count'] }}</div>
                                            <div class="workflow-name">{{ $statusRow['label'] }}</div>
                                            <div class="workflow-percent">{{ $statusRow['percent'] }}%</div>
                                        </div>
                                    </td>
                                    @if ($index < count($statusSeries) - 1)
                                        <td class="workflow-arrow">></td>
                                    @endif
                                @endforeach
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="panel">
                        <p class="chart-header"><strong>Repartition des priorites</strong></p>
                        <div class="stacked-bar">
                            @foreach ($prioritySeries as $priorityRow)
                                @php
                                    $priorityWidth = $priorityRow['percent'] > 0 ? max($priorityRow['percent'], 3) : 0;
                                @endphp
                                <span class="segment {{ $priorityRow['color'] }}" style="width: {{ $priorityWidth }}%;"></span>
                            @endforeach
                        </div>

                        <table class="legend-grid">
                            @foreach ($prioritySeries as $priorityIndex => $priorityRow)
                                @if ($priorityIndex % 2 === 0)
                                    <tr>
                                @endif
                                <td>
                                    <span class="dot {{ $priorityRow['color'] }}"></span>
                                    {{ $priorityRow['label'] }}: {{ $priorityRow['count'] }} ({{ $priorityRow['percent'] }}%)
                                </td>
                                @if ($priorityIndex % 2 === 1 || $priorityIndex === count($prioritySeries) - 1)
                                    @if ($priorityIndex % 2 === 0)
                                        <td></td>
                                    @endif
                                    </tr>
                                @endif
                            @endforeach
                        </table>

                        <p class="chart-header" style="margin-top: 8px;"><strong>Velocite sur 6 semaines</strong></p>
                        @if (count($velocitySeries) > 0)
                            <table class="velocity-grid">
                                <tr class="velocity-canvas">
                                    @foreach ($velocitySeries as $velocityPoint)
                                        @php
                                            $doneValue = (int) ($velocityPoint['done'] ?? 0);
                                            $height = $maxVelocity > 0 ? (int) round(($doneValue / $maxVelocity) * 78) : 0;
                                            $height = max($height, $doneValue > 0 ? 8 : 2);
                                        @endphp
                                        <td>
                                            <span class="velocity-bar" style="height: {{ $height }}px; line-height: 12px;">{{ $doneValue }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($velocitySeries as $velocityPoint)
                                        <td>
                                            <div class="velocity-label">{{ $velocityPoint['label'] ?? '-' }}</div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        @else
                            <p class="muted small">Aucune donnee de velocite disponible.</p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <div class="section-title">Charge membres</div>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Membre</th>
                    <th>Role</th>
                    <th>Actif</th>
                    <th>Assignees</th>
                    <th>Ouvertes</th>
                    <th>Done</th>
                    <th>Taux done</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($memberWorkload as $member)
                    @php
                        $assignedCount = (int) ($member['tasks_assigned'] ?? 0);
                        $doneCount = (int) ($member['tasks_done'] ?? 0);
                        $memberDoneRate = $assignedCount > 0 ? round(($doneCount / $assignedCount) * 100, 1) : 0.0;
                    @endphp
                    <tr>
                        <td>{{ $member['name'] }}</td>
                        <td>{{ $roleLabels[$member['project_role']] ?? $member['project_role'] }}</td>
                        <td>{{ $member['active'] ? 'Oui' : 'Non' }}</td>
                        <td>{{ $assignedCount }}</td>
                        <td>{{ $member['tasks_open'] }}</td>
                        <td>{{ $doneCount }}</td>
                        <td>{{ $memberDoneRate }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Aucune donnee de charge membre.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <div class="section-title">Performance equipe</div>
        <table class="table-grid">
            <thead>
                <tr>
                    <th>Membre</th>
                    <th>Taches done</th>
                    <th>Heures loggees</th>
                    <th>Efficacite (done/h)</th>
                    <th>Comparatif</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($teamPerformancePreview as $performanceRow)
                    @php
                        $efficiency = (float) ($performanceRow['efficiency'] ?? 0.0);
                        $effPercent = $maxEfficiency > 0 ? (int) round(($efficiency / $maxEfficiency) * 100) : 0;
                        $effPercent = max(0, min(100, $effPercent));
                    @endphp
                    <tr>
                        <td>{{ $performanceRow['name'] }}</td>
                        <td>{{ $performanceRow['tasks_done'] }}</td>
                        <td>{{ $performanceRow['logged_hours'] }}</td>
                        <td>{{ number_format($efficiency, 3) }}</td>
                        <td>
                            <div class="eff-track">
                                <div class="eff-fill" style="width: {{ max($effPercent, $efficiency > 0 ? 4 : 0) }}%;"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Aucune donnee de performance disponible.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <div class="section-title">Chronologie recente et recommandations</div>
        <table class="two-col">
            <tr>
                <td>
                    <div class="timeline-card">
                        @forelse ($timelinePreview as $event)
                            <div class="timeline-item">
                                <p class="timeline-title">{{ $event['action'] ?? 'Action non renseignee' }}</p>
                                <p class="timeline-meta">{{ $event['date'] ?? 'N/A' }} | {{ $event['actor'] ?? 'Systeme' }}</p>
                                <p class="timeline-meta">Tache: {{ $event['task'] ?? 'N/A' }}</p>
                            </div>
                        @empty
                            <p class="muted">Aucun evenement de timeline.</p>
                        @endforelse
                    </div>
                </td>
                <td>
                    <div class="panel">
                        <p class="chart-header"><strong>Actions recommandees (priorite 7 jours)</strong></p>
                        <ol class="recommendation-list">
                            @foreach ($recommendations as $recommendation)
                                <li>{{ $recommendation }}</li>
                            @endforeach
                        </ol>

                        <div class="note-box">
                            <p><strong>Lecture metier:</strong> score sante base sur progression, activite equipe et maitrise des retards. Ces indicateurs servent a prioriser les decisions de pilotage et la repartition des ressources.</p>
                            <p style="margin-top: 4px;"><strong>Tags projet:</strong> {{ $tagsPreview !== '' ? $tagsPreview : 'Aucun tag significatif' }}</p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <div class="footer">
        Futuristic Africa Lab - Project Manager | Rapport PDF | Page <span class="page"></span>
    </div>
</body>
</html>
