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

## Installation

You can install the package via composer:

```bash
composer require aloisogomes/laravel-db-tenant
```

## Setup

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

// 1. Default Context (uses default connection from .env)
$localUser = User::find(1); 

// 2. Switch to 'tenant_db' connection
Tenant::start('tenant_db');

$remoteUser = User::find(1); 
// This query runs on 'tenant_db'

// 3. End context (returns to previous state)
Tenant::end();
```

### 2\. Sticky Connections (The Power Feature)

Models created within a tenant context retain their connection reference forever. You can manipulate and save them safely outside the tenant block.

```php
Tenant::start('archive_db');
$archivedUser = User::find(500);
Tenant::end();

// We are back to the default connection here
$localUser = User::find(1);

// UPDATE TEST:
$localUser->update(['name' => 'Local']); // Updates default DB
$archivedUser->update(['name' => 'Archived']); // Updates 'archive_db' automatically!
```

### 3\. Nested Contexts (Stacking)

The package manages a LIFO (Last In, First Out) stack.

```php
// Stack: [] (Default)
Tenant::start('client_A');

    // Stack: ['client_A']
    $userA = User::first(); 

    Tenant::start('client_B');
    
        // Stack: ['client_A', 'client_B']
        $userB = User::first();
        
    Tenant::end();
    
    // Stack: ['client_A'] (Back to Client A)
    $anotherUserA = User::first();

Tenant::end();
// Stack: [] (Back to Default)
```

### 4\. Explicit Override

If you need to force a specific connection regardless of the current context, use the `tenant()` static method (alias for `on`):

```php
Tenant::start('client_A');

// Forces 'mysql' connection even inside the 'client_A' block
$admin = User::tenant('mysql')->find(1);
```

### 5\. Transactions

To safely run transactions within the current active context (whether it is the default or a tenant), use the `transaction` helper:

```php
Tenant::start('client_A');

Tenant::transaction(function () {
    // This transaction starts specifically on 'client_A' connection
    $user = User::find(1);
    $user->balance -= 100;
    $user->save();
    
    // If this fails, only 'client_A' is rolled back
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