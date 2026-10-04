<?php

namespace Omnilex\Model;

/**
 * What to ask search() and recent() for. Every criterion is optional. A
 * source applies those it can (its Capabilities::$criteria name them) and
 * refuses the others with a NotSupportedException rather than answer
 * unfiltered - except $sort, a preference: a source that cannot sort that
 * way keeps its own order.
 *
 *   new Query(text: 'responsabilité du fait des choses', kind: Kind::DECISION, jurisdictions: ['cc'], from: '2020-01-01')
 *   new Query(text: 'responsabilité', kind: Kind::ARTICLE, subjects: ['Code civil'], at: '2018-01-01')
 *   $query->with(['cursor' => $results->next])
 */
final readonly class Query
{
    public const RELEVANCE = 'relevance';
    public const NEWEST = 'newest';
    public const OLDEST = 'oldest';

    /** The criteria, as Capabilities::$criteria names them. */
    public const CRITERIA = ['text', 'title', 'number', 'kind', 'jurisdictions', 'from', 'to', 'subjects', 'types', 'at'];

    public ?\DateTimeImmutable $from;
    public ?\DateTimeImmutable $to;
    public ?\DateTimeImmutable $at;

    /**
     * @param string|null                    $text          free text; in double quotes, the exact expression where the source can
     * @param string|null                    $title         words of the title
     * @param string|null                    $number        a number: of a text (2019-290), of an article (L36-11), of a case (17-18.194)
     * @param list<string>                   $jurisdictions the source's own codes: a court, an order of courts
     * @param \DateTimeInterface|string|null $from          dated from that day (a decision's date, a text's signature)
     * @param \DateTimeInterface|string|null $to            dated up to that day
     * @param list<string>                   $subjects      the source's own subject headings (matière): a code's name, a theme, a subject code
     * @param list<string>                   $types         the source's own natures: LOI, DECRET, arret, REG...
     * @param \DateTimeInterface|string|null $at            texts and articles as in force on that day
     * @param int                            $limit         results per page (each source caps it)
     * @param string|null                    $cursor        the Results::$next of the page before
     */
    public function __construct(
        public ?string $text = null,
        public ?string $title = null,
        public ?string $number = null,
        public ?Kind $kind = null,
        public array $jurisdictions = [],
        \DateTimeInterface|string|null $from = null,
        \DateTimeInterface|string|null $to = null,
        public array $subjects = [],
        public array $types = [],
        \DateTimeInterface|string|null $at = null,
        public int $limit = 25,
        public ?string $cursor = null,
        public string $sort = self::RELEVANCE,
    ) {
        $this->from = self::day($from);
        $this->to = self::day($to);
        $this->at = self::day($at);
    }

    public function with(array $changes): self
    {
        return new self(...array_merge(get_object_vars($this), $changes));
    }

    /**
     * The criteria this query sets: what a source must be able to apply.
     *
     * @return list<string>
     */
    public function criteria(): array
    {
        return array_values(array_filter(self::CRITERIA, fn (string $name) => null !== $this->$name && [] !== $this->$name && '' !== $this->$name));
    }

    /** A day, at midnight UTC: dates of law have no hour. */
    public static function day(\DateTimeInterface|string|null $date): ?\DateTimeImmutable
    {
        if (null === $date || '' === $date) {
            return null;
        }
        if (\is_string($date)) {
            try {
                $date = new \DateTimeImmutable($date, new \DateTimeZone('UTC'));
            } catch (\Exception) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a date.', $date));
            }
        }

        return new \DateTimeImmutable($date->format('Y-m-d'), new \DateTimeZone('UTC'));
    }
}
