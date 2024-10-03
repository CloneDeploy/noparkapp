<?php
namespace App\Filament\Resources;
use Illuminate\Support\Facades\Auth;
use Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource as MainPermissionResource;

class PermissionResource extends MainPermissionResource
{
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->super;
    }
}
