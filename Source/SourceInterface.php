<?php

namespace Omnilex\Source;

use Omnilex\Model\Capabilities;

/**
 * A service that publishes sources of law: Légifrance, Judilibre, EUR-Lex,
 * the administrative courts' open data. A source implements the capability
 * interfaces of what its service does, and only those:
 *
 *   SearchInterface          search(Query): Results
 *   TextReaderInterface      text($id, $at): ?Text
 *   ArticleReaderInterface   article($id, $number, $at): ?Article
 *   DecisionReaderInterface  decision($id): ?Decision
 *   CitationsInterface       citations($id): list<Citation>
 *   RecentInterface          recent($since, ?Query): Results
 *
 * Unknown is null (or an empty page). A service that is down, refuses the
 * credentials or asks to slow down is an UnavailableException - never read
 * as "not found". An identifier or a criterion the source cannot take is a
 * NotSupportedException; any other error answer a ProviderException.
 */
interface SourceInterface
{
    /** The name it is configured as: "legifrance", "judilibre"... */
    public function getName(): string;

    public function capabilities(): Capabilities;
}
