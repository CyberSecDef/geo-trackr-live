<?php

namespace Database\Factories;

use App\Models\Treasure;
use App\Services\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treasure>
 */
class TreasureFactory extends Factory
{
    protected $model = Treasure::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'code' => app(CodeGenerator::class)->random(),
            'message' => $this->faker->sentence(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'created_accuracy_m' => $this->faker->randomFloat(1, 3, 30),
            'status' => 'active',
        ];
    }

    public function paused(): static
    {
        return $this->state(fn () => ['status' => 'paused']);
    }
}
