<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::query()
            ->whereIn('status', ['planning', 'active', 'on_hold'])
            ->count();

        $totalTasks = Task::count();
        $completedTasks = Task::query()->where('status', 'done')->count();
        $completionRate = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 1)
            : 0;

        $overdueTasks = Task::query()
            ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING])
            ->whereDate('due_date', '<', now())
            ->count();

        return [
            Stat::make('Utilisateurs', number_format(User::count()))
                ->description('Comptes actifs')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            Stat::make('Projets', number_format($totalProjects))
                ->description($activeProjects.' actifs')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            Stat::make('Completion taches', $completionRate.'%')
                ->description($completedTasks.' / '.$totalTasks.' terminees')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Taches en retard', number_format($overdueTasks))
                ->description('A traiter en priorite')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
