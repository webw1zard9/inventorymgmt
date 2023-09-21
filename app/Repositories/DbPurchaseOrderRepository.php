<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/13/17
 * Time: 12:46
 */

namespace App\Repositories;

use App\Batch;
use App\Category;
use App\Events\POCreated;
use App\License;
use App\Location;
use App\Order;
use App\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DbPurchaseOrderRepository extends DbOrderRepository implements PurchaseOrderRepositoryInterface
{
    protected $order_class = PurchaseOrder::class;

    protected $order_type = 'purchase';

    protected $selected_category = null;

    protected $data;

    public function create($data)
    {
        $this->data = $data;

        $this->data['user_id'] = Auth::user()->id;
        $this->data['location_id'] = ($this->data['location_id'] ?? Auth::user()->active_locations->first()->id);
        $this->data['type'] = $this->order_type;
        $this->data['ref_number'] = null;
        $this->data['status'] = 'pending';
        $this->data['due_date'] = Carbon::parse($this->data['txn_date'])->addDays(0);
        $this->data['subtotal'] = 0;
        $this->data['tax'] = 0;
        $this->data['total'] = 0;
        $this->data['balance'] = 0;
        //dd($this->data);

        $purchase_order = app($this->order_class)->create($this->data);
        $purchase_order->set_order_id();

        $purchase_order->initJournal();

//        event(new POCreated($purchase_order));

        $activity_prop = collect([
            'PO' => $purchase_order->ref_number,
            'Vendor' => $purchase_order->vendor->name,
        ]);

        activity('purchase_order')
            ->causedBy(Auth::user())
            ->performedOn($purchase_order)
            ->withProperties($activity_prop)
            ->log('Created');
        //dd($this->data['_batches']);

//        $purchase_order->updateTotals();

        return $purchase_order;
    }

    private function empty_batch($batch)
    {
        return is_null($batch['quantity']);
    }

    private function set_selected_category($cat_id)
    {
        if (! $this->selected_category or $this->selected_category->id != $cat_id) {
            $this->selected_category = Category::find($cat_id);
        }

    }
}
