<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::listen(function (QueryExecuted $query): void {
            $threshold = (int) env('SLOW_QUERY_THRESHOLD_MS', 750);

            if ($threshold > 0 && $query->time >= $threshold) {
                Log::channel(config('logging.slow_queries_channel', 'daily'))->warning('Slow database query detected', [
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                    'sql' => $query->sql,
                ]);
            }
        });
    }
}
