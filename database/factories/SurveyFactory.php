<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey>
 */
class SurveyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image' => $this->faker->imageUrl(),
            'title' => $this->faker->sentence(),
            'slug' => Str::slug($this->faker->sentence(), '-'),
            'status' => $this->faker->randomElement([1,0]),
            'description' => $this->faker->paragraph(),
            'created_at' => now(),
            'updated_at' => now(),
            'expire_date' => now()->addDays(rand(1, 30)),
        ];
    }
}
