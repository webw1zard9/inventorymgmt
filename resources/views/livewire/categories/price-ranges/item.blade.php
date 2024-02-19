
{{ Form::open(['class'=>'item_update_form ', 'wire:submit.prevent="update"']) }}
<div class="row">
    <div class="col-4">
        <input wire:model="category_price_range.name" type="text"  class="form-control" placeholder="Label" />
        <div class="text-danger">@error('category_price_range.name') {{ $message }} @enderror</div>
    </div>
    <div class="col-3">

        <div class="input-group mb-2">
            <span class="input-group-addon">$</span>
            <input wire:model="category_price_range.min_price" type="number" step="0.01" class="form-control" autocomplete="off" placeholder="Min" />
        </div>
        <div class="text-danger">@error('category_price_range.min_price') {{ $message }} @enderror</div>

    </div>
    <div class="col-3">
        <div class="input-group mb-2">
            <span class="input-group-addon">$</span>
            <input wire:model="category_price_range.max_price" type="number" step="0.01" class="form-control" autocomplete="off" placeholder="Max" />
        </div>
        <div class="text-danger">@error('category_price_range.max_price') {{ $message }} @enderror</div>
    </div>
    <div class="col-2">
        <div class="d-flex justify-content-start">
            <button type="submit" class="btn btn-primary">Save</button>

            @if($category_price_range->id)
            <a href="#" wire:click.prevent="removeItem" type="button" wire:loading.attr="disabled" class="btn btn-danger waves-effect waves-light ml-2" >
                <div wire:loading wire:target="removeItem" wire:loading.class="d-flex justify-content-center" class="mt-1">
                    <x-loading class="la-sm"/>
                </div>

                <div wire:loading.remove wire:target="removeItem">
                    <i class=" mdi mdi-delete-forever"></i>
                </div>
            </a>
            @endif

        </div>

    </div>

</div>
{{ Form::close() }}
