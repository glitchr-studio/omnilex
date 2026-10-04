<?php

namespace Omnilex\Model;

/** What a source holds, and what a search asks for. */
enum Kind: string
{
    /** A whole text: a code, a law, a decree, a regulation, a directive. */
    case TEXT = 'text';
    /** One article of a text. */
    case ARTICLE = 'article';
    /** A court's decision. */
    case DECISION = 'decision';
}
