<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait LoadRelations
{
    /**
     * Load dynamically some relations depending on request parameters and available relation for the model
     *
     * @param Model $model
     * @param Request $request
     * @param array $allowedRelations
     * @return Model
     */
    public function loadRelations(Model $model, Request $request, array $allowedRelations)
    {
        // Get requested relations from URL
        $requestedRelations = $request->query();

        // Filter requested relations to keep  only those available
        $validRelations = array_intersect(array_keys($requestedRelations), $allowedRelations);

        // If there are valid relations to load
        if (!empty($validRelations)) {
            if ($model instanceof Model) {
                // Load relations for a single model
                $model->load($validRelations);
            } elseif ($model instanceof Collection) {
                // Load relations for a collection
                $modelClass = get_class($modelOrCollection->first()); // Get the model class
                $model = $modelClass::with($validRelations)
                    ->whereIn('id', $model->pluck('id'))
                    ->get();
            }
        }

        return $modelOrCollection;
    }
}
