<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTasksTable extends BaseWidget
{
    protected static ?string $heading = 'Dernieres taches mises a jour';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Tache')
                    ->searchable()
                    ->limit(45),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projet')
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Task::STATUS_DONE => 'success',
                        Task::STATUS_DOING => 'warning',
                        Task::STATUS_TODO => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Task::statusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Echeance')
                    ->since(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Maj')
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([5, 10, 25]);
    }

    protected function getTableQuery(): Builder
    {
        return Task::query()->with(['project']);
    }
}
