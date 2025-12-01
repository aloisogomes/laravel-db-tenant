<?php

namespace AloisoGomes\LaravelDbTenant\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use AloisoGomes\LaravelDbTenant\Traits\HasTenantConnection;

class User extends Model
{
    use HasTenantConnection;

    protected $guarded = [];
    protected $table = 'users';
}