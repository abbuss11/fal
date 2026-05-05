<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;

class TasksByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Repartition des taches par statut';

    protected function getData(): array
    {
        $statusLabels = [
            'todo' => 'To Do',
            'doing' => 'Doing',
            'done' => 'Done',
        ];

        $statusCounts = Task::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];

        foreach ($statusLabels as $status => $label) {
            $labels[] = $label;
            $data[] = (int) ($statusCounts[$status] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Taches',
                    'data' => $data,
                    'backgroundColor' => ['#60a5fa', '#f59e0b', '#22c55e'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
