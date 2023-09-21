<dl class="row">
    <dt class="col-5 text-right">Status:</dt>
    <dd class="col-7"><span class="badge badge-{{ status_class($purchaseOrder->status) }}">{{ ucwords($purchaseOrder->status) }}</span></dd>

    <dt class="col-5 text-right">PO#</dt>
    <dd class="col-7">{{ $purchaseOrder->ref_number }}</dd>

    <dt class="col-5 text-right">Purchase Date:</dt>
    <dd class="col-7">{{ $purchaseOrder->txn_date->format('m/d/Y') }}</dd>

    <dt class="col-5 text-right">Buyer:</dt>
    <dd class="col-7">{{ $purchaseOrder->user->name }}</dd>

    <dt class="col-5 text-right">Location:</dt>
    <dd class="col-7">{!! ($purchaseOrder->location ? $purchaseOrder->location->name.($purchaseOrder->location->trashed()?" <span class='text-danger'>(deleted)</span>":"") : "--") !!}</dd>

    <dt class="col-5 text-right">Vendor:</dt>
    <dd class="col-7"><a href="{{ route('vendors.show', $purchaseOrder->vendor) }}">{{ $purchaseOrder->vendor->name }}</a></dd>

    <dt class="col-5 text-right">Total:</dt>
    <dd class="col-7">{{ display_currency($purchaseOrder->total) }}</dd>

{{--    <dt class="col-5 text-right">PO Balance:</dt>--}}
{{--    <dd class="col-7">{{ display_currency($purchaseOrder->balance) }}</dd>--}}

    <dt class="col-5 text-right">Current Payable:</dt>
    <dd class="col-7">{{ display_currency($purchaseOrder->total_owed) }}</dd>

    <dt class="col-5 text-right">Remaining Inventory Value:</dt>
    <dd class="col-7">{{ display_currency($purchaseOrder->remaining_inventory_value) }}</dd>

</dl>