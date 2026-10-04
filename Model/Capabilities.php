<?php

namespace Omnilex\Model;

use Omnilex\Source\ArticleReaderInterface;
use Omnilex\Source\CitationsInterface;
use Omnilex\Source\DecisionReaderInterface;
use Omnilex\Source\RecentInterface;
use Omnilex\Source\SearchInterface;
use Omnilex\Source\SourceInterface;
use Omnilex\Source\TextReaderInterface;

/**
 * What a source holds and what it can be asked. The operations are the
 * interfaces it implements (operations() lists them); this says the rest:
 * the kinds of documents, the identifiers it reads, the criteria its search
 * applies.
 */
final readonly class Capabilities
{
    public const OPERATIONS = [
        'search' => SearchInterface::class,
        'text' => TextReaderInterface::class,
        'article' => ArticleReaderInterface::class,
        'decision' => DecisionReaderInterface::class,
        'citations' => CitationsInterface::class,
        'recent' => RecentInterface::class,
    ];

    /**
     * @param list<Kind>   $kinds         what the source holds
     * @param list<Scheme> $identifiers   the identifiers it reads, besides its own
     * @param list<string> $criteria      the Query criteria its search applies (Query::CRITERIA)
     * @param list<string> $jurisdictions the countries or legal orders it covers: "FR", "EU"
     * @param bool         $versions      whether it reads a text or an article as in force at a date
     * @param int|null     $pageSize      the most results it gives per page
     * @param bool         $pseudonymised whether its decisions come with the names of the persons removed
     */
    public function __construct(
        public array $kinds = [],
        public array $identifiers = [],
        public array $criteria = [],
        public array $jurisdictions = [],
        public bool $versions = false,
        public ?int $pageSize = null,
        public bool $pseudonymised = false,
    ) {
    }

    public function holds(Kind $kind): bool
    {
        return \in_array($kind, $this->kinds, true);
    }

    public function reads(Scheme $scheme): bool
    {
        return \in_array($scheme, $this->identifiers, true);
    }

    public function filters(string $criterion): bool
    {
        return \in_array($criterion, $this->criteria, true);
    }

    /**
     * The operations a source implements: search, text, article, decision, citations, recent.
     *
     * @return list<string>
     */
    public static function operations(SourceInterface $source): array
    {
        return array_keys(array_filter(self::OPERATIONS, static fn (string $interface) => $source instanceof $interface));
    }
}
