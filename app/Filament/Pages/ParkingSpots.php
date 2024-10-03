<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SpotsMap;
use App\Models\Location;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class ParkingSpots extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $spots = [];
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.parking-spots';

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
    protected function getHeaderWidgets(): array
    {
        return [
            SpotsMap::class
        ];
    }

    public function mount(): void
    {
        $this->spots = Location::all()->toArray();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\TextInput::make('name')
                    ->required(),
            ])
            ->statePath('spots');
    }


}
