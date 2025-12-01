<?php

namespace AloisoGomes\LaravelDbTenant\Traits;

use AloisoGomes\LaravelDbTenant\Facades\Tenant;
use Illuminate\Support\Facades\Config;

trait HasTenantConnection
{
    public static function tenant(string $connectionName)
    {
        return static::on($connectionName);
    }

    public function initializeHasTenantConnection()
    {
        // 1. Se não houver nenhum tenant ativo na pilha, não faz nada.
        if (!($currentTenant = Tenant::current())) {
            return;
        }

        // 2. CORREÇÃO DO BUG:
        // O Model padrão retorna NULL em getConnectionName().
        // Precisamos verificar se a conexão foi alterada explicitamente para algo
        // que NÃO seja null e NEM a default.
        $currentConnection = $this->getConnectionName();
        $defaultConnection = Config::get('database.default');

        // Se a conexão já está definida (não é null) E é diferente da padrão,
        // significa que o usuário forçou algo (ex: via propriedade na classe).
        // Nesse caso, respeitamos e não sobrescrevemos.
        if ($currentConnection !== null && $currentConnection !== $defaultConnection) {
             return;
        }

        // 3. Aplica a conexão do Tenant Context
        $this->setConnection($currentTenant);
    }

    public function getTenantConnectionName(): string
    {
        return $this->getConnectionName();
    }
}