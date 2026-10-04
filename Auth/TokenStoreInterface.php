<?php

namespace Omnilex\Auth;

use Omnilex\Model\Token;

/**
 * Where a token is kept between two requests of an application, so that
 * each does not ask the authorisation server for a new one (a PISTE token
 * lives an hour). The Symfony bridge gives one over the application's cache.
 */
interface TokenStoreInterface
{
    public function get(string $key): ?Token;

    public function set(string $key, Token $token): void;

    public function delete(string $key): void;
}
