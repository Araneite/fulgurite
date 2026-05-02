<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait HandlesIncludes
{
    protected function applyIncludes(Request $request, Builder $query, array $allowedIncludes = []): Builder
    {
        $includes = $this->parseIncludes($request);
        
        if (! empty($allowedIncludes)) {
            $includes = $includes->filter(fn (string $include) => in_array($include, $allowedIncludes, true));
        }
        
        $relations = $includes
            ->filter(fn (string $include) => method_exists($query->getModel(), $include))
            ->values()
            ->all();
        
        if (! empty($relations)) {
            $query->with($relations);
        }
        
        return $query;
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
