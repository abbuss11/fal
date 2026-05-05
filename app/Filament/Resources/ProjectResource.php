<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\MembersRelationManager;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Modules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->label('Chef de projet')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('members')
                    ->relationship('members', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->label('Membres'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('objective')
                    ->label('Objectif')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(Project::statusOptions())
                    ->required(),
                Forms\Components\Select::make('priority')
                    ->options(Project::priorityOptions())
                    ->required(),
                Forms\Components\TextInput::make('budget')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Date debut'),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Date echeance'),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Date cloture'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Chef de projet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'planning' => 'info',
                        'on_hold' => 'warning',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Project::statusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Project::PRIORITY_URGENT => 'danger',
                        Project::PRIORITY_HIGH => 'warning',
                        Project::PRIORITY_MEDIUM => 'info',
                        Project::PRIORITY_LOW => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Project::priorityOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('tasks_count')
                    ->counts('tasks')
                    ->label('Taches')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Echeance')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Project::statusOptions()),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Project::priorityOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('report')
                    ->label('Rapport')
                    ->icon('heroicon-o-document-chart-bar')
                    ->url(fn (Project $record): string => static::getUrl('report', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'report' => Pages\ProjectReport::route('/{record}/report'),
        ];
    }
}
