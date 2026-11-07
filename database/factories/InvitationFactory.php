<?php

namespace Database\Factories;

use App\Enums\RsvpStatus;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->optional()->numerify('09#########'),
            'seats' => fake()->numberBetween(1, 4),
            'status' => RsvpStatus::Pending,
            'attending_count' => 0,
        ];
    }

    /**
     * Indicate that the guest accepted the invitation.
     */
    public function attending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RsvpStatus::Attending,
            'attending_count' => fn (array $attributes) => $attributes['seats'],
            'guest_names' => fn (array $attributes) => collect(range(1, $attributes['seats']))->map(fn () => fake()->name())->all(),
            'opened_at' => now(),
            'responded_at' => now(),
        ]);
    }

    /**
     * Indicate that the guest declined the invitation.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RsvpStatus::Declined,
            'attending_count' => 0,
            'opened_at' => now(),
            'responded_at' => now(),
        ]);
    }
}
