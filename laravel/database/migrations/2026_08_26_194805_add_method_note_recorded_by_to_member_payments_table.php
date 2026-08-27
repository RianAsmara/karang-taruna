<?php

use App\Enums\MemberPaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * mobile-screens.md § 16's "Catat pembayaran iuran" sheet (method
 * Segmented: Tunai/Transfer, optional note) and § 17's "Riwayat" (amount,
 * date recorded, who recorded it) both need fields this table didn't have.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->string('method')->default(MemberPaymentMethod::Tunai->value);
            $table->text('note')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('member_payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->dropColumn(['method', 'note', 'recorded_by']);
        });
    }
};
