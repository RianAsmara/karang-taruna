<?php

namespace Database\Factories;

use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransactionAttachment>
 */
class FinancialTransactionAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'financial_transaction_id' => FinancialTransaction::factory(),
            'disk' => 'local',
            'path' => 'financial-transaction-attachments/'.fake()->uuid().'.jpg',
            'original_name' => 'bukti.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'uploaded_by' => User::factory(),
        ];
    }
}
