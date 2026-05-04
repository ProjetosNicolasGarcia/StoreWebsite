<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CatalogCacheObserver
{
    /**
     * Blindagem de Segurança Transacional.
     * Garante que o cache só seja invalidado DEPOIS que as operações
     * do painel administrativo (Filament) forem comitadas no banco de dados.
     */
    public bool $afterCommit = true;

    public function saved(Model $model): void
    {
        $this->flushRelevantCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->flushRelevantCache($model);
    }

    /**
     * Invalida as Cache Tags pertinentes ao modelo modificado.
     * * @param Model $model
     */
    protected function flushRelevantCache(Model $model): void
    {
        // Limpa a tag genérica do catálogo e a tag específica da tabela modificada.
        // Exemplo: se salvar um Produto, limpa tags: ['catalog', 'products']
        Cache::tags(['catalog', $model->getTable()])->flush();
    }
}