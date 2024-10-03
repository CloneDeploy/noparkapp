<?php

namespace App\Filament\Resources\RoleResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\ListRecords;


class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return Auth::user()->super ? [
            CreateAction::make(),
        ] : [];
    }
}
