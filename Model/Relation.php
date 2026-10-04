<?php

namespace Omnilex\Model;

/** How a document relates to another: the direction is read from the document the citation was asked for. */
enum Relation: string
{
    case CITES = 'cites';
    case CITED_BY = 'cited_by';
    /** A decision applies a text (its visa). */
    case APPLIES = 'applies';
    case APPLIED_BY = 'applied_by';
    /** A decision interprets a text (a preliminary ruling). */
    case INTERPRETS = 'interprets';
    case INTERPRETED_BY = 'interpreted_by';
    case AMENDS = 'amends';
    case AMENDED_BY = 'amended_by';
    case REPEALS = 'repeals';
    case REPEALED_BY = 'repealed_by';
    /** A text adopted on the basis of another. */
    case BASED_ON = 'based_on';
    case BASIS_OF = 'basis_of';
    /** A decision rules on another: the judgment under appeal. */
    case CONTESTS = 'contests';
    /** The decision that came after this one in the same case. */
    case FOLLOWED_BY = 'followed_by';
    /** Case law brought together by the court itself (a rapprochement). */
    case RELATED = 'related';
}
