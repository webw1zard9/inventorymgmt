<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $saleOrder->customer->name.' - '.$saleOrder->ref_number }}</title>

    <style type="text/css">
        @page {
            margin: 0px;
        }
        body {
            margin: 10px;
            font-size: 12px;
        }
        * {
            font-family: Verdana, Arial, sans-serif;
        }
        a {
            color: #fff;
            text-decoration: none;
        }
        table {
            font-size: small;
        }
        th {
            text-align: left;
        }

        .payments th {
            padding: 4px;
            background: #eee;
        }

        .order-items td,
        .payments  td {
            border-bottom: 1px solid #EEEEEE;
        }

        td {
            padding: 3px;

        }
        tfoot tr td {
            font-weight: bold;
            font-size: small;
        }
        .invoice table {
            margin: 15px;
        }
        .invoice h3 {
            margin-left: 15px;
        }
        .spacer {
            width: 65%;
        }
        .label {
            text-align: right;
        }
        .balance {
            font-size: 16px;
        }
        .information {
            /*background-color: ;*/
            color: #000;
            padding: 0 15px;
        }
        .information .logo {
            margin: 5px;
        }
        .information table {
            /*padding: 10px;*/
        }

        .subtotal_table td.label {
            width: 70%;
        }
        .subtotal_table td {
            width: 30%;
        }
    </style>

</head>
<body>

@if($saleOrder->balance == 0)
    <div class="paid-stamp">
        <img src="{{ public_path() }}/images/paid-stamp.png" width="140px" style="position: absolute; left: 50%; top: 140px; margin-left: -70px">
    </div>
@endif

<div class="information">


    <table width="100%">

        <tr>
            <td align="left" style="width: 35%; padding-top: 0px;">
                <h3 style="font-size: 18px; margin: 0;">

                    @if($saleOrder->type == 'return')
                        Credit Memo#
                    @elseif($saleOrder->sale_type == 'transfer')
                        Transfer#
                    @else
                        Invoice#
                    @endif
                    <br>{{ $saleOrder->ref_number }}
                </h3>

            </td>
            <td align="left" style="width: 35%;">


            </td>
            <td align="right" style="width: 30%;">

            </td>
        </tr>
        <tr>
            <td align="left" style="width: 35%;">
                <strong>Ship To:</strong>
                <address>
                    @if( ! empty($saleOrder->destination_license) )
                        {!! $saleOrder->destination_license->present()->name_address()  !!}
                    @endif
                </address>
            </td>
            <td align="left" style="width: 35%;">
                <strong>Bill To:</strong>
                <address>
                    {{ $saleOrder->customer->name }}
                    @if(!empty($saleOrder->customer->details['business_name']) && $saleOrder->customer->name != $saleOrder->customer->details['business_name'])
                        <br>{{ ($saleOrder->customer->details['business_name']) }}
                    @endif
                    <br>{{ $saleOrder->customer->details['address'] }}
                    <br>{{ $saleOrder->customer->details['address2'] }}
                </address>
            </td>
        </tr>

    </table>
<hr>

    <table width="100%">
        <tr>


            <td align="left" style="width:70%; vertical-align: top;">

                <p><strong>Order Date: </strong> {{ $saleOrder->txn_date->format('M d, Y') }}
                    @if($saleOrder->expected_delivery_date)
                        <br><strong>Expected Delivery Date: </strong> {{ $saleOrder->expected_delivery_date->format('M d, Y') }}
                    @endif
                </p>

            </td>

            <td align="left" valign="top" style="width:30%;">


                <p>
                    <strong>Terms:</strong>
                    @if( ! is_null($saleOrder->terms))
                        {{ config('inventorymgmt.payment_terms')[$saleOrder->terms] }}
                    @else
                        {{ (!empty($saleOrder->customer->details['terms']) ? config('inventorymgmt.payment_terms')[$saleOrder->customer->details['terms']] : 'Due on Receipt' ) }}
                    @endif
                <br>
                    <strong>Due Date: </strong>
                    @if($saleOrder->due_date)
                        {{ $saleOrder->due_date->format('M d, Y') }}
                    @else
                        {{ $saleOrder->txn_date->addDays((!empty($saleOrder->customer->details['terms']) ? $saleOrder->customer->details['terms'] : 0 ))->format('M d, Y') }}
                    @endif
                </p>

                <p style="background: #eee; padding: 5px;" class="balance">Balance: {{ display_currency($saleOrder->balance) }}</p>


            </td>

            <td align="left" valign="top" style="width:10%;">
            </td>
        </tr>

    </table>

</div>

<div class="invoice">

    <table width="100%" class="order-items" style="border: 1px">
        <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>SKU</th>
            <th class="text-right" nowrap>Qty</th>
            <th class="text-right">Price</th>
            <th class="text-right">Total</th>
        </tr>
        </thead>

        <tbody>

        @foreach($saleOrder->order_details->groupBy('cog')->sortKeysDesc() as $isCOG => $order_details_collection)

            @foreach($order_details_collection->groupBy('batch.category.name') as $category_name => $order_details)

                <tr>
                    <td colspan="{{ ($saleOrder->status=='delivered'?'9':'8') }}" style="background: #eee; padding: 4px;"><strong>{{ ( ! $isCOG?'Misc.':$category_name) }}</strong></td>
                </tr>

                @foreach($order_details->sortBy('sold_as_name') as $order_detail)

                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        @if( ! $isCOG)

                            <td colspan="2">{{ ($order_detail->sold_as_name?:$order_detail->batch->name) }}</td>

                        @else

                            <td>{{ ($order_detail->sold_as_name?:$order_detail->batch->name) }}</td>
                        <td>
                            @if(!empty($order_detail->batch))
                                {{ ($order_detail->metrc_uid?:$order_detail->batch->ref_number) }}
                            @endif
                        </td>

                        @endif
                        {{--<td></td>--}}
                        <td class="text-right" nowrap>

                            @if(!empty($order_detail->batch) && $order_detail->batch->wt_based)

                                @if( ! is_null($order_detail->units_accepted) && $order_detail->units_accepted == 0)
                                    0
                                @else

                                    {{$order_detail->batch->wt_grams}} g
                                @endif

                            @else
                                {{ !is_null($order_detail->units_accepted)?$order_detail->units_accepted:$order_detail->units }} <span style="font-size: 70%;">{{ (!empty($order_detail->batch)?$order_detail->batch->uom:'') }}</span>
                            @endif

                        </td>
                        <td class="text-right">{{ display_currency($order_detail->unit_sale_price) }}</td>
                        <td class="text-right">{{ display_currency($order_detail->subtotal) }}</td>
                    </tr>

                @endforeach

                <tr>
                    <td></td>
                    {{--<td></td>--}}
                    {{--<td></td>--}}
                    <td></td>
                    <td></td>
                    <td></td>
                    <td colspan="">Subtotal:</td>
                    <td colspan="">{{ display_currency($order_details->sum('subtotal')) }}</td>
                </tr>

            @endforeach

        @endforeach

        </tbody>

    </table>


</div>

<div class="information" style="">
    <table width="100%">

        <tr>
            <td align="left" style="width: 50%;">
                <p></p>
            </td>
            <td align="right" style="width: 50%;">
                <table width="100%" style="" align="right" class="subtotal_table">

                    <tr>

                        <td class="label">Subtotal:</td>
                        <td>{{ display_currency($saleOrder->subtotal) }}</td>
                    </tr>

                    @if($saleOrder->discount)
                        <tr>

                            <td class="label">Discount{{ ($saleOrder->discount_description?' - '.$saleOrder->discount_description:'') }}:</td>
                            <td><span style="color: red;">({{ display_currency($saleOrder->discount) }})</span></td>
                        </tr>

                        <tr>

                            <td class="label">Subtotal w/ <Discount></Discount>:</td>
                            <td><span>{{ display_currency($saleOrder->subtotal - $saleOrder->discount) }}</span></td>
                        </tr>

                    @endif

                    <tr>
                        <td class="label">Total:</td>
                        <td>{{ display_currency($saleOrder->total) }}</td>
                    </tr>

                </table>
            </td>
        </tr>

        <tr>
            <td colspan="2" align="left" style="vertical-align: top;">
                @if(false && $saleOrder->sale_type == 'bulk')
                    <p style="padding: 5px; background: yellow; display: inline">*** {{ $saleOrder->customer->name }} is responsible for the cultivation tax of product on this invoice. ***</p>
                @endif
                @if($saleOrder->order_notes)
                    <p>
                        <strong>Order Notes</strong><br>
                        {!! nl2br($saleOrder->order_notes) !!}
                    </p>
                @endif
                @if(!empty($saleOrder->customer->details['delivery_window']))
                    <strong>Delivery Window:</strong> {{ $saleOrder->customer->details['delivery_window'] }}
                @endif
            </td>

        </tr>

    </table>
</div>

@if($saleOrder->transactions->count())

<div class="information" style="">
    <h4>Payments</h4>
    <table width="100%" class="payments">

        <thead>
        <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Ref#</th>
            <th>Memo</th>
        </tr>
        </thead>


        <tbody>
        @foreach($saleOrder->transactions as $transaction)
            <tr>
                <td>{{ $transaction->txn_date() }}</td>
                <td>{{ display_currency($transaction->amount) }}</td>
                <td>{{ $transaction->payment_method }}</td>
                <td>{{ $transaction->ref_number }}</td>
                <td>{{ $transaction->memo }}</td>
            </tr>
        @endforeach

        </tbody>

        <tfoot>
        <tr>
            <td>Total</td>
            <td>{{ display_currency($saleOrder->transactions->sum('amount')) }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        </tfoot>

    </table>
</div>

@endif

</body>
</html>