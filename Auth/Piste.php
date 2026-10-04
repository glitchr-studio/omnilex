<?php

namespace Omnilex\Auth;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * PISTE (piste.gouv.fr), the French State's API portal, in front of the
 * Légifrance and Judilibre APIs: an application declared there gets a
 * client id and a client secret, one pair for its sandbox and one for
 * production, and signs in with the OAuth client-credentials flow.
 */
final class Piste
{
    public const TOKEN_URI = 'https://oauth.piste.gouv.fr/api/oauth/token';
    public const SANDBOX_TOKEN_URI = 'https://sandbox-oauth.piste.gouv.fr/api/oauth/token';
    public const SCOPE = 'openid';

    public static function credentials(
        HttpClientInterface $http,
        string $clientId,
        #[\SensitiveParameter] string $clientSecret,
        bool $sandbox = false,
        ?TokenStoreInterface $store = null,
        string $source = 'piste',
        ?string $tokenUri = null,
    ): ClientCredentials {
        return new ClientCredentials($http, $tokenUri ?? ($sandbox ? self::SANDBOX_TOKEN_URI : self::TOKEN_URI), $clientId, $clientSecret, self::SCOPE, $store, $source);
    }
}
