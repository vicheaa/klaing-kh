<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('access_provider');
            $table->string('original_publisher');
            $table->string('source_url', 2048);
            $table->string('license_status')->default('pending_review');
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sources');
    }
};
