<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Upload>
 */
class UploadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'disk' => 'local',
            'path' => 'uploads/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
