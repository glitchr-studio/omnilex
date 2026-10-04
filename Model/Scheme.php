<?php

namespace Omnilex\Model;

/**
 * The kinds of identifiers a legal document carries. Each is validated on
 * its shape, without the network: an identifier that parses is well formed,
 * not known to exist.
 */
enum Scheme: string
{
    /** European Case Law Identifier: ECLI:FR:CCASS:2018:C301117, ECLI:EU:C:2014:317. */
    case ECLI = 'ecli';
    /** A document of EU law in EUR-Lex: 32016R0679; a consolidated version: 02016R0679-20160504. */
    case CELEX = 'celex';
    /** European Legislation Identifier: http://data.europa.eu/eli/reg/2016/679/oj, /eli/decret/2018/2/13/JUSC1732516D/jo/texte. */
    case ELI = 'eli';
    /** The French official number of a text published in the Journal officiel: JUSC1732516D. */
    case NOR = 'nor';
    /** An appeal number at the Cour de cassation: 17-18.194. */
    case POURVOI = 'pourvoi';
    /** Légifrance: a consolidated text (a code, a law, a decree). */
    case LEGITEXT = 'legitext';
    /** Légifrance: one version of an article of a consolidated text. */
    case LEGIARTI = 'legiarti';
    /** Légifrance: a section of a consolidated text. */
    case LEGISCTA = 'legiscta';
    /** Légifrance: a text as published in the Journal officiel. */
    case JORFTEXT = 'jorftext';
    /** Légifrance: an article as published in the Journal officiel. */
    case JORFARTI = 'jorfarti';
    /** Légifrance: one issue of the Journal officiel. */
    case JORFCONT = 'jorfcont';
    /** Légifrance: a decision of the judicial courts. */
    case JURITEXT = 'juritext';
    /** Légifrance: a decision of the administrative courts. */
    case CETATEXT = 'cetatext';
    /** Légifrance: a decision of the Conseil constitutionnel. */
    case CONSTEXT = 'constext';

    public function label(): string
    {
        return match ($this) {
            self::ECLI => 'ECLI',
            self::CELEX => 'CELEX number',
            self::ELI => 'ELI',
            self::NOR => 'NOR',
            self::POURVOI => 'numéro de pourvoi',
            default => $this->name.' identifier',
        };
    }

    /** One of Légifrance's own identifiers: a prefix and twelve digits. */
    public function isLegifrance(): bool
    {
        return !\in_array($this, [self::ECLI, self::CELEX, self::ELI, self::NOR, self::POURVOI], true);
    }

    /** What the identifier names, when its scheme tells. */
    public function kind(): ?Kind
    {
        return match ($this) {
            self::ECLI, self::POURVOI, self::JURITEXT, self::CETATEXT, self::CONSTEXT => Kind::DECISION,
            self::LEGIARTI, self::JORFARTI => Kind::ARTICLE,
            self::NOR, self::LEGITEXT, self::JORFTEXT => Kind::TEXT,
            default => null,
        };
    }
}
