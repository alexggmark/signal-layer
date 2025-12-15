<?php

namespace App\Http\Controllers;

use App\Http\Requests\GA4\SelectPropertyRequest;
use App\Models\GA4Connection;
use App\Services\GA4Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class GA4Controller extends Controller
{
    public function __construct(protected GA4Service $ga4Service) {}

    /**
     * Show the GA4 connection page.
     */
    public function index(Request $request): Response
    {
        $connection = $request->user()->ga4Connection;

        return Inertia::render('ga4/index', [
            'connection' => $connection ? [
                'property_id' => $connection->property_id,
                'property_name' => $connection->property_name,
                'connected_at' => $connection->connected_at->toISOString(),
                'can_generate_report' => $connection->canGenerateReport(),
                'time_until_next_report' => $connection->timeUntilNextReport(),
            ] : null,
        ]);
    }

    /**
     * Initiate the OAuth flow to connect GA4.
     */
    public function connect(): RedirectResponse
    {
        $authUrl = $this->ga4Service->getAuthUrl();

        return redirect()->away($authUrl);
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            Log::warning('GA4 OAuth error', ['error' => $request->get('error')]);

            return to_route('ga4.index')
                ->with('error', 'Failed to connect to Google Analytics. Please try again.');
        }

        $code = $request->get('code');

        if (! $code) {
            return to_route('ga4.index')
                ->with('error', 'No authorization code received from Google.');
        }

        try {
            $tokens = $this->ga4Service->exchangeCodeForTokens($code);

            session([
                'ga4_access_token' => $tokens['access_token'],
                'ga4_refresh_token' => $tokens['refresh_token'],
            ]);

            return to_route('ga4.select-property');
        } catch (\Exception $e) {
            Log::error('GA4 token exchange failed', ['error' => $e->getMessage()]);

            return to_route('ga4.index')
                ->with('error', 'Failed to authenticate with Google Analytics. Please try again.');
        }
    }

    /**
     * Show the property selection page.
     */
    public function selectProperty(Request $request): Response|RedirectResponse
    {
        $accessToken = session('ga4_access_token');

        if (! $accessToken) {
            return to_route('ga4.connect');
        }

        try {
            $properties = $this->ga4Service->getProperties($accessToken);

            return Inertia::render('ga4/select-property', [
                'properties' => $properties,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch GA4 properties', ['error' => $e->getMessage()]);

            return to_route('ga4.index')
                ->with('error', 'Failed to fetch your Google Analytics properties. Please try again.');
        }
    }

    /**
     * Store the selected property and complete the connection.
     */
    public function storeProperty(SelectPropertyRequest $request): RedirectResponse
    {
        $refreshToken = session('ga4_refresh_token');

        if (! $refreshToken) {
            return to_route('ga4.connect');
        }

        $validated = $request->validated();

        $request->user()->ga4Connection()->delete();

        GA4Connection::create([
            'user_id' => $request->user()->id,
            'property_id' => $validated['property_id'],
            'property_name' => $validated['property_name'],
            'refresh_token' => $refreshToken,
            'connected_at' => now(),
        ]);

        session()->forget(['ga4_access_token', 'ga4_refresh_token']);

        return to_route('ga4.index')
            ->with('success', 'Google Analytics connected successfully!');
    }

    /**
     * Disconnect the GA4 integration.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->ga4Connection()->delete();

        return to_route('ga4.index')
            ->with('success', 'Google Analytics disconnected successfully.');
    }
}
