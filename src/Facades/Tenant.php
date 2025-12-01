<?php
namespace AloisoGomes\LaravelDbTenant\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void start(string $connectionName)
 * @method static void end()
 * @method static string|null current()
 * @method static mixed transaction(callable $callback)
 */
class Tenant extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'db-tenant';
    }
}