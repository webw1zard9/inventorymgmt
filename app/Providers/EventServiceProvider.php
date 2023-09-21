<?php

namespace App\Providers;

use App\BatchLocation;
use App\Events\BatchAllocated;
use App\Events\BatchCreated;
use App\Events\BatchDeleted;
use App\Listeners\BatchAllocatedListener;
use App\Listeners\BatchCreatedListener;
use App\Listeners\BatchDeletedListener;
use App\Observers\BatchLocationObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\UserCreated::class => [
            \App\Listeners\SetUpUserAccountingJournals::class,
        ],
        \App\Events\LocationCreated::class => [
            \App\Listeners\LocationAccountingJournals::class,
        ],
        \App\Events\SaleOrderDelivered::class => [
            \App\Listeners\BookRevenue::class,
        ],
        BatchCreated::class => [
            BatchCreatedListener::class,
        ],
        BatchDeleted::class => [
            BatchDeletedListener::class,
        ],
        BatchAllocated::class => [
            BatchAllocatedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
