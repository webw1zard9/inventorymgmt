<?php

namespace App\Http\Livewire\SaleOrder;

use App\Batch;
use App\SaleOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AddItem extends Component
{
    use WithPagination;

    public SaleOrder $sale_order;

    public $readyToLoad = false;
    protected $paginationTheme = 'bootstrap';
//    public $search = '';
    public $search = [];

    protected $listeners = [
        'loadBatches',
        'addItemModalClosed',
    ];

    public function mount(SaleOrder $sale_order)
    {
        $this->sale_order = $sale_order;
    }

    public function render()
    {
        return view('livewire.sale-order.add-item', [
            'batches' => ($this->readyToLoad ? $this->getBatches() : []),
//            'batches' => $this->getBatches(),
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSearchTest()
    {
        $this->resetPage();
    }

    public function loadBatches()
    {
        $this->readyToLoad = true;
    }

    public function getBatches()
    {
        if (Gate::denies('batches.sell')) {
            return redirect(route('sale-orders.show', $this->sale_order));
        }

        $batches = Batch::currentInventoryWithSaleOrderBatches($this->sale_order->location_id, $this->sale_order->id, $this->search)
                ->orderBy('location_batch_name')
                ->paginate(5);

        return $batches;
    }

    public function addItemModalClosed()
    {
        $this->search = [];
        $this->resetPage();
    }

}
