@php
    $indent = 20;
@endphp

@foreach($child_batches as $batch)

    <tr>
        <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">

            @if($depth>0)
                <i class=" mdi mdi-subdirectory-arrow-right"></i>
            @endif
                {{ $batch->id }}
            @if($depth<0)

            @endif

        </td>
        <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">
            <a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a>
        </td>
        <td>{{ $batch->category->name }}</td>
        <td>{{ $batch->name }}</td>
        <td>{{ $batch->units_purchased }} {{ $batch->uom }}</td>
        <td>{{ $batch->inventory }} {{ $batch->uom }}</td>
        <td>
            @if($batch->order_details_cog->count())
                @foreach($batch->order_details_cog as $order_details_cog)
                    <div>{{ ($order_details_cog->sale_order?$order_details_cog->sale_order->customer->name:"--") }}<br>
                    {{ $order_details_cog->sale_order->id }}<br>
                    <a href="{{ route('sale-orders.show', $order_details_cog->sale_order) }}">{{ $order_details_cog->sale_order->ref_number }}</a>
                    </div>
                @endforeach
            @endif
        </td>
        <td>{{ $batch->order_details_cog->sum('units_accepted') }}</td>
        <td>{{ display_currency($batch->order_details_cog->sum('cost')) }}</td>
        <td>{{ display_currency($batch->order_details_cog->sum('revenue')) }}</td>
        <td>
            {{ display_currency($batch->order_details_cog_sum_margin) }}
        </td>
        <td>{{ display_currency($batch->transfer_logs->count() ? $batch->transfer_logs->sum('inventory_loss')*-1 : 0) }}</td>
    </tr>

    @if($batch->children_batches->count())

        @include('users._purchase-orders', ['child_batches'=>$batch->children_batches, 'depth'=>$depth+$indent, 'created_batches'=>$created_batches])

    @endif

    @if($batch->created_batch)

        @if( ! $created_batches->contains($batch->created_batch->id) )

            @php $created_batches->push($batch->created_batch->id) @endphp

            @include('users._purchase-orders', ['child_batches'=>[$batch->created_batch], 'depth'=>-1, 'created_batches'=>$created_batches])

        @else

            <tr>
                <td colspan="11" style="background: #00CC00">Batch created: {{ $batch->created_batch->id }}</td>
            </tr>


        @endif

    @endif


@endforeach