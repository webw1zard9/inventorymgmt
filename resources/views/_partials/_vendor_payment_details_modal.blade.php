<div class="modal fade vendor-payment-{{ $transaction->id }}" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="mySmallModalLabel">
                    Txn# {{ $transaction->id }} -
                @if($transaction->amount)
                        Payment: {{ display_currency($transaction->amount) }}
                    @else
                        Vendor Credit Applied
                    @endif

                </h4>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Txn #</th>
                        <th>Order#</th>
                        <th>Amount</th>
                        <th>Type</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($transaction->children as $child_transaction)
                    <tr>
                        <td>{{ $child_transaction->id }}</td>
                        <td>
                            @if($child_transaction->purchase_order)
                            <a href="{{ route('purchase-orders.show', $child_transaction->purchase_order) }}">{{ $child_transaction->purchase_order->ref_number }}</a>
                            @else
                            Vendor Credit
                            @endif
                        </td>
                        <td>{{ display_currency($child_transaction->amount) }}</td>
                        <td>{{ ucfirst($child_transaction->type) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <th colspan="2"></th>
                    <th colspan="3">{{ display_currency($transaction->children->sum('amount')) }}</th>
                    </tfoot>
                </table>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>