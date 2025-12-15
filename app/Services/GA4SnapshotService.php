<?php

namespace App\Services;

use App\Models\GA4Connection;
use App\Models\GA4Snapshot;

class GA4SnapshotService
{
    public function __construct(
        protected GA4Service $ga4Service
    ) {}

    /**
     * Generate a new snapshot for the given connection.
     *
     * @throws \Exception
     */
    public function generate(GA4Connection $connection): GA4Snapshot
    {
        $accessToken = $this->ga4Service->getAccessTokenForConnection($connection);

        $demographicsData = $this->ga4Service->getDemographics(
            $connection->property_id,
            $accessToken
        );

        $snapshot = GA4Snapshot::create([
            'connection_id' => $connection->id,
            'snapshot_data' => $demographicsData,
            'generated_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $connection->markReportGenerated();

        return $snapshot;
    }

    /**
     * Get the latest snapshot for a connection.
     */
    public function getLatestSnapshot(GA4Connection $connection): ?GA4Snapshot
    {
        return $connection->latestSnapshot;
    }
}
