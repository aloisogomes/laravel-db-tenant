<?php

namespace AloisoGomes\LaravelDbTenant\Traits;

use AloisoGomes\LaravelDbTenant\Facades\Tenant;
use Illuminate\Support\Facades\Config;

trait HasTenantConnection
{
    /**
     * Alias estático para User::on($conn).
     */
    public static function tenant(string $connectionName)
    {
        return static::on($connectionName);
    }

    /**
     * Inicializa a conexão baseada no contexto.
     */
    public function initializeHasTenantConnection()
    {
        // Se não houver tenant ativo, encerra.
        if (!($currentTenant = Tenant::current())) {
            return;
        }

        // Se a conexão já foi definida explicitamente (ex: via tenant()), respeita ela.
        // Verificamos se a conexão atual é diferente da default para saber se foi alterada.
        if ($this->getConnectionName() !== Config::get('database.default')) {
             return;
        }

        $this->setConnection($currentTenant);
    }

    /**
     * Método auxiliar para descobrir de onde o objeto veio.
     */
    public function getTenantConnectionName(): string
    {
        return $this->getConnectionName();
    }
}