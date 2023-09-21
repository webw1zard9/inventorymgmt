<tr>

    <td style="">

        <h5 class="text-muted" style="margin-bottom: 5px">{{ $category_name }}{{ ($brand_name?": ".$brand_name:"") }}</h5>
        <h4 style="margin: 0px 0px 5px 0px;">{{ $sold_as_name }}</h4>
        <span style="white-space: nowrap">
        <span><strong>SKU:</strong> {{ $batch->ref_number }}</span>
        @can('batches.show.cost')
        <span> | <strong>Cost:</strong> {{ display_currency($unit_cost) }}</span>
        @endcan
        <span> | <strong>Price:</strong> {{ display_currency($price) }}</span>
        </span>

    </td>
    <td>
        @if($batch->track_inventory)
        <h5 class="{{ (!$available_qty?"text-danger":"") }}">{{ $available_qty }} {{ $batch->uom }}</h5>
        @else
            <i>Unlimited</i>
        @endif
    </td>
    <td>
        <h5>
            <div wire:loading.remove>
                @if($qty_on_order)
                    <strong class="text-success">{{ $qty_on_order }} {{ $batch->uom }}</strong>
                @else
                    --
                @endif
            </div>
            <div wire:loading wire:target="addToOrder">
                <x-loading class="la-sm" />
            </div>
        </h5>
    </td>

    <td>
        <div class="input-group mb-2">
            <input wire:model.defer="quantity" wire:target="addToOrder" wire:loading.attr="disabled" type="text" class="form-control" style="width: 50px" />
            <span class="input-group-addon">{{ $batch->uom }}</span>
        </div>
        @error('quantity')<span class="text-danger">{{ $message }}</span>@enderror
    </td>
    <td>
        <div class="input-group mb-2">
            <span class="input-group-addon">$</span>
            <input wire:model.defer="price" wire:target="addToOrder" wire:loading.attr="disabled" type="text" class="form-control" style="width: 75px" />
        </div>

        @error('price')<span class="text-danger">{{ $message }}</span>@enderror
        @error('add-batch-error')<span class="text-danger">{{ $message }}</span>@enderror
    </td>
    <td>
        <div style="white-space: nowrap">
        <button wire:click="addToOrder" wire:loading.attr="disabled" class="btn btn-primary mr-2 prevent_double_clicks">Add</button>
        @if($batch->track_inventory)
        <button wire:click="addToOrder({{ $available_qty }})" wire:loading.attr="disabled" class="btn btn-primary prevent_double_clicks">Add All</button>
        @endif
        </div>
    </td>
</tr>