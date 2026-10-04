<?php

namespace Omnilex\Model;

/**
 * A text - a code, a law, a decree, a regulation, a directive - in one
 * version: the one in force at the date asked for.
 */
final readonly class Text
{
    /**
     * @param list<Version>        $versions  every version the source lists, oldest first (empty when it lists none)
     * @param list<Article>        $articles  in the text's order, when the source gives them apart
     * @param list<string>         $subjects
     * @param list<Citation>       $citations
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $id,
        public string $title,
        public Identifiers $identifiers = new Identifiers(),
        /** The source's own nature: "CODE", "LOI", "DECRET", "REG", "DIR"... */
        public ?string $type = null,
        /** The date of the text: its signature, its adoption. */
        public ?\DateTimeImmutable $date = null,
        public ?\DateTimeImmutable $publishedOn = null,
        public ?Version $version = null,
        public array $versions = [],
        /** Plain text of the whole version, when the source gives it. */
        public ?string $content = null,
        public ?string $html = null,
        public array $articles = [],
        public array $subjects = [],
        public array $citations = [],
        /** ISO 639-1. */
        public ?string $language = null,
        public ?string $url = null,
        public string $source = '',
        public array $raw = [],
    ) {
    }

    public function isInForce(): bool
    {
        return true === $this->version?->status->isInForce();
    }

    /** The article of that number, among those the source gave. */
    public function article(string $number): ?Article
    {
        $wanted = self::number($number);
        foreach ($this->articles as $article) {
            if (null !== $article->number && self::number($article->number) === $wanted) {
                return $article;
            }
        }

        return null;
    }

    /** "L. 36-11", "L36-11" and "l 36 11" name the same article. */
    public static function number(string $number): string
    {
        return strtoupper((string) preg_replace('~[\s.\-]+~u', '', $number));
    }
}
