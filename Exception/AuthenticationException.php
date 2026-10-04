<?php

namespace Omnilex\Exception;

/**
 * The service refuses the credentials: a client id or secret that is wrong,
 * an application not subscribed to the API, terms of use not accepted on
 * the portal. Not "not found" either.
 */
final class AuthenticationException extends UnavailableException
{
}
