<?php

namespace Omnilex\Source;

use Omnilex\Exception\NotSupportedException;
use Omnilex\Exception\ProviderException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Query;
use Omnilex\Model\Results;

/** A source that searches its corpus. */
interface SearchInterface extends SourceInterface
{
    /**
     * The documents matching the query, one page at a time.
     *
     * @throws NotSupportedException when the query sets a criterion the source cannot apply
     * @throws UnavailableException
     * @throws ProviderException
     */
    public function search(Query $query): Results;
}
