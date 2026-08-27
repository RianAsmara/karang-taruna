<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Upload;
use App\Models\User;

class UploadPolicy
{
    public function create(User $user, Organization $organization): bool
    {
        return $user->roleIn($organization) !== null;
    }

    public function view(User $user, Upload $upload): bool
    {
        return $user->roleIn($upload->organization) !== null;
    }
}
