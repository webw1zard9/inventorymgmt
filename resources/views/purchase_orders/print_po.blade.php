<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $purchaseOrder->vendor->name.' - '.$purchaseOrder->ref_number }}</title>

    <style type="text/css">
        @page {
            margin: 10px;
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
            font-size: 16px;
        }
        .invoice {
            padding: 0 15px;
        }
        .invoice table {
            /*margin: 15px;*/
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

@if($purchaseOrder->balance == 0)
    <div class="paid-stamp">
        <img src="{{ public_path() }}/images/paid-stamp.png" width="140px" style="position: absolute; left: 50%; top: 240px; margin-left: -70px">
    </div>
@endif

<div class="information">

    <table width="100%">
        <tr>
            <td align="left" style="width: 50%; padding-top: 0px;">
                <h3 style="font-size: 18px">Purchase Order# {{ $purchaseOrder->ref_number }}</h3>
            </td>
            <td align="right" style="width: 50%; padding-top: 0px;">

            </td>
        </tr>
    </table>

</div>

<div class="information">

    <table width="100%">
        <tr>

            <td align="left" style="width: 35%; padding-top: 0px; vertical-align: top;">
                <strong>Vendor: {{ $purchaseOrder->vendor->name }}</strong>
            </td>

            <td align="left" style="width: 35%; padding-top: 0px; vertical-align: top;">

            </td>
            <td align="right" style="width: 30%; padding-top: 0px; vertical-align: top;">
            </td>
        </tr>

    </table>

</div>
<hr>
<div class="information">

    <table width="100%">
        <tr>
            <td align="left" valign="top" style="width: 75%; padding-top: 0px;">
                {{--<p><strong>Metrc Manifest#: </strong> {{ $purchaseOrder->manifest_no }}</p>--}}
                <p><strong>Status: </strong> <span class="badge badge-{{ ( ($purchaseOrder->balance > 0) ? 'success' : 'danger' ) }}">{{ ( ($purchaseOrder->balance > 0) ? 'Open' : 'Paid' ) }}</span></p>
                <p><strong>Date: </strong> {{ $purchaseOrder->txn_date->format('M d, Y') }}</p>

            </td>
            <td align="left" valign="top" style="width: 25%; padding-top: 0px;">
                <p>
                    <strong>Terms:</strong>
                    @if( ! is_null($purchaseOrder->terms))
                        {{ config('inventorymgmt.payment_terms')[$purchaseOrder->terms] }}
                    @else
                        {{ (!empty($purchaseOrder->vendor->details['terms']) ? config('inventorymgmt.payment_terms')[$purchaseOrder->vendor->details['terms']] : 'Due on Receipt' ) }}
                    @endif
                </p>
                <p>
                    <strong>Due Date: </strong>
                    @if($purchaseOrder->due_date)
                        {{ $purchaseOrder->due_date->format('M d, Y') }}
                    @else
                        {{ $purchaseOrder->txn_date->addDays((!empty($purchaseOrder->vendor->details['terms']) ? $purchaseOrder->vendor->details['terms'] : 0 ))->format('M d, Y') }}
                    @endif
                </p>
                <p style="background: #eee; padding: 5px;" class="balance">Balance: {{ display_currency($purchaseOrder->balance) }}</p>
            </td>
        </tr>
    </table>
</div>

<div class="invoice">
    <h2>Items</h2>
        <table width="100%" class="order-items" style="border: 1px">
            <thead>
            <tr>
                <th>UID</th>
                <th width="330px">Name</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            @foreach($purchaseOrder->batches as $batch)
                <tr>

                    <td style="white-space: nowrap;">{{ $batch->ref_number }}</td>
                    <td>
                        @if($batch->brand) <strong>{{ $batch->brand->name }}</strong><br> @endif
                        {{ $batch->category->name }}: {{ $batch->present()->non_branded_name }}
                    </td>
                    <td style="white-space: nowrap;">
                        {{ $batch->units_purchased }} {{ $batch->uom }}
                    </td>
                    <td>
                        {{ display_currency($batch->unit_price) }}
                    </td>
                    <td>{{ display_currency($batch->subtotal_price) }}</td>
                    {{--<td>({{ display_currency($batch->tax) }})</td>--}}
{{--                    <td>{{ display_currency($batch->subtotal_price - $batch->tax) }}</td>--}}
                </tr>
            @endforeach
            </tbody>

            <tfoot style="font-size: 19px">
                <tr>
                    <td></td>
                    {{--<td></td>--}}
                    <td></td>
                    <td></td>
                    <td style="text-align: right"></td>
                    <td>{{ display_currency($purchaseOrder->batches->sum('subtotal_price')) }}</td>
{{--                    <td>({{ display_currency($purchaseOrder->batches->sum('tax')) }})</td>--}}
{{--                    <td>{{ display_currency($purchaseOrder->batches->sum('subtotal_price') - $purchaseOrder->batches->sum('tax')) }}</td>--}}
                </tr>
            </tfoot>
        </table>


</div>

<div class="information" style="">
    <table width="100%">

        <tr>
            <td align="left" style="width: 40%;">

            </td>
            <td align="right" style="width: 60%;">

            </td>
        </tr>


    </table>
</div>

@if($purchaseOrder->transactions->count())

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
            @foreach($purchaseOrder->transactions as $transaction)
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
                <td>{{ display_currency($purchaseOrder->transactions->sum('amount')) }}</td>
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