<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name'=> $this->faker->name(),
            'slug'=> $this->faker->slug(),
            'description'=> $this->faker->text(),
            'status_page_enabled'=> $this->faker->boolean(),
            'customer_id'=> null,
            "created_at" => $this->faker->dateTime(),
            "updated_at" => $this->faker->dateTime(),
            'deleted_at' => $this->faker->dateTime(),
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => 1,
        ];
    }
}
