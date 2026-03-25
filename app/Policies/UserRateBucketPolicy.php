<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRateBucket;

class UserRateBucketPolicy
{
    public function viewAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function view(Admin|User $actor, UserRateBucket $model): bool
    {
        return $actor instanceof Admin;
    }

    public function create(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function update(Admin|User $actor, UserRateBucket $model): bool
    {
        return $actor instanceof Admin;
    }

    public function delete(Admin|User $actor, UserRateBucket $model): bool
    {
        return $actor instanceof Admin;
    }

    public function deleteAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }
}
