<?php

namespace App\Services;

use App\Models\GA4Connection;
use Google\Analytics\Admin\V1beta\Client\AnalyticsAdminServiceClient;
use Google\Analytics\Admin\V1beta\ListAccountSummariesRequest;
use Illuminate\Support\Facades\Http;

class GA4Service
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id') ?? '';
        $this->clientSecret = config('services.google.client_secret') ?? '';
        $this->redirectUri = config('services.google.redirect_uri') ?? '';
    }

    /**
     * Generate the OAuth authorization URL for Google.
     */
    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/analytics.readonly',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }

    /**
     * Exchange an authorization code for tokens.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     *
     * @throws \Exception
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to exchange code for tokens: '.$response->body());
        }

        $data = $response->json();

        if (! isset($data['refresh_token'])) {
            throw new \Exception('No refresh token received. User may need to revoke access and reconnect.');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => $data['expires_in'],
        ];
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @return array{access_token: string, expires_in: int}
     *
     * @throws \Exception
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to refresh access token: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],
        ];
    }

    /**
     * Get an access token for a GA4 connection (refreshes if needed).
     */
    public function getAccessTokenForConnection(GA4Connection $connection): string
    {
        $tokens = $this->refreshAccessToken($connection->refresh_token);

        return $tokens['access_token'];
    }

    /**
     * Fetch the list of GA4 properties the user has access to.
     *
     * @return array<int, array{property_id: string, property_name: string, account_name: string}>
     */
    public function getProperties(string $accessToken): array
    {
        $client = new AnalyticsAdminServiceClient([
            'credentials' => $this->createCredentialsFromToken($accessToken),
        ]);

        $properties = [];

        try {
            $request = new ListAccountSummariesRequest;
            $accountSummaries = $client->listAccountSummaries($request);

            foreach ($accountSummaries as $accountSummary) {
                $accountName = $accountSummary->getDisplayName();

                foreach ($accountSummary->getPropertySummaries() as $propertySummary) {
                    $properties[] = [
                        'property_id' => $propertySummary->getProperty(),
                        'property_name' => $propertySummary->getDisplayName(),
                        'account_name' => $accountName,
                    ];
                }
            }
        } finally {
            $client->close();
        }

        return $properties;
    }

    /**
     * Create credentials array from an access token.
     *
     * @return array<string, mixed>
     */
    protected function createCredentialsFromToken(string $accessToken): array
    {
        return [
            'access_token' => $accessToken,
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ];
    }
}
