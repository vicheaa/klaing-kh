<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_source_id')->constrained()->restrictOnDelete();
            $table->char('base_currency', 3);
            $table->char('quote_currency', 3);
            $table->string('rate_type', 24);
            $table->date('effective_date');
            $table->decimal('rate', 24, 10);
            $table->dateTime('source_recorded_at')->nullable();
            $table->char('raw_hash', 64);
            $table->timestamps();

            $table->unique(
                ['data_source_id', 'base_currency', 'quote_currency', 'rate_type', 'effective_date'],
                'exchange_rates_source_pair_type_date_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
