<?php

namespace Database\Seeders;

use App\Permission;
use App\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $perm_name_slug = [

            'Users List' => 'users.index',
            'Users View' => 'users.view',
            'Users Crate' => 'users.create',
            'Users Edit' => 'users.edit',

            'Vendors List' => 'users.index.vendor',
            'Vendors View' => 'users.view.vendor',
            'Vendors Create' => 'users.create.vendor',
            'Vendors Edit' => 'users.edit.vendor',
            'Vendors Delete' => 'users.delete.vendor',

            'Customers List' => 'users.index.customer',
            'Customers View' => 'users.view.customer',
            'Customers Create' => 'users.create.customer',
            'Customers Edit' => 'users.edit.customer',
            'Customers Delete' => 'users.delete.customer',

            'Sales Rep List' => 'users.index.salesrep',
            'Sales Rep View' => 'users.view.salesrep',
            'Sales Rep Create' => 'users.create.salesrep',
            'Sales Rep Edit' => 'users.edit.salesrep',
            'Sales Rep Delete' => 'users.delete.salesrep',

            'Purchase Orders List' => 'po.index',
            'Purchase Orders Show' => 'po.show',
            'Purchase Orders Create' => 'po.create',

            'Sale Orders Index' => 'so.index',
            'Sale Orders Show' => 'so.show',
            'Sale Orders Create' => 'so.create',

            'Batches List' => 'batches.index',
            'Batches Show' => 'batches.show',
            'Batches Edit' => 'batches.edit',
            'Batches Sell' => 'batches.sell',
            'Batches Reconcile' => 'batches.reconcile',
            'Batches Transfer' => 'batches.transfer',
            'Batches Show Cost' => 'batches.show.cost',
            'Batches Show Vendor' => 'batches.show.vendor',
            'Batches Show Sold' => 'batches.show.sold',
            'Batches Allocate' => 'batches.allocate',
            'Batches Approved Allocation' => 'batches.approve.allocate',

            //  'Pre-Pack Logs View' => 'prepacklogs.show',

            'Locations List' => 'locations.index',

            'Sauce List' => 'users.index.sauce',
            'Sauce View' => 'users.view.sauce',
            'Sauce Create' => 'users.create.sauce',
            'Sauce Edit' => 'users.edit.sauce',
            'Sauce Delete' => 'users.delete.sauce',

            'Location Managers List' => 'users.index.locationmanager',
            'Location Managers View' => 'users.view.locationmanager',
            'Location Managers Create' => 'users.create.locationmanager',
            'Location Managers Edit' => 'users.edit.locationmanager',
            'Location Managers Delete' => 'users.delete.locationmanager',

            'Accounting' => 'accounting',
            'Accounting Sales Summary' => 'accounting.sales.summary',
            'Accounting Payables' => 'accounting.payables',
            'Accounting Receivables' => 'accounting.receivables',
            'Accounting P&L' => 'accounting.profitloss',
            'Accounting Daily Close Out' => 'accounting.dailycloseout',
            'Accounting Inventory Loss' => 'accounting.inventoryloss',

            'Activity Log' => 'activitylog.index',

            "Dashboard - Revenue Summary" => "dashboard.revenue_summary",
            "Dashboard - Revenue Month Over Month" => "dashboard.revenue_month_o_month",
            "Dashboard - Revenue Quarter Over Quarter" => "dashboard.revenue_qtr_o_qtr",
            "Dashboard - Top Products by Category" => "dashboard.top_products_by_category",
            "Dashboard - Revenue by Category" => "dashboard.revenue_by_category",
            "Dashboard - Sales Rep Revenue by Category" => "dashboard.sales_rep_revenue_by_category",
            "Dashboard - Inventory Value By Location" => "dashboard.inventory_location",
            "Dashboard - Inventory Value By Vendor" => "dashboard.inventory_vendor",
            "Brands List" => "brands.index",
            "Categories list" => "categories.index",
            "Sale Orders - Reverse Delivered" => "so.reverse_delivered",
            "Manage Roles" => "manage.roles",
        ];

        //delete non-existing permissions
        Permission::whereNotIn('name', array_values($perm_name_slug))->delete();

        foreach ($perm_name_slug as $permission_name => $permission_slug) {
            try {
                $permission = Permission::firstOrCreate([
                    'description' => $permission_name,
                    'name' => $permission_slug,
                ]);
            } catch (Exception $e) {
                dump($e->getMessage());
                dump('error...'.$permission_slug);
            }

            //location manager only
            if (in_array($permission_slug, [
                'accounting.sales.summary',

                'po.index',
                'po.show',
                'po.create',

                'so.index',
                'so.show',
                'so.create',

                'batches.index',
                'batches.show',
                'batches.edit',
                'batches.transfer',
                'batches.sell',
                'batches.allocate',
                'batches.approve-allocate',

                'users.index.vendor',
                'users.view.vendor',
                'users.create.vendor',
                'users.edit.vendor',
                'users.delete.vendor',

                'users.index.salesrep',
                'users.view.salesrep',
                'users.create.salesrep',
                'users.edit.salesrep',
                'users.delete.salesrep',

                'users.index.sauce',
                'users.view.sauce',
                'users.create.sauce',
                'users.edit.sauce',
                'users.delete.sauce',

            ])) {
                $manager_role = Role::where('name', 'locationmanager')->first();
                $permission->roles()->attach($manager_role->id);
            }

            //sales rep
            if (in_array($permission_slug, [
                'so.index',
                'so.show',
                'so.create',

                'users.index.customer',
                'users.view.customer',
                'users.create.customer',
                'users.edit.customer',
                'users.delete.customer',

                'batches.index',
                'batches.show',
                'batches.sell',
            ])) {
                $sales_rep_role = Role::where('name', 'salesrep')->first();
                $permission->roles()->attach($sales_rep_role->id);
            }

            // sauce
            if (in_array($permission_slug, [
                'so.index',
                'so.show',

            ])) {
                $sales_rep_role = Role::where('name', 'sauce')->first();
                $permission->roles()->attach($sales_rep_role->id);
            }
        }
    }
}
