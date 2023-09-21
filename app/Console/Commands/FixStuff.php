<?php

namespace App\Console\Commands;

use App\BatchLocation;
use App\OrderTransaction;
use Illuminate\Console\Command;
use Scottlaurent\Accounting\Models\JournalTransaction;

class FixStuff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:stuff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $txn = JournalTransaction::where('id', 'a608fc46-baf3-49d0-8a54-c1dc3089dec4')->first();

        dd($txn);


    }
}
