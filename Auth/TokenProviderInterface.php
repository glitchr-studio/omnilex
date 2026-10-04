<?php

namespace Omnilex\Auth;

use Omnilex\Exception\AuthenticationException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Token;

/** What signs a source's calls: a token that is valid now. */
interface TokenProviderInterface
{
    /**
     * @throws AuthenticationException when the credentials are refused
     * @throws UnavailableException    when the authorisation server cannot be reached
     */
    public function token(): Token;

    /** Drops the token in hand: the next token() asks for a fresh one (after a 401). */
    public function forget(): void;
}
