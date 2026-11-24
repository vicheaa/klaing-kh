<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_source_id')->constrained()->restrictOnDelete();
            $table->string('status', 24);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->dateTime('retry_after_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
