<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Management';

    //protected static bool $shouldRegisterNavigation = false;


    static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->super || Auth::user()->admin;
    }




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Google Parking Spot')
                    ->description('Enter the name of the parking spot')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                Forms\Components\FileUpload::make('avatar_url')
                                    ->avatar()
                                    ->label('Avatar')
                                    ->disk('cloudinary')
                                    ->directory('avatars')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 1, 'xl' => 4]),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 1, 'xl' => 4]),
                            ]),
                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state) {
                                        $component->state('');
                                    })
                                    ->required()
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (Page $livewire) => ($livewire instanceof CreateRecord))
                                    ->revealable()
                                    ->columnSpan(['default' => 1, 'xl' => 4]),
                            ]),
                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                Forms\Components\Select::make('roles')
                                    //->multiple()
                                    ->relationship(
                                        name: 'roles',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => Auth::user()->admin ? $query->where('id', '<>', 2) : $query,
                                    )
                                    ->preload()
                                    ->searchable()
                                    ->columnSpan(['default' => 1, 'xl' => 4]),
                            ]),

                    ]),
                //Forms\Components\TextInput::make('custom_fields'),

            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->visible(fn(): bool => Auth::user()->super || Auth::user()->admin)
                    ->label('Role'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->placeholder('Not deleted')
                    ->dateTime()
                    ->visible(fn(): bool => Auth::user()->super),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Impersonate::make()
                        ->label('Log in as')
                        ->grouped()
                        ->visible(fn(User $record) => !$record->trashed())
                        ->redirectTo('/dashboard'),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (User $record): bool => !$record->super && !$record->trashed() && Auth::id() !== $record->id),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])

            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                //     Tables\Actions\ForceDeleteBulkAction::make(),
                //     Tables\Actions\RestoreBulkAction::make(),
                // ]),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutRole('Super Admin')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
