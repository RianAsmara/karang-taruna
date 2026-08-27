<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_themes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_hex', 7);
            $table->jsonb('color_light');
            $table->jsonb('color_dark');
            $table->string('disk');
            $table->string('logo_source_path');
            $table->string('logo_source_mime');
            $table->string('logo_mark_1x_path');
            $table->string('logo_mark_2x_path');
            $table->string('logo_mark_3x_path');
            $table->string('logo_icon_path');
            $table->string('logo_mono_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_themes');
    }
};
