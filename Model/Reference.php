<?php

namespace Omnilex\Model;

/**
 * A pointer to a text, an article or a decision: what a search lists and
 * what a citation leads to. $id is the source's own identifier, the one its
 * text(), article() or decision() reads; $identifiers hold the normalised
 * ones (ECLI, CELEX, NOR...).
 */
final readonly class Reference
{
    /** @param array<string, mixed> $raw what the source answered, for what the model does not carry */
    public function __construct(
        public Kind $kind,
        public string $id,
        public string $title,
        public Identifiers $identifiers = new Identifiers(),
        public ?\DateTimeImmutable $date = null,
        /** The source's own nature: "LOI", "REG", "arret"... */
        public ?string $type = null,
        public ?string $number = null,
        public ?Court $court = null,
        public ?Status $status = null,
        /** An excerpt or a summary, as plain text. */
        public ?string $summary = null,
        public ?string $url = null,
        public string $source = '',
        public array $raw = [],
    ) {
    }
}
