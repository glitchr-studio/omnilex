<?php

namespace Omnilex\Model;

/**
 * A court's decision. $pseudonymised says whether the source publishes it
 * with the names of the persons removed (Judilibre, the administrative
 * courts' open data): Omnilex gives such a text as it is and never tries to
 * put the names back; a site that shows it must not either. Null when the
 * source does not say.
 */
final readonly class Decision
{
    /**
     * @param list<string>         $numbers   every case number, the main one first
     * @param list<string>         $subjects  the headings the court filed it under (titrage, themes)
     * @param list<Citation>       $citations texts applied, decisions contested or brought together
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $id,
        public ?string $title = null,
        public Identifiers $identifiers = new Identifiers(),
        public ?Court $court = null,
        public ?\DateTimeImmutable $date = null,
        /** The main case number: pourvoi, requête, affaire. */
        public ?string $number = null,
        public array $numbers = [],
        /** The source's own nature: "arret", "Ordonnance", "JUDG"... */
        public ?string $type = null,
        public ?string $solution = null,
        /** How the court published it: bulletin, recueil Lebon... */
        public ?string $publication = null,
        public ?string $summary = null,
        public array $subjects = [],
        /** Plain text. */
        public ?string $content = null,
        public ?string $html = null,
        public array $citations = [],
        public ?bool $pseudonymised = null,
        public ?\DateTimeImmutable $updatedOn = null,
        /** ISO 639-1. */
        public ?string $language = null,
        public ?string $url = null,
        public string $source = '',
        public array $raw = [],
    ) {
    }

    public function ecli(): ?string
    {
        return $this->identifiers->value(Scheme::ECLI);
    }
}
