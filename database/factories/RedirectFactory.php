<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Redirect>
 */
class RedirectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'url' => fake()->url(),
            'title' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'is_active' => true,
            'clicks' => 0,
        ];
    }

    /**
     * Indicate that the redirect is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the redirect has been clicked.
     */
    public function withClicks(?int $clicks = null): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => $clicks ?? fake()->numberBetween(1, 1000),
        ]);
    }
}
