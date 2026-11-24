<?php

namespace App\Domain\ExchangeRate\Services;

use App\Domain\DataSource\Models\DataSource;
use App\Domain\ExchangeRate\Data\CurrencyPair;
use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\Ingestion\Models\SyncRun;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;

class ExchangeRateQueries
{
    public function source(): ?DataSource
    {
        return DataSource::query()->where('slug', DataSource::NBC_EXCHANGE_RATES)->first();
    }

    public function archive(): ?DataSource
    {
        return DataSource::query()->where('slug', DataSource::GDT_EXCHANGE_RATE_HISTORY)->first();
    }

    /** @return list<string> */
    public function currencies(?DataSource $source): array
    {
        $currencies = ExchangeRate::query()
            ->where('data_source_id', $source?->id)
            ->where('quote_currency', ExchangeRate::QUOTE_CURRENCY)
            ->where('rate_type', ExchangeRate::RATE_TYPE)
            ->where('effective_date', '<=', now('Asia/Phnom_Penh')->toDateString())
            ->distinct()
            ->get(['base_currency'])
            ->toBase()
            ->map(fn (ExchangeRate $rate): string => $rate->base_currency)
            ->push(ExchangeRate::BASE_CURRENCY, ExchangeRate::QUOTE_CURRENCY)
            ->unique()
            ->sort()
            ->all();

        return array_values($currencies);
    }

    public function latest(?DataSource $source, ?DataSource $archive = null, ?CurrencyPair $pair = null): ?ExchangeRate
    {
        return $this->pairRates($source, $archive, $pair ?? new CurrencyPair)->latest('exchange_rates.effective_date')->first();
    }

    /** @return Collection<int, ExchangeRate> */
    public function dailyTrend(?DataSource $source, ?string $from, ?string $to, ?DataSource $archive = null, ?CurrencyPair $pair = null): Collection
    {
        $today = now('Asia/Phnom_Penh')->toDateString();
        $end = min($to ?? $today, $today);
        $windowStart = CarbonImmutable::parse($end)->subDays(89)->toDateString();

        return $this->pairRates($source, $archive, $pair ?? new CurrencyPair)
            ->whereBetween('exchange_rates.effective_date', [max($from ?? $windowStart, $windowStart), $end])
            ->orderBy('exchange_rates.effective_date')
            ->get();
    }

    /** @return LengthAwarePaginator<int, ExchangeRate> */
    public function history(?DataSource $source, ?string $from, ?string $to, int $perPage, ?DataSource $archive = null, ?CurrencyPair $pair = null): LengthAwarePaginator
    {
        return $this->pairRates($source, $archive, $pair ?? new CurrencyPair)
            ->when($from !== null, fn (Builder $query) => $query->where('exchange_rates.effective_date', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->where('exchange_rates.effective_date', '<=', $to))
            ->latest('exchange_rates.effective_date')
            ->paginate($perPage);
    }

    /**
     * @return array{
     *     access_provider: string,
     *     original_publisher: string,
     *     source_url: string,
     *     last_synced_at: ?string,
     *     is_stale: bool,
     *     sync_failed: bool
     * }
     */
    public function metadata(?DataSource $source, ?ExchangeRate $latest): array
    {
        $lastStatus = $source === null ? null : SyncRun::query()
            ->where('data_source_id', $source->id)
            ->whereIn('status', [SyncRun::SUCCESS, SyncRun::FAILED])
            ->latest('id')
            ->value('status');

        return [
            'access_provider' => $source->access_provider ?? DataSource::NBC_ATTRIBUTION['access_provider'],
            'original_publisher' => $source->original_publisher ?? DataSource::NBC_ATTRIBUTION['original_publisher'],
            'source_url' => $source->source_url ?? DataSource::NBC_ATTRIBUTION['source_url'],
            'last_synced_at' => $source?->last_synced_at?->toISOString(),
            'is_stale' => $latest !== null
                && $latest->effective_date <= now('Asia/Phnom_Penh')->subDays(3)->toDateString(),
            'sync_failed' => $lastStatus === SyncRun::FAILED,
        ];
    }

    /** @return Builder<ExchangeRate> */
    private function pairRates(?DataSource $source, ?DataSource $archive, CurrencyPair $pair): Builder
    {
        if ($pair->base === $pair->quote) {
            return ExchangeRate::query()->whereRaw('1 = 0');
        }

        if ($pair->includesRiel()) {
            $currency = $pair->foreignCurrency();

            return $this->rielQuotes($source, $currency, $currency === ExchangeRate::BASE_CURRENCY ? $archive : null);
        }

        $targetQuotes = $this->rielQuotes($source, $pair->quote)->select(['effective_date', 'rate', 'unit']);

        return $this->rielQuotes($source, $pair->base)
            ->joinSub($targetQuotes, 'target_quotes', function (JoinClause $join): void {
                $join->on('exchange_rates.effective_date', '=', 'target_quotes.effective_date');
            })
            ->select(['exchange_rates.*', 'target_quotes.rate as target_rate', 'target_quotes.unit as target_unit'])
            ->withCasts(['target_rate' => 'decimal:10', 'target_unit' => 'integer']);
    }

    /** @return Builder<ExchangeRate> */
    private function rielQuotes(?DataSource $source, string $currency, ?DataSource $archive = null): Builder
    {
        $quotes = ExchangeRate::query()
            ->where('exchange_rates.base_currency', $currency)
            ->where('exchange_rates.quote_currency', ExchangeRate::QUOTE_CURRENCY)
            ->where('exchange_rates.effective_date', '<=', now('Asia/Phnom_Penh')->toDateString());

        $liveRates = (clone $quotes)
            ->where('data_source_id', $source?->id)
            ->where('rate_type', ExchangeRate::RATE_TYPE);

        if ($archive === null) {
            return $liveRates;
        }

        return $quotes->where(function (Builder $matches) use ($source, $archive, $liveRates): void {
            $matches
                ->where(fn (Builder $query) => $query
                    ->where('data_source_id', $source?->id)
                    ->where('rate_type', ExchangeRate::RATE_TYPE))
                ->orWhere(fn (Builder $query) => $query
                    ->where('data_source_id', $archive->id)
                    ->where('rate_type', ExchangeRate::OFFICIAL_RATE_TYPE)
                    ->whereNotIn('effective_date', $liveRates->select('effective_date')));
        });
    }
}
