<?php

namespace App\Policies;

use Modules\Auth\Models\User;

class UserPolicy
{
    public function update(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $authUser->isAdmin();
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() && $authUser->id !== $user->id;
    }

    public function view(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $authUser->isAdmin();
    }

    public function block(User $authUser, User $user): bool
    {
        return $authUser->isAdmin() && $authUser->id !== $user->id;
    }

    public function unblock(User $authUser, User $user): bool
    {
        return $authUser->isAdmin();
    }
}
