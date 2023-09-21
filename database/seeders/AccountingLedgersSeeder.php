<?php

namespace Database\Seeders;

use App\ChartOfAccount;
use Illuminate\Database\Seeder;
use Scottlaurent\Accounting\Models\Ledger;

class AccountingLedgersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        DB::statement('SET FOREIGN_KEY_CHECKS = 0'); // disable foreign key constraints

        $ledgers = [
            [
                'name' => 'Assets',
                'type' => 'asset',
            ],
            [
                'name' => 'Liabilities',
                'type' => 'liability',
            ],
            [
                'name' => 'Company Equity',
                'type' => 'equity',
            ],
            [
                'name' => 'Revenue',
                'type' => 'income',
            ],
            [
                'name' => 'Expenses',
                'type' => 'expense',
            ],
            [
                'name' => 'Accounts Receivable',
                'type' => 'asset',
            ],
            [
                'name' => 'Accounts Payable',
                'type' => 'liability',
            ],
            [
                'name' => 'Inventory',
                'type' => 'asset',
            ],
            [
                'name' => 'Cash',
                'type' => 'asset',
            ],
            [
                'name' => 'Cost of Goods Sold',
                'type' => 'expense',
            ],
        ];

        foreach ($ledgers as $ledger) {
            if (is_null(Ledger::whereName($ledger['name'])->first())) {
                Ledger::create($ledger);
            }
        }

        $chart_of_accounts = [
            [
                'data' => ['name' => 'Cash', 'code' => '1001'],
                'ledger' => 'Cash',
            ],
            [
                'data' => ['name' => 'Inventory', 'code' => '1030'],
                'ledger' => 'Inventory',
            ],
            [
                'data' => ['name' => 'Payroll Payable', 'code' => '2030'],
                'ledger' => 'Liabilities',
            ],
            [
                'data' => ['name' => 'Commission Payable', 'code' => '2040'],
                'ledger' => 'Liabilities',
            ],
            [
                'data' => ['name' => 'Revenue', 'code' => '4001'],
                'ledger' => 'Revenue',
            ],
            [
                'data' => ['name' => 'Cost of Goods Sold', 'code' => '5001'],
                'ledger' => 'Cost of Goods Sold',
            ],
            [
                'data' => ['name' => 'Prepaid Inventory', 'code' => '6001'],
                'ledger' => 'Liabilities',
            ],
            [
                'data' => ['name' => 'Vendor Credits', 'code' => '7001'],
                'ledger' => 'Assets',
            ],
        ];

        foreach ($chart_of_accounts as $chart_of_account) {
            if (is_null(ChartOfAccount::whereName($chart_of_account['data']['name'])->first())) {
                $coa = ChartOfAccount::create($chart_of_account['data'])->initJournal();
                $coa->assignToLedger(Ledger::whereName($chart_of_account['ledger'])->first());
            }
        }
    }
}
