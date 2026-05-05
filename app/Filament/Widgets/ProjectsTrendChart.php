<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use Filament\Widgets\ChartWidget;

class ProjectsTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tendance mensuelle projets et livraisons';

    protected function getData(): array
    {
        $labels = [];
        $projectsData = [];
        $tasksCompletedData = [];

        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $start = now()->startOfMonth()->subMonths($monthOffset);
            $end = (clone $start)->endOfMonth();

            $labels[] = $start->format('M Y');
            $projectsData[] = Project::query()
                ->whereBetween('created_at', [$start, $end])
                ->count();
            $tasksCompletedData[] = Task::query()
                ->where('status', 'done')
                ->whereBetween('completed_at', [$start, $end])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Nouveaux projets',
                    'data' => $projectsData,
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.18)',
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Taches terminees',
                    'data' => $tasksCompletedData,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.18)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

