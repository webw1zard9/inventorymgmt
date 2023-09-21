<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Coingate extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'coingate';
    }
}
