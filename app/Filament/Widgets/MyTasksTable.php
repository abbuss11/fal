<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MyTasksTable extends BaseWidget
{
    protected static ?string $heading = 'Mes taches';

    public function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->query(
                Task::query()
                    ->with('project')
                    ->where('assigned_to', $userId)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Tache')
                    ->searchable()
                    ->limit(45),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projet')
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Task::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Task::STATUS_DONE => 'success',
                        Task::STATUS_DOING => 'warning',
                        Task::STATUS_TODO => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Echeance')
                    ->since(),
            ])
            ->defaultSort('due_date', 'asc')
            ->paginated([5, 10]);
    }
}
