<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Number;
use App\Models\User;

class NumberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any Number');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Number $number): bool
    {
        return $user->checkPermissionTo('view Number');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create Number');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Number $number): bool
    {
        return $user->checkPermissionTo('update Number');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Number $number): bool
    {
        return $user->checkPermissionTo('delete Number');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Number $number): bool
    {
        return $user->checkPermissionTo('restore Number');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Number $number): bool
    {
        return $user->checkPermissionTo('force-delete Number');
    }
}
