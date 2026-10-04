<?php

namespace Omnilex\Tests;

use Omnilex\Auth\ClientCredentials;
use Omnilex\Auth\Piste;
use Omnilex\Auth\StaticToken;
use Omnilex\Auth\TokenStoreInterface;
use Omnilex\Exception\AuthenticationException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Token;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/** The token request and answer are those of "Exemples d'utilisation de l'API Légifrance", § 1.1. */
final class ClientCredentialsTest extends TestCase
{
    private const ANSWER = '{"access_token": "th2uv3lq9zY2vAoth59QpYtCSID1iWn0AG6XhnjgAP54eoY1440vp3", "token_type": "Bearer", "expires_in": 3600, "scope": "openid"}';

    public function testTheTokenIsAskedTheOAuthWayAndKept(): void
    {
        $requests = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options['body'], $options['normalized_headers']['content-type'][0] ?? null];

            return new MockResponse(self::ANSWER);
        });
        $credentials = Piste::credentials($http, 'my-client', 'my-secret', sandbox: true);

        $token = $credentials->token();
        self::assertSame('th2uv3lq9zY2vAoth59QpYtCSID1iWn0AG6XhnjgAP54eoY1440vp3', $token->accessToken);
        self::assertSame('Bearer', $token->type);
        self::assertSame('openid', $token->scope);
        self::assertEqualsWithDelta(3600, $token->expiresAt->getTimestamp() - time(), 5);
        self::assertSame($token, $credentials->token(), 'kept while it lives');
        self::assertSame([[
            'POST',
            'https://sandbox-oauth.piste.gouv.fr/api/oauth/token',
            'grant_type=client_credentials&client_id=my-client&client_secret=my-secret&scope=openid',
            'Content-Type: application/x-www-form-urlencoded',
        ]], $requests);

        $credentials->forget();
        self::assertNotSame($token, $credentials->token(), 'asked afresh once forgotten');
        self::assertCount(2, $requests);
        self::assertSame(Piste::TOKEN_URI, 'https://oauth.piste.gouv.fr/api/oauth/token');
    }

    public function testATokenAboutToDieIsReplaced(): void
    {
        $answers = ['{"access_token":"short","expires_in":30}', '{"access_token":"long","expires_in":3600}'];
        $http = new MockHttpClient(function () use (&$answers): MockResponse {
            return new MockResponse(array_shift($answers));
        });
        $credentials = new ClientCredentials($http, 'https://auth.test/token', 'id', 'secret');

        self::assertSame('short', $credentials->token()->accessToken);
        self::assertSame('long', $credentials->token()->accessToken, 'thirty seconds left is within the minute of leeway');
        self::assertSame('long', $credentials->token()->accessToken);
    }

    public function testTheStoreSparesTheAuthorisationServer(): void
    {
        $store = new class implements TokenStoreInterface {
            /** @var array<string, Token> */
            public array $tokens = [];

            public function get(string $key): ?Token
            {
                return $this->tokens[$key] ?? null;
            }

            public function set(string $key, Token $token): void
            {
                $this->tokens[$key] = $token;
            }

            public function delete(string $key): void
            {
                unset($this->tokens[$key]);
            }
        };
        $calls = 0;
        $http = new MockHttpClient(function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse(self::ANSWER);
        });

        $first = Piste::credentials($http, 'my-client', 'my-secret', store: $store)->token();
        // Another request of the application: a new object, the same store.
        $second = Piste::credentials($http, 'my-client', 'my-secret', store: $store)->token();

        self::assertSame(1, $calls);
        self::assertSame($first->accessToken, $second->accessToken);
        self::assertCount(1, $store->tokens);
        self::assertStringNotContainsString('my-secret', array_key_first($store->tokens));

        Piste::credentials($http, 'other-client', 'x', store: $store)->token();
        self::assertSame(2, $calls, 'another client, another token');

        $credentials = Piste::credentials($http, 'my-client', 'my-secret', store: $store);
        $credentials->forget();
        self::assertCount(1, $store->tokens, 'forgotten in the store too');
    }

    public function testRefusedCredentials(): void
    {
        // What PISTE answers to an unknown client.
        $http = new MockHttpClient(new MockResponse('{"error":"invalid_client","error_description":"Client authentication failed (e.g. unknown client, no client authentication included, or unsupported authentication method)."}', ['http_code' => 401]));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('[legifrance] the client id or secret is refused: Client authentication failed');
        Piste::credentials($http, 'nope', 'nope', source: 'legifrance')->token();
    }

    public function testAnAuthorisationServerDownIsUnavailable(): void
    {
        foreach ([new MockResponse('', ['http_code' => 503]), new MockResponse('', ['error' => 'DNS failure'])] as $response) {
            try {
                (new ClientCredentials(new MockHttpClient($response), 'https://auth.test/token', 'id', 'secret'))->token();
                self::fail();
            } catch (UnavailableException $e) {
                self::assertNotInstanceOf(AuthenticationException::class, $e, 'down is not refused');
            }
        }
    }

    public function testAStaticToken(): void
    {
        $token = new StaticToken('abc');
        $token->forget();

        self::assertSame('abc', $token->token()->accessToken);
        self::assertSame('Bearer', $token->token()->type);
    }
}
