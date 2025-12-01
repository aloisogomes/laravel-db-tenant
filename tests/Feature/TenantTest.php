<?php

namespace AloisoGomes\LaravelDbTenant\Tests\Feature;

use AloisoGomes\LaravelDbTenant\Tests\TestCase;
use AloisoGomes\LaravelDbTenant\Tests\Models\User;
use AloisoGomes\LaravelDbTenant\Facades\Tenant;

class TenantTest extends TestCase
{
    /** @test */
    public function it_uses_default_connection_when_no_tenant_is_active()
    {
        $user = new User();
        $this->assertEquals('testing', $user->getConnectionName());
    }

    /** @test */
    public function it_switches_connection_when_tenant_starts()
    {
        Tenant::start('tenant_db');
        $user = new User();
        $this->assertEquals('tenant_db', $user->getConnectionName());
        Tenant::end();
        
        $user2 = new User();
        $this->assertEquals('testing', $user2->getConnectionName());
    }

    /** @test */
    public function it_maintains_sticky_connection_after_context_ends()
    {
        Tenant::start('tenant_db');
        
        $remoteUser = User::create(['name' => 'Remote User']);
        $this->assertEquals('tenant_db', $remoteUser->getConnectionName());

        Tenant::end();

        // O objeto deve manter a conexão onde foi criado
        $this->assertEquals('tenant_db', $remoteUser->getConnectionName());

        // Atualização deve ir para o banco correto
        $remoteUser->update(['name' => 'Updated']);
        
        $this->assertDatabaseHas('users', ['name' => 'Updated'], 'tenant_db');
        $this->assertDatabaseMissing('users', ['name' => 'Updated'], 'testing');
    }

    /** @test */
    public function it_supports_nested_stacks()
    {
        Tenant::start('tenant_db');
        $this->assertEquals('tenant_db', Tenant::current());

        Tenant::start('other_tenant');
        $this->assertEquals('other_tenant', Tenant::current());
        
        // Objeto criado no nível 2
        $userL2 = new User();
        $this->assertEquals('other_tenant', $userL2->getConnectionName());

        Tenant::end(); // Sai do nível 2
        $this->assertEquals('tenant_db', Tenant::current());

        Tenant::end(); // Sai do nível 1
        $this->assertNull(Tenant::current());
    }

    /** @test */
    public function it_allows_explicit_override_via_tenant_method()
    {
        Tenant::start('tenant_db');

        // Força uso do testing mesmo estando no tenant_db
        $user = User::tenant('testing')->create(['name' => 'Force Local']);

        $this->assertEquals('testing', $user->getConnectionName());
        $this->assertDatabaseHas('users', ['name' => 'Force Local'], 'testing');
    }

    /** @test */
    public function it_runs_transactions_on_active_tenant()
    {
        Tenant::start('tenant_db');

        try {
            Tenant::transaction(function () {
                User::create(['name' => 'Rollback Me']);
                throw new \Exception('Fail');
            });
        } catch (\Exception $e) {
            // catch
        }

        $this->assertDatabaseMissing('users', ['name' => 'Rollback Me'], 'tenant_db');
        Tenant::end();
    }
}