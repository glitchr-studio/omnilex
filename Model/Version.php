<?php

namespace Omnilex\Model;

/**
 * One version of a text or of an article: the dates between which it
 * applies ($to is the last day, null when no end is known), its legal state,
 * and the identifier that reads it again.
 */
final readonly class Version
{
    public function __construct(
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        public Status $status = Status::UNKNOWN,
        public ?string $id = null,
        public ?string $label = null,
        public ?string $url = null,
    ) {
    }

    /** Whether the version applies on that day (an unknown start never does). */
    public function covers(\DateTimeInterface $date): bool
    {
        $day = $date->format('Y-m-d');

        return null !== $this->from && $this->from->format('Y-m-d') <= $day && (null === $this->to || $day <= $this->to->format('Y-m-d'));
    }

    /**
     * The version that applies on that day, among several.
     *
     * @param iterable<Version> $versions
     */
    public static function at(iterable $versions, \DateTimeInterface $date): ?self
    {
        $found = null;
        foreach ($versions as $version) {
            if ($version->covers($date) && (null === $found || $version->from > $found->from)) {
                $found = $version;
            }
        }

        return $found;
    }
}
