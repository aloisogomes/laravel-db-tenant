<?php

namespace AloisoGomes\LaravelDbTenant\Tests\Feature;

use AloisoGomes\LaravelDbTenant\Tests\TestCase;
use AloisoGomes\LaravelDbTenant\Tests\Models\User;
use AloisoGomes\LaravelDbTenant\Facades\Tenant;
use PHPUnit\Framework\Attributes\Test;

class TenantTest extends TestCase
{
    #[Test]
    public function it_uses_default_connection_when_no_tenant_is_active()
    {
        $user = new User();
        
        // CORREÇÃO: Usamos getConnection()->getName() para resolver o 'null' 
        // para o nome real da conexão ativa ('testing').
        $this->assertEquals('testing', $user->getConnection()->getName());
    }

    #[Test]
    public function it_switches_connection_when_tenant_starts()
    {
        Tenant::start('tenant_db');

        $user = new User();
        // Aqui o nome é explícito porque nossa Trait setou a propriedade
        $this->assertEquals('tenant_db', $user->getConnectionName());

        Tenant::end();
        
        $user2 = new User();
        // CORREÇÃO: Aqui voltou para default (null), então resolvemos o objeto
        $this->assertEquals('testing', $user2->getConnection()->getName());
    }

    #[Test]
    public function it_maintains_sticky_connection_after_context_ends()
    {
        Tenant::start('tenant_db');
        
        $remoteUser = User::create(['name' => 'Remote User']);
        $this->assertEquals('tenant_db', $remoteUser->getConnectionName());

        Tenant::end();

        // Verifica se manteve a conexão
        $this->assertEquals('tenant_db', $remoteUser->getConnectionName());

        // Tenta update
        $remoteUser->update(['name' => 'Updated Name']);

        $this->assertDatabaseHas('users', ['name' => 'Updated Name'], 'tenant_db');
        $this->assertDatabaseMissing('users', ['name' => 'Updated Name'], 'testing');
    }

    #[Test]
    public function it_supports_nested_stacks()
    {
        Tenant::start('tenant_db');
        $this->assertEquals('tenant_db', Tenant::current());

        Tenant::start('other_tenant');
        $this->assertEquals('other_tenant', Tenant::current());
        
        $userL2 = new User();
        $this->assertEquals('other_tenant', $userL2->getConnectionName());

        Tenant::end();
        $this->assertEquals('tenant_db', Tenant::current());

        Tenant::end();
        $this->assertNull(Tenant::current());
    }

    #[Test]
    public function it_allows_explicit_override_via_tenant_method()
    {
        Tenant::start('tenant_db');

        $user = User::tenant('testing')->create(['name' => 'Force Local']);

        // Quando usamos 'on' ou 'tenant', o Laravel seta a conexão explicitamente,
        // então getConnectionName() retorna a string 'testing' e não null.
        $this->assertEquals('testing', $user->getConnectionName());
        $this->assertDatabaseHas('users', ['name' => 'Force Local'], 'testing');
    }
}