<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_areas', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('level', 8);
            $table->string('code', 8);
            $table->string('name_km');
            $table->string('name_en');
            $table->string('province_code', 2)->nullable();
            $table->string('district_code', 4)->nullable();
            // The village list references a code absent from the commune list.
            $table->string('commune_code', 6)->nullable();

            $table->index(['level', 'code']);
            $table->index(['province_code', 'level', 'code']);
            $table->index(['district_code', 'level', 'code']);
            $table->index(['commune_code', 'level', 'code']);
            $table->foreign('province_code')->references('id')->on('administrative_areas');
            $table->foreign('district_code')->references('id')->on('administrative_areas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_areas');
    }
};
