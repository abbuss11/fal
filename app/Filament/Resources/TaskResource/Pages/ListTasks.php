<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kanban')
                ->label('Vue Kanban')
                ->icon('heroicon-o-view-columns')
                ->url(TaskResource::getUrl('kanban')),
            Actions\Action::make('calendar')
                ->label('Vue Calendrier')
                ->icon('heroicon-o-calendar')
                ->url(TaskResource::getUrl('calendar')),
            Actions\CreateAction::make(),
        ];
    }
}
