<?php

namespace Omnilex\Model;

/** The legal state of a version of a text or of an article. */
enum Status: string
{
    case IN_FORCE = 'in_force';
    /** Adopted, comes into force later. */
    case FUTURE = 'future';
    /** Replaced by a later version. */
    case MODIFIED = 'modified';
    case REPEALED = 'repealed';
    case ANNULLED = 'annulled';
    /** No longer applicable without having been repealed (lapsed, transferred, replaced). */
    case EXPIRED = 'expired';
    case UNKNOWN = 'unknown';

    public function isInForce(): bool
    {
        return self::IN_FORCE === $this;
    }
}
