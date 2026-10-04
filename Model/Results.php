<?php

namespace Omnilex\Model;

/**
 * One page of references. $next is the cursor of the following page (pass
 * it as Query::$cursor), null on the last one or when the source does not
 * page.
 *
 * @implements \IteratorAggregate<int, Reference>
 */
final readonly class Results implements \IteratorAggregate, \Countable
{
    /**
     * @param list<Reference> $items
     * @param int|null        $total how many documents the whole query matches, when the source says
     */
    public function __construct(
        public array $items = [],
        public ?string $next = null,
        public ?int $total = null,
    ) {
    }

    public function isLast(): bool
    {
        return null === $this->next;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
