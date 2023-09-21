<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $vendor->name }}</title>

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
        .payments td {
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

        .text-danger {
            color: #ef5350 !important;
        }

        .text-success {
            color: #52bb56 !important;
        }

        .mdi {
            display: inline-block;
            font: normal normal normal 24px/1 "Material Design Icons";
            font-size: inherit;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transform: translate(0, 0);
        }

        .mdi-check-circle:before {
            content: "\f5e0";
        }

        *, ::after, ::before {
            box-sizing: inherit;
        }
    </style>

</head>
<body>

<div class="information">

    <table width="100%">
        <tr>
            <td align="left" style="width: 50%; padding-top: 0px;">
                <h3 style="font-size: 18px">Vendor Statement</h3>
            </td>
            <td align="right" style="width: 50%; padding-top: 0px;">

            </td>
        </tr>
    </table>

</div>

<div class="information">

    <h3>Vendor: {{ $vendor->name }}</h3>

    <table width="100%">
        <tr></tr>
        <tr>
            <td align="left" style="width: 35%; padding-top: 0px; vertical-align: top;"><strong>Balance: {{ display_currency($final_balance) }}</strong></td>
            <td align="left" style="width: 35%; padding-top: 0px; vertical-align: top;"></td>
            <td align="right" style="width: 30%; padding-top: 0px; vertical-align: top;">Date: {{ \Carbon\Carbon::now()->format('m/d/Y') }}</td>
        </tr>

    </table>

</div>
<hr>
<div class="invoice">
    <h2>Transactions</h2>

    @include('users.vendors._statement_table')

</div>


</body>
</html>