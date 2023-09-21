<?php

namespace App\Http\Livewire\User\Vendor;

use App\Order;
use App\OrderTransaction;
use App\User;
use Illuminate\Http\Request;
use Livewire\Component;

class Payment extends Component
{
    public User $vendor;
    public $selected_vendor_id;
    public $vendors;
    public $locations;
    public $vendor_payable_data;
    public $purchase_orders=[428=>['amount_paid'=>285]];

    protected $rules = [
        'purchase_orders.*.amount_paid' => ['optional']
    ];

    public function mount(User $vendor, OrderTransaction $order_transaction)
    {


//        dump($vendor);
//        dump($order_transaction);
//        dd($request);
//        dd($order_transaction);
//        if($vendor) {
//            $this->selected_vendor_id = $vendor->id;
            $this->loadVendorData();

//        }
//dd($vendor);
//        $vendors = User::vendors()->active()->pluck('name', 'id');

//        $this->vendors = $vendors;
        $this->vendor = $vendor;
    }

    public function updatedSelectedVendorId()
    {
//        $this->loadVendorData();
//        $this->vendor = User::vendors($this->selected_vendor_id)->with('purchase_orders_with_balance')->first();
    }

    protected function loadVendorData()
    {


    }

    public function render()
    {
        return view('livewire.users.vendors.payment')->layoutData([
            'title'=> 'Vendor / Payments'
        ]);
    }
}
