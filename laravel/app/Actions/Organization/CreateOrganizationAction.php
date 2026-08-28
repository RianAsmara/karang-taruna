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
     * Create a new organization and make the given user its KETUA. Also
     * becomes their active organization — meaningful once a user can
     * belong to more than one (SwitchOrganizationAction): landing back
     * on an old org right after creating a new one would be a strange
     * result for something the user just explicitly asked for.
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

            $user->forceFill(['active_organization_id' => $organization->id])->save();

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
