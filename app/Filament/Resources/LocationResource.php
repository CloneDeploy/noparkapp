<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Location;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Livewire\Component as Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Filament\Forms\Components\ViewField;
use Illuminate\Database\Eloquent\Builder;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use App\Filament\Resources\LocationResource\Pages;
use App\Models\Currency;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function country(): string
    {
        return Session::get('country', 'FR');
    }

    public static function curr()
    {
        return Session::get('curr', 2);
    }

    public static function livewire(Livewire $livewire): void
    {
        $livewire->log('country');
        $livewire->log('currency');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::user()->id)
                    ->hidden(fn() => Auth::user()->admin | Auth::user()->super),
                Forms\Components\Section::make('Google Parking Spot')
                    ->description('Enter the name of the parking spot')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Grid::make()
                            ->visibleOn(['view'])
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                ViewField::make('qrcode')
                                ->label('QR code')
                                ->view('filament.forms.image')
                                ->columnSpan(4),
                            ]),
                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Location user')
                                    ->relationship(
                                        name: 'user',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => Auth::user()->admin ? $query->where('id', '<>', 1) : $query,
                                    )
                                    ->reactive()
                                    ->visible(fn() => Auth::user()->admin | Auth::user()->super)
                                    ->columnSpan(['default' => 1, 'xl' => 3]),
                            ]),
                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([

                                Forms\Components\Select::make('country')
                                    ->reactive()
                                    ->options([
                                        "UK" => "United Kingdom",
                                        "BE" => "Belgium",
                                        "US" => "United States",
                                        "FI" => "Finland",
                                        "DK" => "Denmark",
                                        "FR" => "France",
                                        "FO" => "The Faroe Islands",
                                        "IS" => "Iceland",
                                        "IT" => "Italy",
                                        "LI" => "Liechtenstein",
                                        "NL" => "Netherlands",
                                        "NO" => "Norway",
                                        "CH" => "Switzerland",
                                        "SK" => "Slovakia",
                                        "SI" => "Slovenia",
                                        "ES" => "Spain",
                                        "SE" => "Sweden",
                                        "CZ" => "Czech Republic",
                                        "DE" => "Germany",
                                        "HU" => "Hungary",
                                        "AT" => "Austria"
                                    ])
                                    ->afterStateUpdated(function (Livewire $livewire, $state) {
                                            switch ($state) {
                                                case 'UK':
                                                    Session::put('curr', 1);
                                                    break;
                                                case 'US':
                                                    Session::put('curr', 2);
                                                    break;
                                                case 'CH':
                                                    Session::put('curr', 4);
                                                    break;
                                                case 'DK':
                                                    Session::put('curr', 5);
                                                    break;
                                                default:
                                                    Session::put('curr', 3);
                                            }
                                        Session::put('country', $state);
                                        Log::info('Country changed to ' . $state);
                                        $livewire->dispatch('refresh-page', 'country changed to ' . $state);
                                    })
                                    ->default(self::country())
                                    ->columnSpan(['default' => 1, 'xl' => 3]),
                                Forms\Components\Select::make('currency_id')
                                        ->reactive()
                                        ->label('Currency')
                                        ->relationship(name: 'currency', titleAttribute: 'name')
                                        ->preload()
                                        ->native(false)
                                        ->columnSpan(['default' => 1, 'xl' => 3])
                                        ->afterStateUpdated(function (Livewire $livewire, $state) {
                                            Session::put('curr', $state);
                                            Log::info('Currency changed to ' . $state);
                                            $livewire->dispatch('refresh-page', $state);
                                        })
                                        ->default(self::curr()),
                                Forms\Components\TextInput::make('address')
                                    ->columnSpan(['default' => 1, 'xl' => 6])->required(),
                                Forms\Components\TextInput::make('price')
                                    ->default(5)
                                    ->prefix(function() {
                                        $curr = self::curr();
                                        $model = Currency::find($curr);
                                        return $model->symbol;
                                    })
                                    ->required()
                                    ->placeholder(2)
                                    ->suffix('/ hour')
                                    ->columnSpan(['default' => 1, 'xl' => 3]),
                            ]),
                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Parking code')
                                    ->required()
                                    ->placeholder('P76126')
                                    ->columnSpan(['default' => 1, 'xl' => 2]),
                                Forms\Components\TextInput::make('name')
                                    ->label('Location name')
                                    ->required()
                                    ->placeholder('Location name')
                                    ->columnSpan(['default' => 1, 'xl' => 4]),
                                Forms\Components\TextInput::make('lat')
                                    ->placeholder('Latitude')
                                    ->readOnly()
                                    ->required()
                                    ->columnSpan(['default' => 1, 'xl' => 3]),
                                Forms\Components\TextInput::make('lng')
                                    ->placeholder('Longitude')
                                    ->readOnly()
                                    ->required()
                                    ->columnSpan(['default' => 1, 'xl' => 3]),
                            ]),

                        Forms\Components\Grid::make()
                            ->columnSpanFull()
                            ->columns(12)
                            ->schema([
                                Map::make('location')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('lat', $state['lat']);
                                        $set('lng', $state['lng']);
                                    })
                                    ->autocompleteReverse(true)
                                    ->reverseGeocode([
                                        'name' => '%n %S',
                                    ])
                                    ->defaultLocation([39.526610, -107.727261])
                                    ->defaultZoom(21)
                                    ->autocomplete(
                                        fieldName: 'address',
                                        countries: [self::country()],
                                    )
                                    ->columnSpanFull()
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Parking location')
                    ->description(fn(Model $record) => $record->address),

                Tables\Columns\TextColumn::make('price')
                    ->label('Parking price')
                    ->formatStateUsing(fn($state) => "€" . number_format($state, 2) . " EUR"),
                Tables\Columns\TextColumn::make('lat')
                    ->label('Latitude'),
                Tables\Columns\TextColumn::make('lng')
                    ->label('Longitude'),
                Tables\Columns\TextColumn::make('user.name')->visible(fn(): bool => Auth::user()->super || Auth::user()->admin),
                Tables\Columns\TextColumn::make('created_at')
                    ->since(),

            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
