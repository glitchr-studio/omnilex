<?php

namespace Omnilex\Model;

/**
 * The court that gave a decision. $code is the source's own key ("cc",
 * "CE", "TA75", "CJ"), the one Query::$jurisdictions takes for that source.
 */
final readonly class Court
{
    public function __construct(
        public string $name,
        public ?string $code = null,
        /** "FR", "EU" */
        public ?string $country = null,
        public ?string $chamber = null,
        public ?string $formation = null,
        /** The seat, for the courts that have several: "Cour d'appel de Nancy". */
        public ?string $location = null,
    ) {
    }
}
