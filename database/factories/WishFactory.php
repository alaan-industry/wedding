<?php

namespace Database\Factories;

use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wish>
 */
class WishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => null,
            'name' => fake()->name(),
            'message' => fake()->sentence(12),
            'is_shared' => true,
            'is_hidden' => false,
            'hearts_count' => 0,
        ];
    }

    /**
     * Indicate that the guest chose not to share the wish on the public wall.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_shared' => false,
        ]);
    }

    /**
     * Indicate that the couple hid the wish from the public wall.
     */
    public function hiddenByCouple(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
