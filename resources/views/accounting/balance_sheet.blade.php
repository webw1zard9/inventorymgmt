@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-8">

            <div class="card-box">
                <h4 class="hidden-print">Assets</h4>

                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Type</th>

                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($ledger_by_gruops['asset'] as $ledger)
                        @continue(!$ledger->getCurrentBalanceInDollars())
                            <tr scope="row" class="table-warning">
                                <td>{{ $ledger->id }}</td>
                                <td>{{ $ledger->name }}</td>
                                <td>{{ $ledger->type }}</td>
                                <td>{{ display_currency($ledger->getCurrentBalanceInDollars()) }}</td>

                            </tr>

                        @if($ledger->journals)

                            @foreach($ledger->journals as $journal)
                                @continue(!$journal->morphed->journal_balance_in_dollars)
                                <tr scope="row">
                                    <td>{{ $journal->morphed->id }}</td>
                                    <td>{{ $journal->morphed->name }}</td>
                                    <td>{{ get_class($journal->morphed) }}</td>
                                    <td>{{ display_currency($journal->morphed->journal_balance_in_dollars) }}</td>
                                </tr>
                            @endforeach
                            @endif

                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="3">Total Assets</th>
                        <th>{{ display_currency($total_assets) }}</th>
                    </tr>
                    </tfoot>
                </table>

                <hr>

                <h4 class="hidden-print">Liabilities</h4>

                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Type</th>

                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($ledger_by_gruops['liability'] as $ledger)
                        @continue(!$ledger->getCurrentBalanceInDollars())
                        <tr scope="row" class="table-warning">
                            <td>{{ $ledger->id }}</td>
                            <td>{{ $ledger->name }}</td>
                            <td>{{ $ledger->type }}</td>
                            <td>{{ display_currency($ledger->getCurrentBalanceInDollars()) }}</td>

                        </tr>

                        @if($ledger->journals)

                            @foreach($ledger->journals as $journal)
                                @continue(!$journal->morphed->journal_balance_in_dollars)
                                <tr scope="row">
                                    <td>{{ $journal->morphed->id }}</td>
                                    <td>{{ $journal->morphed->name }}</td>
                                    <td>{{ get_class($journal->morphed) }}</td>
                                    <td>{{ display_currency($journal->morphed->journal_balance_in_dollars) }}</td>
                                </tr>
                            @endforeach
                        @endif

                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="3">Total Liabilities</th>
                        <th>{{ display_currency($total_liabilities) }}</th>
                    </tr>
                    </tfoot>
                </table>

                <h4 class="hidden-print">Equity</h4>

                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Type</th>

                        <th></th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($ledger_by_gruops['equity'] as $ledger)
                        {{--@continue(!$ledger->getCurrentBalanceInDollars())--}}
                        <tr scope="row" class="table-warning">
                            <td>{{ $ledger->id }}</td>
                            <td>{{ $ledger->name }}</td>
                            <td>{{ $ledger->type }}</td>
                            <td>{{ display_currency($ledger->getCurrentBalanceInDollars()) }}</td>

                        </tr>

                        @if($ledger->journals)

                            @foreach($ledger->journals as $journal)
                                @continue(!$journal->morphed->journal_balance_in_dollars)
                                <tr scope="row">
                                    <td>{{ $journal->morphed->id }}</td>
                                    <td>{{ $journal->morphed->name }}</td>
                                    <td>{{ get_class($journal->morphed) }}</td>
                                    <td>{{ display_currency($journal->morphed->journal_balance_in_dollars) }}</td>
                                </tr>
                            @endforeach
                        @endif

                        <tr scope="row">
                            <td></td>
                            <td>Earnings</td>
                            <td></td>
                            <td>{{ display_currency($retained_earnings) }}</td>
                        </tr>

                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="3">Total Equity</th>
                        <th>{{ display_currency($total_equity + $retained_earnings) }}</th>
                    </tr>

                    <tr>
                        <th colspan="3">Total Liabilities & Equity</th>
                        <th>{{ display_currency($total_liabilities + $total_equity + $retained_earnings) }}</th>
                    </tr>

                    </tfoot>
                </table>

            </div>
        </div>
    </div>

@endsection