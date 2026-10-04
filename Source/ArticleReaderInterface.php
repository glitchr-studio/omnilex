<?php

namespace Omnilex\Source;

use Omnilex\Exception\NotSupportedException;
use Omnilex\Exception\ProviderException;
use Omnilex\Exception\UnavailableException;
use Omnilex\Model\Article;
use Omnilex\Model\Identifier;

/** A source that reads one article of a text. */
interface ArticleReaderInterface extends SourceInterface
{
    /**
     * An article as in force on that day, or null when unknown, or not in
     * force then. $id names the article itself (its $number is then left
     * out) or the text it belongs to, with the article's $number.
     *
     * Without a date: the version the identifier names when it names one
     * (a Légifrance LEGIARTI), the one in force today otherwise.
     *
     * @throws NotSupportedException
     * @throws UnavailableException
     * @throws ProviderException
     */
    public function article(Identifier|string $id, ?string $number = null, ?\DateTimeInterface $at = null): ?Article;
}
