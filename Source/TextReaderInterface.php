<?php

namespace Omnilex\Source;

use Omnilex\Exception\NotSupportedException;
use Omnilex\Exception\ProviderException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Identifier;
use Omnilex\Model\Text;

/** A source that reads a whole text. */
interface TextReaderInterface extends SourceInterface
{
    /**
     * The text as in force on that day (today when null), or null when the
     * source does not know it, or knows no version of it for that day.
     *
     * @throws NotSupportedException when the source cannot read that kind of identifier
     * @throws UnavailableException
     * @throws ProviderException
     */
    public function text(Identifier|string $id, ?\DateTimeInterface $at = null): ?Text;
}
