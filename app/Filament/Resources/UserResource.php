<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('job_title')
                    ->label('Poste')
                    ->maxLength(120),
                Forms\Components\TextInput::make('phone')
                    ->label('Telephone')
                    ->maxLength(60),
                Forms\Components\Textarea::make('bio')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options(User::roleOptions())
                    ->default(User::ROLE_MEMBER)
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('permissions')
                    ->relationship('permissions', 'label')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->label('Permissions directes'),
                Forms\Components\Toggle::make('notify_email')
                    ->label('Notifications email')
                    ->default(true),
                Forms\Components\Toggle::make('notify_realtime')
                    ->label('Notifications temps reel')
                    ->default(true),
                Forms\Components\Toggle::make('notify_push')
                    ->label('Notifications push')
                    ->default(true),
                Forms\Components\Toggle::make('is_active')
                    ->label('Compte actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Poste')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => User::roleOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_MANAGER => 'primary',
                        User::ROLE_PROJECT_MANAGER => 'warning',
                        User::ROLE_MEMBER => 'info',
                        User::ROLE_CLIENT => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions directes')
                    ->counts('permissions'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Derniere activite')
                    ->since()
                    ->placeholder('Jamais'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
