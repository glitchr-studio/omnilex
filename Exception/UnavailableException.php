<?php

namespace Omnilex\Exception;

/**
 * The service could not be reached, is down, or refuses for now: never
 * "not found". A caller that caches an answer must not cache this one, and
 * a legal watch must not read it as "nothing new".
 */
class UnavailableException extends ProviderException
{
}
