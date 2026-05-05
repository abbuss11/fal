<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = 'Historique';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Acteur')
                    ->placeholder('Systeme')
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge(),
                Tables\Columns\TextColumn::make('meta')
                    ->label('Details')
                    ->formatStateUsing(function (?array $state): string {
                        if (! $state) {
                            return '-';
                        }

                        return (string) json_encode($state, JSON_UNESCAPED_UNICODE);
                    })
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
