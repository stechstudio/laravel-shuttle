<?php

namespace STS\Shuttle\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \STS\Shuttle\Shuttle
 */
class Shuttle extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-shuttle';
    }
}
