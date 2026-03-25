<?php

namespace App\Policies;

use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\User;

class AdAccountPolicy
{
    public function viewAny(Admin|User $actor): bool
    {
        return true;
    }

    public function view(Admin|User $actor, AdAccount $adAccount): bool
    {
        if ($actor instanceof Admin) {
            return true;
        }

        return $adAccount->user_id === $actor->id;
    }

    public function create(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function update(Admin|User $actor, AdAccount $adAccount): bool
    {
        return $actor instanceof Admin;
    }

    public function delete(Admin|User $actor, AdAccount $adAccount): bool
    {
        return $actor instanceof Admin;
    }

    public function restore(Admin|User $actor, AdAccount $adAccount): bool
    {
        return $actor instanceof Admin;
    }

    public function forceDelete(Admin|User $actor, AdAccount $adAccount): bool
    {
        return $actor instanceof Admin;
    }

    public function deleteAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }
}
