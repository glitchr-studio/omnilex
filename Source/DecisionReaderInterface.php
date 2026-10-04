<?php

namespace Omnilex\Source;

use Omnilex\Exception\NotSupportedException;
use Omnilex\Exception\ProviderException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Decision;
use Omnilex\Model\Identifier;

/** A source that reads a court's decision. */
interface DecisionReaderInterface extends SourceInterface
{
    /**
     * The decision, or null when unknown.
     *
     * @throws NotSupportedException
     * @throws UnavailableException
     * @throws ProviderException
     */
    public function decision(Identifier|string $id): ?Decision;
}
