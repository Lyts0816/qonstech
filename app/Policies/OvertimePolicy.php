<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OvertimePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Overtime $overtime): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Overtime $overtime): bool
    {
        //
        return $user->role === User::ROLE_ADMIN || $user->isClerk();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Overtime $overtime): bool
    {
        //
        return $user->role === User::ROLE_ADMIN || $user->isClerk();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Overtime $overtime): bool
    {
        //
        return $user->role === User::ROLE_ADMIN || $user->isClerk();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Overtime $overtime): bool
    {
        //
        return $user->role === User::ROLE_ADMIN || $user->isClerk();
    }
}
