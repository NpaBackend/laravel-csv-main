<?php

namespace Npa\LaravelCsv\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelCsv extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-csv';
    }
}
