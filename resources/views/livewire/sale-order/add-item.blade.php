<div>

    <div class="modal-body">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <div class="row">
            <div class="col-10 col-md-8 col-lg-6" wire:ignore>
{{--                <div class="form-group">--}}
{{--                    <label for="search_array">Search</label>--}}
                    <select class="form-control" id="search_keywords" multiple="" data-role="tagsinput" style="display: none;" placeholder="Search..."></select>
{{--                </div>--}}
            </div>
            <div class="col-2 col-md-2 col-lg-2">
                <x-loading wire:loading class="la-sm pt-2"/>
            </div>
        </div>

        <div class="table-responsive mt-2" wire:loading.class="opacity-50">

            <table id="batches-datatable" class="table table-sm">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Available</th>
                        <th>Ordered</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                @foreach($batches as $batch)

                    @livewire('sale-order.add-item-batch', ['batch'=>$batch, 'sale_order'=>$sale_order], key(Str::random()))

                @endforeach

                </tbody>

            </table>

            @if($batches)
                <div wire:loading.class="invisible">
                    {{ $batches->links() }}
                    <div>Showing {{ $batches->firstItem() }} to {{ $batches->lastItem() }} of {{ $batches->total() }}</div>
                </div>
            @endif

        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {

                $('#search_keywords').change(function() {
                    @this.set('search', $(this).val());
                })

                $('#add-items').on('hidden.bs.modal', function () {

                    $('#search_keywords').empty();
                    $('.bootstrap-tagsinput').find('.tag').remove();

                    Livewire.emit('addItemModalClosed');
                })

            });

        </script>

    </div>

</div>