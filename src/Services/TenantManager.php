<?php

namespace AloisoGomes\LaravelDbTenant\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TenantManager
{
    protected array $connectionStack = [];

    /**
     * Inicia um novo contexto (empilha).
     */
    public function start(string $connectionName): void
    {
        $this->connectionStack[] = $connectionName;
    }

    /**
     * Encerra o contexto atual (desempilha).
     */
    public function end(): void
    {
        if (!empty($this->connectionStack)) {
            array_pop($this->connectionStack);
        }
    }

    /**
     * Limpa toda a pilha (Reset total).
     */
    public function reset(): void
    {
        $this->connectionStack = [];
    }

    /**
     * Retorna o nome da conexão ativa ou null.
     */
    public function current(): ?string
    {
        if (empty($this->connectionStack)) {
            return null;
        }
        return end($this->connectionStack);
    }

    /**
     * Executa uma transação no banco do contexto atual.
     */
    public function transaction(callable $callback)
    {
        $connection = $this->current() ?? Config::get('database.default');
        return DB::connection($connection)->transaction($callback);
    }
}