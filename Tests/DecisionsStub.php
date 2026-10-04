<?php

namespace Omnilex\Tests;

use Omnilex\Model\Capabilities;
use Omnilex\Model\Decision;
use Omnilex\Model\Identifier;
use Omnilex\Model\Kind;
use Omnilex\Model\Query;
use Omnilex\Model\Results;
use Omnilex\Model\Scheme;
use Omnilex\Source\DecisionReaderInterface;
use Omnilex\Source\RecentInterface;

/** A source that only reads decisions and lists the new ones: no HTTP. */
final class DecisionsStub implements DecisionReaderInterface, RecentInterface
{
    public function getName(): string
    {
        return 'decisions';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(kinds: [Kind::DECISION], identifiers: [Scheme::ECLI], pseudonymised: true);
    }

    public function decision(Identifier|string $id): ?Decision
    {
        return new Decision((string) $id, pseudonymised: true, source: 'decisions');
    }

    public function recent(\DateTimeInterface $since, ?Query $query = null): Results
    {
        return new Results();
    }
}
