<?php

namespace Omnilex\Exception;

/**
 * The source does not do that: an operation it has no interface for, an
 * identifier it cannot read, a search criterion it cannot apply. A source
 * refuses a criterion rather than answer unfiltered: in law, a search that
 * silently drops its jurisdiction or its date is a wrong answer.
 */
final class NotSupportedException extends \LogicException implements OmnilexException
{
    public static function identifier(string $source, string $identifier, string $what = 'look up'): self
    {
        return new self(\sprintf('The "%s" source cannot %s "%s".', $source, $what, $identifier));
    }

    public static function operation(string $source, string $operation): self
    {
        return new self(\sprintf('The "%s" source does not %s.', $source, $operation));
    }

    public static function criterion(string $source, string $criterion, ?string $why = null): self
    {
        return new self(\sprintf('The "%s" source cannot filter by %s%s.', $source, $criterion, $why ? ': '.$why : ''));
    }
}
