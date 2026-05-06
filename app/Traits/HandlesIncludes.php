<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait HandlesIncludes
{
    /**
     * Applies requested includes from ?include=...
     *
     * Supported config:
     * - relations: Eloquent relations allowed for eager loading
     * - fields: optional columns allowed through addSelect()
     * - aliases: public include names resolving to one or many relations/fields
     *
     * Legacy format ["relationA", "relationB"] is treated as relations only.
     * 
     * @param Request $request Pass the request used on this endpoint.
     * @param Builder $query Pass the query builder to eager load relations and fields.
     * @param array $config Array of config allowed in the response.
     * 
     */
    protected function applyIncludes(Request $request, Builder $query, array $config = []): Builder
    {
        $config = $this->normalizeIncludesConfig($config);
        
        $includes = $this->parseIncludes($request)
            ->flatMap(fn (string $include) => $config['aliases'][$include] ?? [$include])
            ->unique()
            ->values();
        
        $relations = $includes
            ->filter(fn (string $include) =>
                in_array($include, $config['relations'], true)
                && method_exists($query->getModel(), $include)
            )
            ->values()
            ->all();
        
        $fields = $includes
            ->filter(fn (string $include) => in_array($include, $config['fields'], true))
            ->values()
            ->all();
        
        if (! empty($relations)) {
            $query->with($relations);
        }
        
        if (! empty($fields)) {
            $query->addSelect($fields);
        }
        
        return $query;
    }
    
    protected function normalizeIncludesConfig(array $allowedIncludes): array
    {
        if (
            array_key_exists('relations', $allowedIncludes)
            || array_key_exists('fields', $allowedIncludes)
            || array_key_exists('aliases', $allowedIncludes)
        ) {
            return [
                'relations' => $allowedIncludes['relations'] ?? [],
                'fields' => $allowedIncludes['fields'] ?? [],
                'aliases' => $allowedIncludes['aliases'] ?? [],
            ];
        }
        
        return [
            'relations' => $allowedIncludes,
            'fields' => [],
            'aliases' => [],
        ];
    }
    
    protected function parseIncludes(Request $request): Collection
    {
        return collect(explode(',', (string) $request->query('include')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique()
            ->values();
    }
}
