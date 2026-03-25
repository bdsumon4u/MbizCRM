<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class UserPolicy
{
    public function viewAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function view(Admin|User $actor, User $model): bool
    {
        if ($actor instanceof Admin) {
            return true;
        }

        return $actor->id === $model->id;
    }

    public function create(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function update(Admin|User $actor, User $model): bool
    {
        if ($actor instanceof Admin) {
            return true;
        }

        return $actor->id === $model->id;
    }

    public function delete(Admin|User $actor, User $model): bool
    {
        return $actor instanceof Admin;
    }

    public function restore(Admin|User $actor, User $model): bool
    {
        return $actor instanceof Admin;
    }

    public function forceDelete(Admin|User $actor, User $model): bool
    {
        return $actor instanceof Admin;
    }

    public function deleteAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }
}
