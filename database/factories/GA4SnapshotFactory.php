<?php

namespace Database\Factories;

use App\Models\GA4Connection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GA4Snapshot>
 */
class GA4SnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'connection_id' => GA4Connection::factory(),
            'snapshot_data' => [
                'devices' => [
                    ['category' => 'desktop', 'sessions' => 1500, 'percentage' => 60.0],
                    ['category' => 'mobile', 'sessions' => 900, 'percentage' => 36.0],
                    ['category' => 'tablet', 'sessions' => 100, 'percentage' => 4.0],
                ],
                'channels' => [
                    ['channel' => 'Organic Search', 'sessions' => 1200, 'conversions' => 48, 'conversionRate' => 4.0],
                    ['channel' => 'Paid Search', 'sessions' => 600, 'conversions' => 36, 'conversionRate' => 6.0],
                    ['channel' => 'Direct', 'sessions' => 400, 'conversions' => 12, 'conversionRate' => 3.0],
                    ['channel' => 'Social', 'sessions' => 300, 'conversions' => 6, 'conversionRate' => 2.0],
                ],
                'totals' => [
                    'sessions' => 2500,
                    'conversions' => 102,
                    'conversionRate' => 4.08,
                ],
                'dateRange' => [
                    'startDate' => now()->subDays(30)->toDateString(),
                    'endDate' => now()->toDateString(),
                ],
            ],
            'generated_at' => now(),
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the snapshot has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
