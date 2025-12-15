<?php

namespace App\Services;

use Google\Auth\FetchAuthTokenInterface;

class GoogleAccessTokenCredentials implements FetchAuthTokenInterface
{
    public function __construct(
        protected string $accessToken
    ) {}

    /**
     * Fetches the auth tokens based on the current state.
     *
     * @return array<string, mixed>
     */
    public function fetchAuthToken(?callable $httpHandler = null): array
    {
        return [
            'access_token' => $this->accessToken,
        ];
    }

    /**
     * Returns the cache key.
     */
    public function getCacheKey(): string
    {
        return '';
    }

    /**
     * Returns the last received access token.
     */
    public function getLastReceivedToken(): ?array
    {
        return [
            'access_token' => $this->accessToken,
            'expires_at' => time() + 3600,
        ];
    }
}
