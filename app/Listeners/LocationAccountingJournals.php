<?php

namespace App\Listeners;

use App\Events\LocationCreated;
use Scottlaurent\Accounting\Models\Ledger;

class LocationAccountingJournals
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  LocationCreated  $event
     * @return void
     */
    public function handle(LocationCreated $event)
    {
        $location = $event->location;

        $location->initJournal()->assignToLedger(Ledger::whereName('Revenue')->first());
    }
}
