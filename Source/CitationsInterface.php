<?php

namespace Omnilex\Source;

use Omnilex\Exception\NotSupportedException;
use Omnilex\Exception\ProviderException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Citation;
use Omnilex\Model\Identifier;

/** A source that follows the links between decisions and texts. */
interface CitationsInterface extends SourceInterface
{
    /**
     * The links of a document, both ways: what it cites, applies or amends,
     * and what cites, interprets or amends it. At most $limit in each
     * direction, in the source's order (newest first where it dates them).
     * Empty when the document has none, or is unknown.
     *
     * @return list<Citation>
     *
     * @throws NotSupportedException
     * @throws UnavailableException
     * @throws ProviderException
     */
    public function citations(Identifier|string $id, int $limit = 100): array;
}
