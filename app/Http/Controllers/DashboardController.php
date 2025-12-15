<?php

namespace App\Http\Controllers;

use App\Services\GA4SnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected GA4SnapshotService $snapshotService
    ) {}

    /**
     * Display the dashboard with latest snapshot data.
     */
    public function index(Request $request): Response
    {
        $connection = $request->user()->ga4Connection;

        $snapshot = null;
        $connectionData = null;

        if ($connection) {
            $snapshot = $this->snapshotService->getLatestSnapshot($connection);

            $connectionData = [
                'property_id' => $connection->property_id,
                'property_name' => $connection->property_name,
                'can_generate_report' => $connection->canGenerateReport(),
                'time_until_next_report' => $connection->timeUntilNextReport(),
            ];
        }

        return Inertia::render('dashboard', [
            'connection' => $connectionData,
            'snapshot' => $snapshot ? [
                'data' => $snapshot->snapshot_data,
                'generated_at' => $snapshot->generated_at->toISOString(),
                'is_expired' => $snapshot->isExpired(),
            ] : null,
        ]);
    }

    /**
     * Generate a new snapshot.
     */
    public function generateSnapshot(Request $request): RedirectResponse
    {
        $connection = $request->user()->ga4Connection;

        if (! $connection) {
            return to_route('dashboard')
                ->with('error', 'Please connect your Google Analytics account first.');
        }

        if (! $connection->canGenerateReport()) {
            $timeUntil = $connection->timeUntilNextReport();

            return to_route('dashboard')
                ->with('error', "Rate limit active. You can generate your next report in {$timeUntil['hours']}h {$timeUntil['minutes']}m.");
        }

        try {
            $this->snapshotService->generate($connection);

            return to_route('dashboard')
                ->with('success', 'Report generated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to generate GA4 snapshot', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return to_route('dashboard')
                ->with('error', 'Failed to generate report. Please try again later.');
        }
    }
}
