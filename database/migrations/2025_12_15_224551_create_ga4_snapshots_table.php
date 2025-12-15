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
        Schema::create('ga4_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')
                ->constrained('ga4_connections')
                ->cascadeOnDelete();
            $table->json('snapshot_data');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('connection_id');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ga4_snapshots');
    }
};
