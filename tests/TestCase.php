<?php

namespace AloisoGomes\LaravelDbTenant\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Schema\Blueprint;
use AloisoGomes\LaravelDbTenant\TenantServiceProvider;
use AloisoGomes\LaravelDbTenant\Facades\Tenant;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            TenantServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Tenant' => Tenant::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Configura banco Default
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Configura banco Tenant 1
        $app['config']->set('database.connections.tenant_db', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Configura banco Tenant 2 (para testes de pilha)
        $app['config']->set('database.connections.other_tenant', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUpDatabase($app)
    {
        // Cria tabela users no Default
        $app['db']->connection('testing')->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Cria tabela users no Tenant 1
        $app['db']->connection('tenant_db')->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Cria tabela users no Tenant 2
        $app['db']->connection('other_tenant')->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
}