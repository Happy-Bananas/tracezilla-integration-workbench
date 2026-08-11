<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\PullOrdersFromShopifyIndividual::class,
        \App\Console\Commands\PullOrdersFromShopifyCollected::class,
        \App\Console\Commands\PullCatalogFromShopify::class,
        \App\Console\Commands\PushCatalogToShopify::class,
        \App\Console\Commands\UpdateInventoryInShopify::class,
        \App\Console\Commands\CheckLocationsInShopify::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
