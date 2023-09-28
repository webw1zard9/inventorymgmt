<?php

namespace App;

use App\Presenters\PresentableTrait;
use App\Traits\ActivityLogTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Scottlaurent\Accounting\Models\Journal;
use Scottlaurent\Accounting\Models\JournalTransaction;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;
use Scottlaurent\Accounting\Services\Accounting;
use Spatie\Permission\Traits\HasRoles;

// implements HasRoleAndPermissionContract

class User extends Authenticatable
{
    use Notifiable, PresentableTrait, AccountingJournal, HasRoles, ActivityLogTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'details',
        'super_user',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'pin',
    ];

    protected $casts = [
        'first_order' => 'datetime',
        'last_order' => 'datetime',
        'details' => 'json',
    ];

    protected $with= ['only_active_locations'];

    protected $presenter = \App\Presenters\User::class;

    public function getBalanceAttribute()
    {
        if ($this->hasRole(['vendor', 'customer'])) {
            $multiplier = (in_array($this->journal->ledger->type, ['asset', 'expense']) ? -1 : 1);

            return ($this->journal->balance->getAmount() / 100) * $multiplier;
        } else {
            return 0;
        }
    }

    public function getVendorCreditBalanceAttribute()
    {
        $vendor_credit_txns = ChartOfAccount::VendorCredits()
            ->journal
            ->transactionsReferencingObjectQuery($this)
            ->get();

        if ($vendor_credit_txns->count() > 0) {
            $balance = $vendor_credit_txns->sum('credit') - $vendor_credit_txns->sum('debit');
        } else {
            $balance = 0;
        }

        return $balance / 100;
    }

    public function getAvailableBalanceAttribute()
    {
        return $this->balance * -1;
    }

    public function prepaidInventory()
    {
        $holding_account_txn = ChartOfAccount::PrepaidInventory()
            ->journal
            ->transactionsReferencingObjectQuery($this)
            ->get();

        if ($holding_account_txn->count() > 0) {
            $balance = $holding_account_txn->sum('credit') - $holding_account_txn->sum('debit');
        } else {
            $balance = 0;
        }

        return $balance / 100;
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getNameAttribute($value)
    {
        return trim($value);
    }

    /**
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * @param $value
     */
    public function setPinAttribute($value)
    {
        $this->attributes['pin'] = bcrypt($value);
    }

    /**
     * @param $value
     */
    public function setPhoneAttribute($value)
    {
        $phone = preg_replace('/\D+/', '', $value);
        $this->attributes['phone'] = ((! preg_match('/^\+1/', $phone) && ! is_null($phone)) ? '+1'.$phone : $phone);
    }

    /**
     * @param $value
     * @return string
     */
    public function getPhoneAttribute($value)
    {
        $phone = substr($value, 2);

        if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $phone, $matches)) {
            $result = $matches[1].'-'.$matches[2].'-'.$matches[3];
            return $result;
        }
        return null;
    }

    public function hasLocation()
    {
        return $this->current_location->exists;
    }

    public function hasMultiLocations()
    {
        return ($this->active_locations->count() > 1);
    }

    public function setCurrentLocationAttribute($value)
    {
        Session::put('current_location', $value);
    }

    public function getCurrentLocationAttribute()
    {
        if (is_null($loc = Session::get('current_location'))) {
            $loc = new Location();
        }

        return $loc;
    }

    public function setOnlyMyLocationsAttribute($value)
    {
        Session::put('only_my_locations', $value);
    }

    public function getOnlyMyLocationsAttribute()
    {
        if (is_null($locs = Session::get('only_my_locations'))) {
            $locs = collect();
        }

        return $locs;
    }

    public function setActiveLocationsAttribute($value)
    {
        Session::put('active_locations', $value);
    }

    public function getActiveLocationsAttribute()
    {
        if (is_null($locs = Session::get('active_locations'))) {
            $locs = collect();
        }

        return $locs;
    }

    public function setMyLocationsAttribute($value)
    {
        Session::put('my_locations', $value);
    }

    public function getMyLocationsAttribute()
    {
        if (is_null($locs = Session::get('my_locations'))) {
            $locs = collect();
        }

        return $locs;
    }

    public function getDisplayNameAttribute($value)
    {
        $name = '';
        if (! empty($this->details['business_name'])) {
            $name .= $this->name.'<br>';
            $name .= '<small><i>'.$this->details['business_name'].'</i></small>';

            return $name;
        } else {
            return $this->name;
        }
    }

    public function getCanEditSuperUserAttribute()
    {
        if (! $this->super_user) {
            return true;
        }
        if ($this->super_user && $this->id == Auth::user()->id) {
            return true;
        } else {
            return false;
        }
    }

    public function isSuperAdmin()
    {
        return $this->super_user;
    }

    /**
     * @param $value
     * @return float
     */
    public function getOutstandingBalanceAttribute($value)
    {
        return $value / 100;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function batch_pickups()
    {
        return $this->hasMany(BatchPickup::class)->with('batch');
    }

    public function sales_commission_details()
    {
        return $this->hasMany(SalesCommissionDetail::class, 'sales_rep_id');
    }

    public function cultivated_batches()
    {
        return $this->hasMany(Batch::class, 'cultivator_id');
    }

    public function tested_batches()
    {
        return $this->hasMany(Batch::class, 'testing_laboratory_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchase_orders()
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id')->with('journal');
    }

    public function purchase_orders_with_balance()
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id')
            ->withWhereHas('journal', function ($q) {
                $q->where('accounting_journals.balance', '!=', 0);
            })
            ->with('transactions')
            ->orderBy('txn_date');
    }

    public function po_transactions()
    {
        return $this->hasManyThrough(OrderTransaction::class, PurchaseOrder::class,  'vendor_id', 'purchase_order_id');
    }

    public function vendor_transactions()
    {
        return $this->hasMany(OrderTransaction::class, 'vendor_id');
    }

    public function vendor_credit_transactions()
    {
        return $this->hasManyThrough(OrderTransaction::class, JournalTransaction::class,  'ref_class_id', 'acct_journal_txn_fid', 'id', 'acct_journal_txn_pid')
            ->where('ref_class', get_class($this));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sale_orders()
    {
        return $this->hasMany(SaleOrder::class, 'customer_id');
    }

    public function first_sale_order()
    {
        return $this->hasOne(SaleOrder::class, 'customer_id')->orderBy('txn_date');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales_rep_orders()
    {
        return $this->hasMany(SaleOrder::class, 'sales_rep_id');
    }

    public function created_sales_commissions()
    {
        return $this->hasMany(SalesCommission::class, 'user_id');
    }

    public function my_sales_commissions()
    {
        return $this->hasMany(SalesCommission::class, 'sales_rep_id');
    }

    public function license_types()
    {
        return $this->belongsToMany(LicenseType::class, 'license_type_user', 'user_id', 'license_type_id')->withTimestamps();
    }

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function sales_rep_customers()
    {
        return $this->belongsToMany(User::class, 'salesrep_customer', 'salesrep_id', 'customer_id')->withTimestamps();
    }

    public function customer_sales_reps()
    {
        return $this->belongsToMany(User::class, 'salesrep_customer', 'customer_id', 'salesrep_id')->withTimestamps();
    }

    public function cultivation_license()
    {
        return $this->licenses()->where('license_type_id', 1);
    }

    public function location()
    {
        return $this->belongsToMany(Location::class)->withTimestamps()->withTrashed()->first();
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class)->withTimestamps()->withTrashed();
    }

    public function only_active_locations()
    {
        return $this->belongsToMany(Location::class)->withTimestamps();
    }

    public function scopeFilters($query, $filters)
    {
        return $filters->apply($query);
    }

    public function scopeSauce($query)
    {
        $query->whereHas('roles', function ($q) {
            $q->where('name', 'sauce');
        });
        if (Auth::check() && Auth::user()->hasLocation()) {
            $query->whereHas('locations', function ($q) {
                $q->where('location_id', Auth::user()->current_location->id);
            });
        }

        return $query;
    }

    public function scopeLocationManagers($query)
    {
        $query->whereHas('roles', function ($q) {
            $q->where('name', 'locationmanager');
        });

        if (Auth::check() && Auth::user()->hasLocation()) {
            $query->whereHas('locations', function ($q) {
                $q->where('location_id', Auth::user()->current_location->id);
            });
        }

        return $query;
    }

    public function scopeSalesReps($query)
    {
        $query->whereHas('roles', function ($q) {
            $q->where('name', 'salesrep');
        });

        if (Auth::check() && Auth::user()->hasLocation()) {
            $query->whereHas('locations', function ($q) {
                $q->where('location_id', Auth::user()->current_location->id);
            });
        }

        return $query;
    }

    public function scopeVendors($query, $vendor_id=null)
    {
        $q_builder = $query->whereHas('roles', function ($q) {
            $q->where('name', 'vendor');
        });

        if($vendor_id) $q_builder->where('id',$vendor_id);

        return $q_builder;
    }

    public function scopeCustomers($query)
    {
        $query->where('users.active',1)->whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        });

        if (Auth::check() && Auth::user()->hasRole('salesrep')) {
            $query->whereIn('users.id', Auth::user()->sales_rep_customers->pluck('id'));
        }

        return $query;
    }

    public function scopeSalesrep($query)
    {

        $query->whereHas('roles', function ($q) {
            $q->where('name', 'salesrep');
        });

        if (Auth::check() && Auth::user()->hasLocation()) {
            $query->whereHas('locations', function ($q) {
                $q->where('location_id', Auth::user()->current_location->id);
            });
        }

        return $query;
    }

    public function scopeActive($query)
    {
        return $query->where('users.active', 1);
    }

    public function scopeTestingLaboratory($query)
    {
        return $query->whereHas('license_types', function ($q) {
            $q->where('name', 'Testing Laboratory');
        })->orderBy('name');
    }

    public function scopeWithAndWhereHas($query, $relation, $constraint)
    {
        return $query->whereHas($relation, $constraint)
            ->with([$relation => $constraint]);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeWithOutstandingBalance($query)
    {
        return $query->select('users.*', \DB::raw('sum(orders.balance) as outstanding_balance'))
            ->join('orders', 'users.id', '=', 'orders.customer_id')
            ->with(['sale_orders' => function ($query) {
                $query->where('balance', '!=', 0)
                    ->with('transactions');
            }])
            ->groupBy('users.id')
            ->orderBy('outstanding_balance', 'desc');
    }

    public function scopePayablesSummary($query)
    {

        $po_accounting_journals_subquery = PurchaseOrder::select([
            'orders.vendor_id',
            DB::raw("SUM(accounting_journals.balance / 100) as 'po_balances'")
        ])
            ->join('accounting_journals', 'accounting_journals.morphed_id', '=', 'orders.id')
            ->where('accounting_journals.morphed_type', '=', 'App\\PurchaseOrder')
            ->groupBy('orders.vendor_id');

        $inventory_costs = BatchLocationAggregate::inventory_values();

        $sold_cost = PurchaseOrder::select([
            'orders.vendor_id',
            DB::raw('locations.name AS sold_location_name'),
            DB::raw('SUM(order_details.units_fulfilled * order_details.unit_cost / 100) AS sold_cost')
        ])
            ->join('batches', 'orders.id', '=', 'batches.purchase_order_id')
            ->join('order_details', 'batches.id', '=', 'order_details.batch_id')
            ->join('orders AS sale_orders', 'order_details.sale_order_id', '=', 'sale_orders.id')
            ->join('locations', 'sale_orders.location_id', '=', 'locations.id')
            ->where('sale_orders.status', '=', 'delivered')
            ->groupBy('orders.vendor_id')
            ->groupBy('locations.name');

        $current_reconciled_cost = TransferLog::select([
            'purchase_order.vendor_id',
            DB::raw('IFNULL(locations.name, "Nest") AS current_reconciled_location_name'),
            DB::raw('SUM(transfer_logs.inventory_loss / 100) AS current_reconciled_cost')
        ])
            ->leftJoin('locations', 'transfer_logs.location_id', '=', 'locations.id')
            ->join('batches', 'transfer_logs.batch_id', '=', 'batches.id')
            ->join('orders AS purchase_order', 'batches.purchase_order_id', '=', 'purchase_order.id')
            ->join('accounting_journals', 'accounting_journals.morphed_id', '=', 'purchase_order.id')
            ->where('accounting_journals.morphed_type', '=', 'App\\PurchaseOrder')
            ->where('accounting_journals.balance', '!=', '0')
            ->groupBy('purchase_order.vendor_id')
            ->groupBy('locations.name')
            ->having(DB::raw('SUM(transfer_logs.inventory_loss / 100)'), '!=', 0)
            ->orderBy('purchase_order.vendor_id')
            ->orderBy('locations.name');

        $reconciled_cost = TransferLog::select([
            'purchase_order.vendor_id',
            DB::raw('IFNULL(locations.name, "Nest") AS reconciled_location_name'),
			DB::raw('SUM(transfer_logs.inventory_loss / 100) AS reconciled_cost')
        ])
            ->leftJoin('locations', 'transfer_logs.location_id', '=', 'locations.id')
            ->join('batches', 'transfer_logs.batch_id', '=', 'batches.id')
            ->join('orders AS purchase_order', 'batches.purchase_order_id', '=', 'purchase_order.id')
            ->groupBy('purchase_order.vendor_id')
            ->groupBy('locations.name')
            ->having(DB::raw('SUM(transfer_logs.inventory_loss / 100)'), '!=', 0)
            ->orderBy('purchase_order.vendor_id')
            ->orderBy('locations.name');

        $paid_cost = PurchaseOrder::select([
            'orders.vendor_id',
            DB::raw('IFNULL(locations.name, "Nest") AS paid_location_name'),
            DB::raw('SUM(order_transactions.amount / 100) AS paid_cost')
        ])
            ->join('order_transactions', 'orders.id', '=', 'order_transactions.purchase_order_id')
            ->leftJoin('locations', 'order_transactions.location_id', '=', 'locations.id')
            ->groupBy('orders.vendor_id')
            ->groupBy('locations.name');

        $query->select([
            'users.id',
            'users.name',
            'po_balances',
            'inventory_location_name',
            'onhand_cost',
            'available_cost',
            'pending_cost',
            'fulfilled_cost',
            'sold_location_name',
            'sold_cost',
            'paid_location_name',
            'paid_cost',
            'reconciled_location_name',
            'reconciled_cost',
            'current_reconciled_location_name',
            'current_reconciled_cost'
        ])
            ->joinSub($po_accounting_journals_subquery, 'accounting_journals', function($join) {
                $join->on('users.id', '=', 'accounting_journals.vendor_id');
            })
            ->leftJoinSub($inventory_costs, 'inventory_costs', function($join) {
                $join->on('users.id', '=', 'inventory_costs.vendor_id');
            })
            ->leftJoinSub($sold_cost, 'sold_sale_orders', function($join) {
                $join->on('users.id', '=', 'sold_sale_orders.vendor_id');
            })
            ->leftJoinSub($paid_cost, 'paid_sale_orders', function($join) {
                $join->on('users.id', '=', 'paid_sale_orders.vendor_id');
            })
            ->leftJoinSub($reconciled_cost, 'reconciled_cost', function($join) {
                $join->on('users.id', '=', 'reconciled_cost.vendor_id');
            })
            ->leftJoinSub($current_reconciled_cost, 'current_reconciled_cost', function($join) {
                $join->on('users.id', '=', 'current_reconciled_cost.vendor_id');
            })
            ->where(function($q) {
                $q->where(function ($q) {
                    $q->where('po_balances', '>', 0)
                        ->orWhere('onhand_cost', '>', 0);
                });
            })
            ->groupBy('users.id')
            ->groupBy('users.name')
            ->groupBy('inventory_location_name')
            ->groupBy('onhand_cost')
            ->groupBy('available_cost')
            ->groupBy('pending_cost')
            ->groupBy('fulfilled_cost')
            ->groupBy('sold_cost')
            ->groupBy('sold_location_name')
            ->groupBy('paid_cost')
            ->groupBy('paid_location_name')
            ->groupBy('reconciled_location_name')
            ->groupBy('reconciled_cost')
            ->groupBy('current_reconciled_location_name')
            ->groupBy('current_reconciled_cost')
            ->orderBy('name', 'asc')
            ;

//            dd($inventory_cost->get());
//        $query->

        return $query;

    }

    public function scopePayables($query, $purchaseOrder = null, $vendor = null)
    {
        $query->with([
            'purchase_orders' => function ($q) use ($purchaseOrder) {
                $q->orderBy('txn_date', 'desc');
                if ($purchaseOrder && $purchaseOrder->exists) {
                    $q->where('orders.id', $purchaseOrder->id);
                } else {
                    $q->where('orders.balance', '!=', 0);
                }
            },
            'purchase_orders.journal',
            'purchase_orders.transactions' => function ($q) {
//                $q->where('payment_method', 'Cash');
            },
            'purchase_orders.transactions.user',
            'purchase_orders.transactions.location',
            'purchase_orders.batches.locations',
            'purchase_orders.batches.allocated_inventory',
            'purchase_orders.batches.allocated_and_sold_inventory',
            'purchase_orders.batches.transfer_logs.location',
            'purchase_orders.batches.transfer_logs.user',
            'purchase_orders.batches.order_details.sale_order.location',
            'purchase_orders.batches.order_details.sale_order.customer',
        ])
            ->whereHas('purchase_orders', function ($q) use ($purchaseOrder) {
                if ($purchaseOrder && $purchaseOrder->exists) {
                    $q->where('orders.id', $purchaseOrder->id);
                } else {
                    $q->where('orders.balance', '>', 0);
                }
            })
            ->orderBy('users.name');

        if ($vendor && $vendor->exists) {
            $query->where('id', $vendor->id);
        }

        return $query;
    }

    public function scopePaidTransactions($query, $transactionPaidFilters)
    {
//        return $query->where('vendor_id', $this->id)
//            ->with([
//                'user',
//                'signature',
//                'location',
//                'purchase_order',
//            ])
//            ->whereIn('order_transactions.type', ['payment','paid','refund','credit'])
////                    ->whereNotNull('order_transactions.purchase_order_id')
//            ->where('order_transactions.payment_method', '!=', 'Vendor Credit')
//            ->orderBy('order_transactions.created_at', 'desc');

        return $query
            ->withWhereHas('vendor_transactions', function($q) use ($transactionPaidFilters) {
                $q->filters($transactionPaidFilters);
                $q->with([
                    'user',
                    'signature',
                    'location',
                    'purchase_order',
                    'children'
                ]);
                $q->whereIn('order_transactions.type', ['payment','paid','refund','credit'])
                    ->orderBy('order_transactions.created_at', 'desc');
            });
    }

    public function scopeCreditTransactions($query, $transactionPaidFilters)
    {
        return $query
            ->withWhereHas('vendor_credit_transactions', function($q) use ($transactionPaidFilters) {
                $q->filters($transactionPaidFilters);
                $q->with([
                    'user',
                    'signature',
                    'location',
                    'purchase_order',
                ])->orderBy('order_transactions.created_at', 'desc');
            });
    }

    public function all_customers_ordered_last()
    {
        return static::customers()
            ->select('users.id', 'users.name',
                DB::raw('min(orders.txn_date) as first_order'),
                DB::raw('max(orders.txn_date) as last_order'),
                DB::raw('count(orders.id) as number_of_orders'),
                DB::raw('sum(orders.subtotal/100) as total_order_value'),
                DB::raw('datediff(now(), max(orders.txn_date)) AS `days_last_order`')
            )
            ->where('users.active', 1)
            ->join('orders', 'users.id', '=', 'orders.customer_id')
            ->groupBy('users.id')
            ->orderBy('days_last_order', 'desc')
            ->get();
    }

    public function hasSalesCommForPeriod($start_date, $end_date)
    {
        $sales_commissions = static::my_sales_commissions()
            ->where('period_start', $start_date->toDateString())
            ->where('period_end', $end_date->toDateString());

        return $sales_commissions->count() ? true : false;
    }

    public function scopeAppSearch($query, $q)
    {
        return $query->where('users.name', 'like', '%'.$q.'%');
    }

    public function scopeWithVendorBalance($query)
    {
        return $query->select('users.*', \DB::raw('sum(orders.balance) as outstanding_balance'))
            ->leftjoin('orders', 'users.id', '=', 'orders.vendor_id')
            ->with(['purchase_orders' => function ($query) {
                $query->where('balance', '!=', 0)
                    ->with('transactions')
                    ->orderBy('txn_date', 'desc')
                    ->orderBy('ref_number', 'desc');
            }])
            ->where('orders.balance', '!=', 0)
            ->groupBy('users.id')
            ->orderBy('outstanding_balance', 'desc');
    }

    public function issue_vendor_credit($payment, $txn_date=null, $payment_method, $ref_number, $memo, $txn_fee, $parent_id=null, $type=null, $location=null)
    {
        // add credit to vendor credit journal
        $journal_transaction = ChartOfAccount::VendorCredits()->journal->{($payment>0?"credit":"debit")}(abs($payment) * 100);
        $journal_transaction->referencesObject($this);
        $journal_transaction->refresh();

        $this->order_txn_payment($payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $parent_id, $type, $location, $journal_transaction, null);

    }

    //vendor payment method
    public function vendor_payment($credit_account, $debit_account, $payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $parent_id=null, $type=null, $location=null)
    {
        // this represents payment in cash to satisy that AR entry
        $payment_money = convert_to_cents($payment, true, true);
        $transaction_group = Accounting::newDoubleEntryTransactionGroup();
        $transaction_group->addTransaction($credit_account, ($payment > 0 ? 'credit' : 'debit'), $payment_money,null, $location);
        $transaction_group->addTransaction($debit_account, ($payment > 0 ? 'debit' : 'credit'), $payment_money, null, $this);
        $transaction_group_id = $transaction_group->commit();

        if ($payment > 0) {
            $journal_transaction = JournalTransaction::where('transaction_group', $transaction_group_id)->whereNull('debit')->first();
        } else {
            $journal_transaction = JournalTransaction::where('transaction_group', $transaction_group_id)->whereNull('credit')->first();
        }
        $this->journal->resetCurrentBalances();

        return $this->order_txn_payment($payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $parent_id, $type, $location, $journal_transaction, $this->id);
    }

    //customer payment method
    public function payment($cash_account, $payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $parent_id=null, $type=null, $location=null)
    {
        // this represents payment in cash to satisy that AR entry
        $payment_money = convert_to_cents($payment, true, true);
        $transaction_group = Accounting::newDoubleEntryTransactionGroup();
        $transaction_group->addTransaction($this->journal, ($payment > 0 ? 'credit' : 'debit'), $payment_money, null);
        $transaction_group->addTransaction($cash_account->journal, ($payment > 0 ? 'debit' : 'credit'), $payment_money, null, $location);
        $transaction_group_id = $transaction_group->commit();

        if ($payment > 0) {
            $journal_transaction = JournalTransaction::where('transaction_group', $transaction_group_id)->whereNull('debit')->first();
        } else {
            $journal_transaction = JournalTransaction::where('transaction_group', $transaction_group_id)->whereNull('credit')->first();
        }
        $this->journal->resetCurrentBalances();

        $this->order_txn_payment($payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $parent_id, $type, $location, $journal_transaction);
    }

    public function order_txn_payment($payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $parent_id=null, $type=null, $location=null,$journal_txn=null, $vendor_id=null)
    {
        $txn = new OrderTransaction();
        $txn->parent_id = $parent_id??null;
        $txn->vendor_id = $vendor_id??null;
        $txn->user_id = (Auth::user() ? Auth::user()->id : User::where('name','Admin')->first()->id);
        $txn->location_id = ($location ? $location->id : Auth::user()->current_location->id);
        $txn->amount = $payment;
        $txn->txn_fee = $txn_fee;
        $txn->type = ($type ?? ($payment < 0 ? 'refund' : 'payment'));
        $txn->txn_date = $txn_date??Carbon::now();
        $txn->payment_method = $payment_method;
        $txn->ref_number = $ref_number;
        $txn->memo = $memo;
        $txn->acct_journal_txn_fid = $journal_txn->acct_journal_txn_pid??null;
        $txn->save();

        return $txn->id;
    }

    public function canAnyGate(...$permissions)
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if (Gate::allows($permission, $this)) {
                return true;
            }
        }

        return false;
    }

    public function userPermissions()
    {
        return $this->permissions();
    }

    public function hasPermission($param)
    {
        return $this->hasPermissionTo($param);
    }

    public function level()
    {
        return ($role = $this->roles->sortByDesc('level')->pluck('level')->first()) ? $role : 0;
    }

    public function aggregatePayablesSummary($vendors)
    {

        $payable_data = [];

        $vendors->map(function($vendor) use (&$payable_data) {

            $payable_data[$vendor->id]['name'] = $vendor->name;
            $payable_data[$vendor->id]['po_balance'] = (float)$vendor->po_balances;

            if(!$vendor->onhand_cost) {
                $payable_data[$vendor->id]['onhand_costs'] = null;
            } else {
                $payable_data[$vendor->id]['onhand_costs'][$vendor->inventory_location_name] = (float)$vendor->onhand_cost;
            }

            if(!$vendor->available_cost) {
                $payable_data[$vendor->id]['available_costs'] = null;
            } else {
                $payable_data[$vendor->id]['available_costs'][$vendor->inventory_location_name] = (float)$vendor->available_cost;
            }

            if(!$vendor->fulfilled_cost) {
                $payable_data[$vendor->id]['fulfilled_costs'] = null;
            } else {
                $payable_data[$vendor->id]['fulfilled_costs'][$vendor->inventory_location_name] = (float)$vendor->fulfilled_cost;
            }

            if(!$vendor->pending_cost) {
                $payable_data[$vendor->id]['pending_costs'] = null;
            } else {
                $payable_data[$vendor->id]['pending_costs'][$vendor->inventory_location_name] = (float)$vendor->pending_cost;
            }

            if(!$vendor->sold_cost) {
                $payable_data[$vendor->id]['sold_costs'] = null;
            } else {
                $payable_data[$vendor->id]['sold_costs'][$vendor->sold_location_name] = (float)$vendor->sold_cost;
            }

            if(!$vendor->paid_cost) {
                $payable_data[$vendor->id]['paid_costs'] = null;
            } else {
                $payable_data[$vendor->id]['paid_costs'][$vendor->paid_location_name] = (float)$vendor->paid_cost;
            }

            if(!$vendor->reconciled_cost) {
                $payable_data[$vendor->id]['reconciled_costs'] = null;
            } else {
                $payable_data[$vendor->id]['reconciled_costs'][$vendor->reconciled_location_name] = (float)$vendor->reconciled_cost;
            }

            if(!$vendor->current_reconciled_cost) {
                $payable_data[$vendor->id]['current_reconciled_costs'] = null;
            } else {
                $payable_data[$vendor->id]['current_reconciled_costs'][$vendor->current_reconciled_location_name] = (float)$vendor->current_reconciled_cost;
            }

        });

        $payable_data = collect($payable_data)->recursive();
//dd($payable_data);
        $payable_data->map(function($vendor_data) {

            $vendor_data->put('onhand_costs_total', 0);
            $vendor_data->put('available_costs_total', 0);
            $vendor_data->put('pending_costs_total', 0);
            $vendor_data->put('fulfilled_costs_total', 0);
            $vendor_data->put('sold_costs_total', 0);
            $vendor_data->put('paid_costs_total', 0);
            $vendor_data->put('reconciled_costs_total', 0);
            $vendor_data->put('current_reconciled_costs_total', 0);
            $vendor_data->put('total_cogs_total', 0);

            if($vendor_data['onhand_costs']) {
                $vendor_data['onhand_costs'] = $vendor_data['onhand_costs']->sortKeys();
                $vendor_data['onhand_costs_total'] = (float)bcadd($vendor_data['onhand_costs_total'], $vendor_data['onhand_costs']->sum(), 2);
            }

            if($vendor_data['available_costs']) {
                $vendor_data['available_costs'] = $vendor_data['available_costs']->sortKeys();
                $vendor_data['available_costs_total'] = (float)bcadd($vendor_data['available_costs_total'], $vendor_data['available_costs']->sum(), 2);
            }

            if($vendor_data['pending_costs']) {
                $vendor_data['pending_costs'] = $vendor_data['pending_costs']->sortKeys();
                $vendor_data['pending_costs_total'] = (float)bcadd($vendor_data['pending_costs_total'], $vendor_data['pending_costs']->sum(), 2);
            }

            if($vendor_data['fulfilled_costs']) {
                $vendor_data['fulfilled_costs'] = $vendor_data['fulfilled_costs']->sortKeys();
                $vendor_data['fulfilled_costs_total'] = (float)bcadd($vendor_data['fulfilled_costs_total'], $vendor_data['fulfilled_costs']->sum(), 2);
            }

            if(empty($vendor_data['total_cogs'])) $vendor_data['total_cogs']=collect();
            if(empty($vendor_data['payables'])) $vendor_data['payables']=collect();

            if($vendor_data['sold_costs']) {
                $vendor_data['sold_costs'] = $vendor_data['sold_costs']->sortKeys();
                $vendor_data['sold_costs_total'] = (float)bcadd($vendor_data['sold_costs_total'], $vendor_data['sold_costs']->sum(), 2);

                $vendor_data['sold_costs']->map(function($v, $k) use (&$vendor_data) {
                    if(empty($vendor_data['total_cogs'][$k])) {
                        $vendor_data['total_cogs']->put($k, $v);
                    } else {
                        $vendor_data['total_cogs'][$k] = (float)bcadd($vendor_data['total_cogs'][$k], $v, 2);
                    }

                    if(empty($vendor_data['payables'][$k])) {
                        $vendor_data['payables']->put($k, $v);
                    } else {
                        $vendor_data['payables'][$k] = (float)bcadd($vendor_data['payables'][$k], $v, 2);
                    }

                });
            }

            if($vendor_data['current_reconciled_costs']) {
                $vendor_data['current_reconciled_costs'] = $vendor_data['current_reconciled_costs']->sortKeys();
                $vendor_data['current_reconciled_costs_total'] = (float)bcadd($vendor_data['current_reconciled_costs_total'], $vendor_data['current_reconciled_costs']->sum(), 2);
            }

            if($vendor_data['reconciled_costs']) {
                $vendor_data['reconciled_costs'] = $vendor_data['reconciled_costs']->sortKeys();
                $vendor_data['reconciled_costs_total'] = (float)bcadd($vendor_data['reconciled_costs_total'], $vendor_data['reconciled_costs']->sum(), 2);

                $vendor_data['reconciled_costs']->map(function($v, $k) use (&$vendor_data) {
                    if(empty($vendor_data['total_cogs'][$k])) {
                        $vendor_data['total_cogs']->put($k, $v);
                    } else {
                        $vendor_data['total_cogs'][$k] = (float)bcadd($vendor_data['total_cogs'][$k], $v, 2);
                    }

                    if(empty($vendor_data['payables'][$k])) {
                        $vendor_data['payables']->put($k, (float)$v);
                    } else {
                        $vendor_data['payables'][$k] = (float)bcadd($vendor_data['payables'][$k], $v, 2);
                    }
                });

            }

            if(!empty($vendor_data['total_cogs'])) {
                $vendor_data['total_cogs_total'] = (float)bcadd($vendor_data['total_cogs_total'], $vendor_data['total_cogs']->sum(), 2);
            }

            if($vendor_data['paid_costs']) {
                $vendor_data['paid_costs'] = $vendor_data['paid_costs']->sortKeys();
                $vendor_data['paid_costs_total'] = (float)bcadd($vendor_data['paid_costs_total'], $vendor_data['paid_costs']->sum(), 2);

                $vendor_data['paid_costs']->map(function($v, $k) use (&$vendor_data) {
                    if(empty($vendor_data['payables'][$k])) {
                        $vendor_data['payables']->put($k, (float)($v * -1));
                    } else {
                        $vendor_data['payables'][$k] = (float)bcsub($vendor_data['payables'][$k], $v, 2);
                    }
                });
            }

            $vendor_data["total_payables2"] = $vendor_data['payables']->sum();

            $vendor_data["total_payables"] = (float)bcsub($vendor_data["po_balance"], bcadd($vendor_data["onhand_costs_total"], $vendor_data["fulfilled_costs_total"],2), 2);

            $vendor_data["payables_check"] = (float)bcsub($vendor_data["total_cogs_total"], $vendor_data["paid_costs_total"], 2);

            $vendor_data["payables_diff"] = (float)bcsub($vendor_data["total_payables"], $vendor_data["payables_check"], 2);

        });

        return $payable_data;
    }

    public function callMagic($method, $parameters)
    {
        if (Str::startsWith($method, 'is')) {
            return $this->hasRole(Str::snake(substr($method, 2)));
        }

        return parent::__call($method, $parameters);
    }

    public function __call($method, $parameters)
    {
        return $this->callMagic($method, $parameters);
    }
}
