<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;

class ChartOfAccount extends Model
{
    use AccountingJournal;

    protected $guarded = [];

    public static function Cash()
    {
        return self::whereName('Cash')->first();
    }

    public static function AR()
    {
        return self::whereName('Accounts Receivable')->first();
    }

    public static function AP()
    {
        return self::whereName('Accounts Payable')->first();
    }

    public static function Revenue()
    {
        return self::whereName('Revenue')->first();
    }

    public static function Inventory()
    {
        return self::whereName('Inventory')->first();
    }

    public static function COG()
    {
        return self::whereName('Cost of Goods Sold')->first();
    }

    public static function PrepaidInventory()
    {
        return self::whereCode('6001')->first();
    }

    public static function VendorCredits()
    {
        return self::whereCode('7001')->first();
    }

    public function getJournalBalanceInDollarsAttribute()
    {
        $multiplier = (in_array($this->journal->ledger->type, ['asset', 'expense']) ? -1 : 1);

        return $this->journal->getCurrentBalanceInDollars() * $multiplier;
    }
}
