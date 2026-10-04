<?php

namespace Omnilex\Model;

/** One article, in one version: the one in force at the date asked for. */
final readonly class Article
{
    /**
     * @param list<Version>        $versions  every version the source knows of the article, oldest first
     * @param list<Citation>       $citations
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $id,
        public ?string $number,
        /** Plain text. */
        public string $content,
        public ?string $html = null,
        public ?string $title = null,
        public Identifiers $identifiers = new Identifiers(),
        public ?Version $version = null,
        public array $versions = [],
        public ?string $textId = null,
        public ?string $textTitle = null,
        /** The headings above the article: "Partie législative > Livre Ier > Titre II". */
        public ?string $path = null,
        public ?string $note = null,
        public array $citations = [],
        public ?string $url = null,
        public string $source = '',
        public array $raw = [],
    ) {
    }

    public function isInForce(): bool
    {
        return true === $this->version?->status->isInForce();
    }
}
