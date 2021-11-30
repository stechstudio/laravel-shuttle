<?php

namespace STS\Shuttle\Facades;

use Illuminate\Support\Facades\Facade;
use STS\Shuttle\ShuttleManager;

/**
 * @see \STS\Shuttle\ShuttleManager
 */
class Shuttle extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ShuttleManager::class;
    }
}
