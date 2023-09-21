<?php

namespace App\Listeners;

use App\Events\UserCreated;
use Scottlaurent\Accounting\Models\Ledger;

class SetUpUserAccountingJournals
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
     * @param  UserCreated  $event
     * @return void
     */
    public function handle(UserCreated $event)
    {
        $user = $event->user;
        $user_journal = $event->user->initJournal();

        $ledger = null;
        if ($user->hasRole('vendor')) {
            $ledger = Ledger::whereName('Accounts Payable')->first();
        } elseif ($user->hasRole('customer')) {
            $ledger = Ledger::whereName('Accounts Receivable')->first();
        }

        if ($ledger) {
            $user_journal->assignToLedger($ledger);
        }
    }
}
