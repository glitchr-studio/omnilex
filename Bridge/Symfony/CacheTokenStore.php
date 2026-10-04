<?php

namespace Omnilex\Bridge\Symfony;

use Omnilex\Auth\TokenStoreInterface;
use Omnilex\Model\Token;
use Psr\Cache\CacheItemPoolInterface;

/** Keeps the tokens in the application's cache pool (cache.app), until they die. */
final class CacheTokenStore implements TokenStoreInterface
{
    public function __construct(private readonly CacheItemPoolInterface $cache)
    {
    }

    public function get(string $key): ?Token
    {
        $item = $this->cache->getItem($key);
        $data = $item->isHit() ? $item->get() : null;

        return \is_array($data) && isset($data['access_token']) ? Token::fromArray($data) : null;
    }

    public function set(string $key, Token $token): void
    {
        $item = $this->cache->getItem($key)->set($token->toArray());
        if (null !== $token->expiresAt) {
            $item->expiresAt($token->expiresAt);
        }
        $this->cache->save($item);
    }

    public function delete(string $key): void
    {
        $this->cache->deleteItem($key);
    }
}
