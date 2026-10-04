<?php

namespace Omnilex\Source;

use Omnilex\Exception\NotSupportedException;
use Omnilex\Exception\ProviderException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Query;
use Omnilex\Model\Results;

/** A source that lists what is new: what a legal watch asks for. */
interface RecentInterface extends SourceInterface
{
    /**
     * The documents new since that day, newest first, one page at a time.
     * Each source says which date it reads (published, added to the base,
     * decided). The query narrows the list (kind, jurisdictions, types...);
     * its from, to and sort are not read.
     *
     * @throws NotSupportedException when the query sets a criterion the source cannot apply
     * @throws UnavailableException  never an empty page: a watch must not read "down" as "nothing new"
     * @throws ProviderException
     */
    public function recent(\DateTimeInterface $since, ?Query $query = null): Results;
}
