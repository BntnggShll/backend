<?php

namespace App\Providers;

use App\Models\Product; // <-- PASTIKAN INI ADA
use App\Models\StockMovement;
use App\Observers\ProductObserver; // <-- PASTIKAN INI ADA
use App\Observers\StockMovementObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        StockMovement::class => [StockMovementObserver::class],
    ];


    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // PASTIKAN BARIS INI ADA DI DALAM METHOD BOOT()
        Product::observe(ProductObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}