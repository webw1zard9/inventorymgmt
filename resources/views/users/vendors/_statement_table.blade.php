
<table class="table" style="width: 100%">
    <thead>
    <tr>
        <th>Date</th>
        <th>Description</th>
        <th>Method</th>
        <th>Memo</th>
        <th>Amount</th>
        @if(!$pdf)<th>Picked Up</th>@endif
        <th>Balance</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ Carbon\Carbon::parse($from)->format('m/d/Y') }}</td>
        <td><em>Balance Forward</em></td>
        <td></td>
        <td></td>
        <td></td>
        @if(!$pdf)<td></td>@endif
        <td>{{ display_currency($starting_balance) }}</strong></td>
    </tr>

    @foreach($all_po_and_txns as $all_po_and_txn)

        @if($all_po_and_txn instanceof \App\OrderTransaction && $all_po_and_txn->amount==0)
            @continue
        @endif

        <tr>
            @if($all_po_and_txn instanceof \App\PurchaseOrder)

                <td style="white-space: nowrap;">{{ $all_po_and_txn->created_at->format('m/d/Y') }}</td>
                <td>
                    @if($pdf)
                        {{ $all_po_and_txn->ref_number }}
                    @else
                        <a href="{{ route('purchase-orders.show', $all_po_and_txn) }}">{{ $all_po_and_txn->ref_number }}</a>
                    @endif
                </td>
                <td>--</td>
                <td>--</td>
                <td>{{ display_currency($all_po_and_txn->total) }}</td>
                @if(!$pdf)<td></td>@endif

            @else
                <td style="white-space: nowrap;">{{ $all_po_and_txn->created_at->format('m/d/Y') }}</td>
                <td>{{ ucfirst($all_po_and_txn->type) }}</td>
                <td>{{ $all_po_and_txn->payment_method }}</td>
                <td style="white-space: nowrap;">{{ $all_po_and_txn->memo }}</td>
                <td style="white-space: nowrap;">

                    @if($all_po_and_txn->amount * -1 < 0)
                        <span class="text-danger">{{ display_currency($all_po_and_txn->amount * -1) }}</span>
                    @else
                        {{ display_currency($all_po_and_txn->amount * -1) }}
                    @endif
                </td>

                @if(!$pdf)
                <td>
                    @if($all_po_and_txn->signature)
                    <a href="{{ route('vendors.transactions.paid-signature', [$vendor, $all_po_and_txn]) }}"><i class="font-16 text-success  mdi mdi-check-circle"></i></a>
                    @endif
                </td>
                @endif

            @endif

            <td>{{ display_currency($all_po_and_txn->running_balance) }}</td>

        </tr>
    @endforeach
    </tbody>

</table>