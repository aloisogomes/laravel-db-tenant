# Laravel DB Tenant

![GitHub Release](https://img.shields.io/github/v/release/aloisogomes/laravel-db-tenant)

**Laravel DB Tenant** is a lightweight, elegant package that allows you to switch database connections "on the fly" for specific blocks of code. Unlike global configuration changes, this package uses a **Context Stack**, allowing for nested connection switches while ensuring Eloquent models remain "sticky" to the connection they were created in.

It is perfect for:

  * Multi-tenant applications with separate databases per client.
  * Data migration scripts moving data between connections.
  * Archival systems where you need to access historical data on a separate DB.

## Features

  * **Context Stacking:** Supports nested `start()` and `end()` calls.
  * **Sticky Connections:** Models instantiated inside a tenant block "remember" their connection, even after the block ends.
  * **Safe Transactions:** Helper to run transactions on the *current* active context.
  * **Queue & Request Safety:** Automatically resets the connection stack after every HTTP request and Queue Job to prevent data leaks.
  * **Zero Config:** Works out of the box with standard Laravel database configurations.

# Before you start..

This package assumes that your connections are to separate databases, but with the same table structure, precisely to take advantage of the rules defined in your models and business logic. Think, for example, of a store platform, where each store has its own database and works separately on different servers, etc... But you want to give an accountant a single platform where he can obtain data from each of the stores in just one place.
 

## This package is not for you if you expect:

* Create complex relationships between tables from different databases (joins, unions, views, etc.);
* Harmonize data from different tables;
* Alert, handle or ensure data consistency involving distinct connections;
* Manage transactions and locks in operations involving objects from different databases*;
* Create replications, mirroring, or backups**

**You can create a script that orchestrates an operation flow ensuring the order and criteria for separate transactions, but it is not the responsibility of this package to manage that flow.*

***You may create a listener in your application to manage events in your models and use this package to update another database, but handling failures or ensuring consistency is out of the scope of this project. The responsibility for these flows remains with the developer who desires this behavior.*

## Installation

You can install the package via composer:

```bash
composer require aloisogomes/laravel-db-tenant
```

## Setup

Configure your known connections in `config/database.php` at `connections` list:

```php
use Illuminate\Support\Str;

return [
  'default' => env('DB_CONNECTION', 'default'),
  // the main key of each connection will be available to our Tenant context
  'connections' => [
    'default' => [
        'driver' => 'sqlite',
        'url' => env('DB_URL'),
        'database' => env('DB_DATABASE', database_path('database.sqlite')),
        'prefix' => '',
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        'busy_timeout' => null,
        'journal_mode' => null,
        'synchronous' => null,
    ],
    'legacy' => [
        'driver' => 'sqlite',
        'url' => env('DB_URL_LEGACY'),
        'database' => env('DB_DATABASE_LEGACY', database_path('database_legacy.sqlite')),
        'prefix' => '',
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS_LEGACY', true),
        'busy_timeout' => null,
        'journal_mode' => null,
        'synchronous' => null,
    ],
    /******
     * 'other_conn' => [...],
     * ...
     * ****/
  ],
  // Others configs...
];
```

Add the `HasTenantConnection` trait to any Eloquent Model you want to be tenant-aware.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use AloisoGomes\LaravelDbTenant\Traits\HasTenantConnection;

class User extends Model
{
    use HasTenantConnection;

    // ...
}
```

That's it. Your model is now ready to react to context switches.

## Usage

### 1\. Basic Context Switching

Use the `Tenant` facade to define the context. Any model retrieved inside the block will use the specified connection.

```php
use AloisoGomes\LaravelDbTenant\Facades\Tenant;
use App\Models\User;

// 1. Default Context (uses default connection from .env), in our example connection named as 'default'
$defaultUser = User::find(1); 

// 2. Switch to 'legacy' connection
Tenant::start('legacy');

$legacyUser = User::find(1); 
// This query runs on 'lagacy'

// 3. End context (returns to previous state)
Tenant::end();
```

### 2\. Sticky Connections (The Power Feature)

Models created within a tenant context retain their connection reference forever. You can manipulate and save them safely outside the tenant block.

```php
Tenant::start('legacy');
$legacyUser = User::find(500);
Tenant::end();

// We are back to the default connection here
$defaultUser = User::find(1);

// UPDATE TEST:
$defaultUser->update(['name' => 'Local']); // Updates default DB
$legacyUser->update(['name' => 'Legacy']); // Updates 'legacy' automatically!
```

### 3\. Nested Contexts (Stacking)

The package manages a LIFO (Last In, First Out) stack.

```php
// Stack: [] (Default)
Tenant::start('legacy');

    // Stack: ['legacy']
    $legacyUser = User::first(); 

    Tenant::start('other_conn');
    
        // Stack: ['legacy', 'other_conn']
        $otherConnUser = User::first();
        
    Tenant::end();
    
    // Stack: ['legacy'] (Back to Legacy)
    $anotherLegacyUser = User::find(500);

Tenant::end();
// Stack: [] (Back to Default)
```

### 4\. Explicit Override

If you need to force a specific connection regardless of the current context, use the `tenant()` static method (alias for `on`):

```php
Tenant::start('legacy');

// Forces 'default' connection even inside the 'lagacy' block
$admin = User::tenant('default')->find(1);
```

### 5\. Transactions

To safely run transactions within the current active context (whether it is the default or a tenant), use the `transaction` helper:

```php
Tenant::start('legacy');

Tenant::transaction(function () {
    // This transaction starts specifically on 'legacy' connection
    $user = User::find(1);
    $user->balance -= 100;
    $user->save();
    
    // If this fails, only 'legacy' is rolled back
});

Tenant::end();
```

## Safety Measures

One of the biggest risks with runtime connection switching is "polluting" the state for subsequent requests (e.g., in Laravel Octane or Queue Workers).

This package automatically handles cleanup:

  * **HTTP Requests:** The stack is reset when the app terminates (after the response is sent).
  * **Queue Jobs:** The stack is reset after every Job is processed or failed.

You can also manually reset the stack at any time:

```php
// Emergency reset
Tenant::reset(); 
```

## Credits

  * [Aloiso Gomes](https://github.com/aloisogomes)  

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.