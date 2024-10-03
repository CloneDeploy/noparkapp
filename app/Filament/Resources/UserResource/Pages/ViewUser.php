<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        return [
            Impersonate::make()->record($record)
                ->visible(fn() => !$record->trashed()),
            Actions\EditAction::make(),
        ];
    }
}
