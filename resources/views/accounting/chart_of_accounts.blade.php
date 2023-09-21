@extends('layouts.app')



@section('content')

    <div class="row">
        <div class="col-lg-8">

            <div class="card-box">
                <h5 class="hidden-print">Ledgers</h5>

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

                    @foreach($ledgers as $ledger)

                                    <tr scope="row" class="table-warning">
                                        <td>{{ $ledger->id }}</td>
                                        <td>{{ $ledger->name }}</td>
                                        <td>{{ $ledger->type }}</td>
                                        <td>{{ display_currency($ledger->getCurrentBalanceInDollars()) }}</td>

                                    </tr>

                                @if($ledger->journals)

                                    @foreach($ledger->journals as $journal)

                                        <tr scope="row">
                                            <td>{{ $journal->morphed->id }}</td>
                                            <td>{{ $journal->morphed->name }}</td>
                                            <td>{{ get_class($journal->morphed) }}</td>
                                            <td>{{ display_currency($journal->morphed->journal_balance_in_dollars) }}</td>
                                            <td></td>
                                        </tr>
                                    @endforeach

                                @endif


                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection