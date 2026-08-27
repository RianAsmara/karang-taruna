<?php

use App\Enums\InventoryCategory;
use App\Enums\InventoryCondition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default(InventoryCategory::Lain->value);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition')->default(InventoryCondition::Baik->value);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->date('last_checked_at')->nullable();
            $table->foreignUlid('responsible_membership_id')->nullable()
                ->constrained('organization_memberships')->nullOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'category']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE inventory_items ADD CONSTRAINT inventory_items_quantity_positive CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
