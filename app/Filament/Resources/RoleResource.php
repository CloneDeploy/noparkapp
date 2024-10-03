<?php

namespace App\Filament\Resources;
use Illuminate\Support\Facades\Auth;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource as MainRoleResource;
use App\Filament\Resources\RoleResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends MainRoleResource
{
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->super;
    }

    public static function getPages(): array
    {

        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // if(Auth::user()->admin) {
        //     return parent::getEloquentQuery()->where('id', '<>', 2);
        // }

        return parent::getEloquentQuery();

    }
}
