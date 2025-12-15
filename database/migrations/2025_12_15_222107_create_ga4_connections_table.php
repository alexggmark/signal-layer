<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ga4_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('property_id');
            $table->string('property_name');
            $table->text('refresh_token');
            $table->timestamp('last_report_generated_at')->nullable();
            $table->timestamp('connected_at');
            $table->timestamps();

            $table->unique('user_id');
            $table->index('property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga4_connections');
    }
};
