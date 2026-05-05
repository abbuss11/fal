<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Membres';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Role global')
                    ->badge(),
                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Role projet')
                    ->badge(),
                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Derniere activite')
                    ->since()
                    ->placeholder('N/A'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Ajouter un membre')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('role')
                            ->label('Role dans le projet')
                            ->options([
                                'project_manager' => 'Chef de projet',
                                'member' => 'Membre',
                            ])
                            ->default('member')
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('updateMembership')
                    ->label('Mettre a jour')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Role projet')
                            ->options([
                                'project_manager' => 'Chef de projet',
                                'member' => 'Membre',
                            ])
                            ->default(fn ($record) => $record->pivot->role ?? 'member')
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(fn ($record) => (bool) ($record->pivot->is_active ?? true)),
                    ])
                    ->action(function ($record, array $data): void {
                        $this->getOwnerRecord()->members()->updateExistingPivot($record->id, [
                            'role' => $data['role'],
                            'is_active' => (bool) ($data['is_active'] ?? true),
                        ]);
                    }),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
