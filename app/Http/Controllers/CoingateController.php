<?php

namespace App\Http\Controllers;

use App\Facades\Coingate;

class CoingateController extends Controller
{
    public function rates($from, $to)
    {
        $value = Coingate::getExchangeRate($from, $to);

        return response()->json([
            'coin_value' => $value,
        ]);
    }
}
