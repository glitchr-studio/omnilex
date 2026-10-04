<?php

namespace Omnilex\Model;

/** A link from a document to another: the relation, read from the document asked for, and where it leads. */
final readonly class Citation
{
    public function __construct(
        public Relation $relation,
        public Reference $target,
        /** What the source says of the link: its own type, the provision concerned. */
        public ?string $note = null,
    ) {
    }
}
