<?php

use App\Enums\InventoryLoanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('borrower_membership_id')->constrained('organization_memberships')->cascadeOnDelete();
            $table->foreignUlid('event_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status')->default(InventoryLoanStatus::Borrowed->value);
            $table->text('purpose')->nullable();
            $table->date('borrowed_at');
            $table->date('due_date');
            $table->date('returned_at')->nullable();
            $table->unsignedInteger('returned_quantity')->nullable();
            $table->string('returned_condition')->nullable();
            $table->text('return_note')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'status']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE inventory_loans ADD CONSTRAINT inventory_loans_quantity_positive CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_loans');
    }
};
