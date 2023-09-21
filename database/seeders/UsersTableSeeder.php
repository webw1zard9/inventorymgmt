<?php

namespace Database\Seeders;

use App\Location;
use App\Permission;
use App\Role;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Scottlaurent\Accounting\Models\Ledger;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            DB::beginTransaction();

            $accounting_permissions = [
                'accounting',
                'accounting.sales.summary',
                'accounting.payables',
                'accounting.receivables',
                'accounting.profitloss',
                'accounting.dailycloseout',
                'accounting.inventoryloss',
                'dashboard.revenue_summary',
                'dashboard.revenue_month_o_month',
                'dashboard.revenue_qtr_o_qtr',
                'dashboard.top_products_by_category',
                'dashboard.revenue_by_category',
                'dashboard.sales_rep_revenue_by_category',
                'dashboard.inventory_location',
                'dashboard.inventory_vendor',
            ];

            $users = [
                'admin' => [
                    'Admin' => [
                        'permissions' => $accounting_permissions,
                    ],
                ],
                'vendor' => [
                    'V1',
                ],
                'customer' => [
                    'C1',
                ],
                'locationmanager' => [
                    'LM1',
                ],
                'salesrep' => [
                    'SR1',
                ],
            ];

            foreach ($users as $user_role => $usernames) {
                $role = Role::whereName($user_role)->first();

                foreach ($usernames as $key => $val) {
                    $user_data = null;
                    $location_name = null;

                    if (is_int($key)) {
                        $username = $val;
                    } elseif (is_array($val)) {
                        $username = $key;
                        $user_data = $val;
                    } else {
                        $username = $key;
                        $location_name = $val;
                    }

                    $user = User::create([
                        'name' => $username,
                        'email' => strtolower(preg_replace('/ /', '', $username)).'@test.com',
                        'password' => 'admin',
                        'super_user' => ($username == 'Admin' ? 1 : 0),
                    ]);

                    $user->roles()->attach($role);

                    $journal = $user->initJournal();

                    $ledger = null;
                    if ($user_role == 'vendor') {
                        $ledger = Ledger::whereName('Accounts Payable')->first();
                        $journal->assignToLedger($ledger);
                    }

                    if ($user_role == 'customer') {
                        $ledger = Ledger::whereName('Accounts Receivable')->first();
                        $journal->assignToLedger($ledger);
                    }

                    if ($location_name) {
                        $location = Location::whereName($location_name)->first();
                        $user->locations()->attach([$location->id]);
                    }

                    if (isset($user_data['permissions'])) {
                        $permission_ids = Permission::whereIn('name', $user_data['permissions'])->pluck('id');
                        $user->userPermissions()->attach($permission_ids);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
        }
    }
}
