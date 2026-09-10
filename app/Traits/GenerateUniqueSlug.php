<?php

namespace App\Traits;

trait GenerateUniqueSlug
{
    protected function makeUniqueSlug(
        string $baseSlug,
        string $modelClass,
        int|string|null $exceptId = null,
        string $column = 'slug'
    ): string {
        $slug = $baseSlug;
        $counter = 2;
        
        while (
            $modelClass::query()
            ->where($column, $slug)
            ->when($exceptId, fn ($query)=> $query->whereKeyNot($exceptId))
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        
        return $slug;
    }
}
