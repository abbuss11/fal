<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Resources\TaskResource\RelationManagers\CommentsRelationManager;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Modules';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Projet')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->label('Assigne a')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(Task::statusOptions())
                    ->default(Task::STATUS_TODO)
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('priority')
                    ->options(Task::priorityOptions())
                    ->required(),
                Forms\Components\TextInput::make('estimated_hours')
                    ->numeric()
                    ->label('Heures estimees'),
                Forms\Components\DateTimePicker::make('due_date')
                    ->label('Echeance'),
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Date completion'),
                Forms\Components\TextInput::make('position')
                    ->numeric()
                    ->default(0),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Assigne a')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success',
                        'doing' => 'warning',
                        'todo' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Task::statusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Echeance')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Task::statusOptions()),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(Task::priorityOptions()),
            ])
            ->actions([
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
            CommentsRelationManager::class,
            ActivityLogsRelationManager::class,
        ];
    }

    /**
     * @return array<int, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'kanban' => Pages\KanbanBoard::route('/kanban'),
            'calendar' => Pages\TaskCalendar::route('/calendar'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
