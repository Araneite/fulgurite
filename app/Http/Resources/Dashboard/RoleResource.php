<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'name'=> $this->name,
            'level'=> $this->level,
            'project'=> $this->whenLoaded("project", function () {
                return new ProjectResource($this->project);
            }),
            'permissions'=> [
                'assign'=> $request->user()->can('assign', $this->resource),
                'view'=> $request->user()->can('view', $this->resource),
                'update'=> $request->user()->can('update', $this->resource),
                'delete'=> $request->user()->can('delete', $this->resource),
            ],
        ];
    }
}
