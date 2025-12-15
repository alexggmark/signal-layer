<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GA4Connection>
 */
class GA4ConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'property_id' => 'properties/'.$this->faker->numberBetween(100000000, 999999999),
            'property_name' => $this->faker->company().' Website',
            'refresh_token' => $this->faker->sha256(),
            'last_report_generated_at' => null,
            'connected_at' => now(),
        ];
    }

    /**
     * Indicate that the connection has recently generated a report.
     */
    public function recentlyGeneratedReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_report_generated_at' => now()->subHours(2),
        ]);
    }

    /**
     * Indicate that the connection can generate a report.
     */
    public function canGenerateReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_report_generated_at' => now()->subHours(6),
        ]);
    }
}
