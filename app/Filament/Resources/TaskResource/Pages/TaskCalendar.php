<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class TaskCalendar extends Page
{
    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.resources.task-resource.pages.task-calendar';

    protected static ?string $title = 'Calendrier des taches';

    public ?int $projectId = null;

    public string $month;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjectsProperty(): Collection
    {
        return Project::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCalendarDaysProperty(): array
    {
        try {
            $monthDate = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        } catch (\Throwable) {
            $monthDate = now()->startOfMonth();
        }
        $start = $monthDate->copy()->startOfWeek();
        $end = $monthDate->copy()->endOfMonth()->endOfWeek();

        $tasks = Task::query()
            ->with(['project', 'assignee'])
            ->whereBetween('due_date', [$start, $end])
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->orderBy('due_date')
            ->get()
            ->groupBy(fn (Task $task): string => $task->due_date?->format('Y-m-d') ?? 'none');

        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateKey = $cursor->format('Y-m-d');

            $days[] = [
                'date' => $cursor->copy(),
                'is_current_month' => $cursor->month === $monthDate->month,
                'tasks' => $tasks[$dateKey] ?? collect(),
            ];

            $cursor->addDay();
        }

        return $days;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour liste')
                ->icon('heroicon-o-list-bullet')
                ->url(TaskResource::getUrl('index')),
            Action::make('kanban')
                ->label('Vue Kanban')
                ->icon('heroicon-o-view-columns')
                ->url(TaskResource::getUrl('kanban')),
        ];
    }
}
