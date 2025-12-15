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
                // Demographics (existing)
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
                // CRO Reports
                'funnel' => [
                    ['step' => 'Session Start', 'count' => 10000, 'dropOffRate' => 0.0],
                    ['step' => 'View Item', 'count' => 4500, 'dropOffRate' => 55.0],
                    ['step' => 'Add to Cart', 'count' => 1800, 'dropOffRate' => 60.0],
                    ['step' => 'Begin Checkout', 'count' => 900, 'dropOffRate' => 50.0],
                    ['step' => 'Purchase', 'count' => 450, 'dropOffRate' => 50.0],
                ],
                'exitRates' => [
                    ['page' => '/', 'exits' => 800, 'pageviews' => 3000, 'exitRate' => 26.7],
                    ['page' => '/products', 'exits' => 450, 'pageviews' => 2000, 'exitRate' => 22.5],
                    ['page' => '/cart', 'exits' => 350, 'pageviews' => 1000, 'exitRate' => 35.0],
                    ['page' => '/checkout', 'exits' => 200, 'pageviews' => 800, 'exitRate' => 25.0],
                    ['page' => '/about', 'exits' => 180, 'pageviews' => 600, 'exitRate' => 30.0],
                ],
                'scrollDepth' => [
                    ['page' => '/', 'scrolled90Percent' => 1200, 'totalViews' => 3000, 'scrollRate' => 40.0],
                    ['page' => '/products', 'scrolled90Percent' => 900, 'totalViews' => 2000, 'scrollRate' => 45.0],
                    ['page' => '/blog/post-1', 'scrolled90Percent' => 400, 'totalViews' => 800, 'scrollRate' => 50.0],
                    ['page' => '/about', 'scrolled90Percent' => 350, 'totalViews' => 600, 'scrollRate' => 58.3],
                ],
                'landingPages' => [
                    'desktop' => [
                        ['page' => '/', 'sessions' => 800, 'conversions' => 40, 'conversionRate' => 5.0],
                        ['page' => '/products', 'sessions' => 400, 'conversions' => 28, 'conversionRate' => 7.0],
                        ['page' => '/blog/post-1', 'sessions' => 200, 'conversions' => 8, 'conversionRate' => 4.0],
                    ],
                    'mobile' => [
                        ['page' => '/', 'sessions' => 600, 'conversions' => 24, 'conversionRate' => 4.0],
                        ['page' => '/products', 'sessions' => 250, 'conversions' => 12, 'conversionRate' => 4.8],
                        ['page' => '/sale', 'sessions' => 150, 'conversions' => 9, 'conversionRate' => 6.0],
                    ],
                ],
                'productPages' => [
                    'purchasers' => ['avgDuration' => 185.5, 'sessions' => 450],
                    'nonPurchasers' => ['avgDuration' => 95.2, 'sessions' => 2050],
                ],
                'pagesBuckets' => [
                    ['bucket' => '1-2', 'sessions' => 875, 'conversions' => 10, 'conversionRate' => 1.14],
                    ['bucket' => '3-5', 'sessions' => 750, 'conversions' => 26, 'conversionRate' => 3.47],
                    ['bucket' => '6-10', 'sessions' => 550, 'conversions' => 36, 'conversionRate' => 6.55],
                    ['bucket' => '11+', 'sessions' => 325, 'conversions' => 30, 'conversionRate' => 9.23],
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
