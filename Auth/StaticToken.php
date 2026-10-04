<?php

namespace Omnilex\Auth;

use Omnilex\Model\Token;

/** A token the application obtained itself. */
final class StaticToken implements TokenProviderInterface
{
    private readonly Token $token;

    public function __construct(#[\SensitiveParameter] Token|string $token)
    {
        $this->token = $token instanceof Token ? $token : new Token($token);
    }

    public function token(): Token
    {
        return $this->token;
    }

    public function forget(): void
    {
    }
}
