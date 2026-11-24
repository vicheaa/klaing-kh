<?php

use App\Console\Commands\SyncExchangeRates;
use App\Domain\ExchangeRate\Actions\SyncExchangeRate;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

foreach (['06:00', '17:00'] as $scheduledTime) {
    Schedule::command(SyncExchangeRates::class)
        ->dailyAt($scheduledTime)
        ->timezone('Asia/Phnom_Penh')
        ->when(SyncExchangeRate::isEnabled(...))
        ->withoutOverlapping()
        ->before(static function () use ($scheduledTime): void {
            Log::info('Scheduled DataEF exchange-rate sync starting.', [
                'scheduled_time' => $scheduledTime,
                'timezone' => 'Asia/Phnom_Penh',
            ]);
        });

}
