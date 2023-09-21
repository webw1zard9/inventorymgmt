<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    public static function getRate($from, $to)
    {
        return self::where('from', $from)->where('to', $to)->first();
    }

    public static function getRates()
    {
        $conversions = [];

        self::all()->each(function ($item) use (&$conversions) {
            $conversions[$item->from][$item->to] = $item->value;
        });

        return $conversions;
    }
}
