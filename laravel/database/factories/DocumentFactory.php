<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'disk' => 'local',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }

    public function archived(): static
    {
        return $this->state(['archived_at' => now()]);
    }
}
