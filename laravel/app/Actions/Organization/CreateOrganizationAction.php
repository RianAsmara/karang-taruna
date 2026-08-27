<?php

namespace App\Actions\Organization;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    /**
     * Create a new organization and make the given user its KETUA.
     */
    public function handle(User $user, string $name): Organization
    {
        return DB::transaction(function () use ($user, $name) {
            $organization = Organization::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
            ]);

            $organization->memberships()->create([
                'user_id' => $user->id,
                'role' => OrganizationRole::Ketua,
            ]);

            return $organization;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
