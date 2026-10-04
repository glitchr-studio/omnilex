<?php

namespace Omnilex\Model;

/**
 * One identifier of a legal document, normalised so that two spellings of
 * it compare equal, and validated on its shape alone - no network: an
 * identifier that parses is well formed, not known to exist.
 *
 *   Identifier::ecli('ecli:fr:ccass:2018:c301117')->value;           // ECLI:FR:CCASS:2018:C301117
 *   Identifier::celex('CELEX:32016R0679')->value;                    // 32016R0679
 *   Identifier::pourvoi('n° C 17-18.194')->value;                    // 17-18.194
 *   Identifier::parse('JUSC1732516D');                               // nor:JUSC1732516D
 *   Identifier::parse('https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000038261631');   // jorftext:JORFTEXT000038261631
 *   Identifier::parse('https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32016R0679');   // celex:32016R0679
 */
final readonly class Identifier implements \Stringable
{
    /** ECLI:country:court:year:ordinal - Council conclusions 2011/C 127/01, annex, § 1. */
    private const ECLI = '~^ECLI:([A-Z]{2}):([A-Z][A-Z0-9]{0,6}):(\d{4}):([A-Z0-9.]{1,25})$~';
    /** Sector, year, descriptor, number; then a corrigendum, a part or a consolidation date. */
    private const CELEX = '~^([0-9CE])(\d{4})([A-Z]{1,2})(\d{4}|\d{3}[A-Z]?|/TXT)((?:\(\d{2}\))?(?:R\(\d{2}\))?)(?:-(\d{8}))?$~';
    /** Ministry (3 letters), directorate (1), year (2 digits), sequence (5), nature (1). */
    private const NOR = '~^[A-Z]{4}\d{7}[A-Z]$~';
    private const POURVOI = '~^(?:[A-Z]\s*)?(\d{2})\s*[-‐‑–]?\s*(\d{2})\s*[.\s]?\s*(\d{3})$~u';
    private const LEGIFRANCE = '~^(LEGITEXT|LEGIARTI|LEGISCTA|JORFTEXT|JORFARTI|JORFCONT|JURITEXT|CETATEXT|CONSTEXT)(\d{12})$~';
    private const LEGIFRANCE_ANYWHERE = '~\b(LEGITEXT|LEGIARTI|LEGISCTA|JORFTEXT|JORFARTI|JORFCONT|JURITEXT|CETATEXT|CONSTEXT)(\d{12})\b~';

    private function __construct(
        public Scheme $scheme,
        public string $value,
    ) {
    }

    /**
     * @throws \InvalidArgumentException when the value is not a valid identifier of that scheme
     */
    public static function of(Scheme|string $scheme, string $value): self
    {
        $scheme = $scheme instanceof Scheme ? $scheme : Scheme::from(strtolower($scheme));

        return self::tryOf($scheme, $value) ?? throw new \InvalidArgumentException(\sprintf('"%s" is not a valid %s.', $value, $scheme->label()));
    }

    /** The identifier, or null when the value is not one of that scheme. */
    public static function tryOf(Scheme|string|null $scheme, ?string $value): ?self
    {
        if (null === $scheme || null === $value) {
            return null;
        }
        $scheme = $scheme instanceof Scheme ? $scheme : Scheme::tryFrom(strtolower($scheme));
        $value = trim($value);
        if (null === $scheme || '' === $value) {
            return null;
        }
        $normalized = match ($scheme) {
            Scheme::ECLI => self::normalizeEcli($value),
            Scheme::CELEX => self::normalizeCelex($value),
            Scheme::ELI => self::normalizeEli($value),
            Scheme::NOR => preg_match(self::NOR, $v = strtoupper((string) preg_replace('~^NOR\s*:?\s*~i', '', $value))) ? $v : null,
            Scheme::POURVOI => self::normalizePourvoi($value),
            default => preg_match(self::LEGIFRANCE, $v = strtoupper($value), $m) && strtolower($m[1]) === $scheme->value ? $v : null,
        };

        return null === $normalized ? null : new self($scheme, $normalized);
    }

    /**
     * Reads an identifier from a string: "scheme:value", a page of
     * Légifrance, EUR-Lex or the Publications Office, an ELI, or a value
     * whose shape tells its scheme. Null when nothing is recognised.
     */
    public static function parse(string $input): ?self
    {
        $input = trim($input);
        if ('' === $input) {
            return null;
        }
        if (preg_match('~^https?://~i', $input)) {
            return self::parseUrl($input);
        }
        // scheme:value (an ECLI carries its own prefix)
        if (preg_match('~^([a-z_]+)\s*:\s*(.+)$~i', $input, $m)) {
            $prefix = strtolower($m[1]);
            if ('ecli' === $prefix) {
                return self::tryOf(Scheme::ECLI, $input) ?? self::tryOf(Scheme::ECLI, $m[2]);
            }
            if ($scheme = Scheme::tryFrom($prefix)) {
                return self::tryOf($scheme, $m[2]);
            }
        }
        if (str_starts_with($input, '/eli/') || str_starts_with($input, 'eli/')) {
            return self::tryOf(Scheme::ELI, $input);
        }
        $upper = strtoupper($input);
        if (preg_match(self::LEGIFRANCE, $upper, $m)) {
            return new self(Scheme::from(strtolower($m[1])), $upper);
        }
        if (preg_match(self::NOR, $upper)) {
            return new self(Scheme::NOR, $upper);
        }
        if (preg_match(self::CELEX, $upper)) {
            return self::tryOf(Scheme::CELEX, $upper);
        }
        // A bare case number reads as a pourvoi only in its full written form: 17-18.194.
        if (preg_match('~^(?:n°\s*)?(?:[A-Z]\s)?\d{2}-\d{2}\.\d{3}$~u', $input)) {
            return self::tryOf(Scheme::POURVOI, $input);
        }

        return null;
    }

    public static function ecli(string $value): self
    {
        return self::of(Scheme::ECLI, $value);
    }

    public static function celex(string $value): self
    {
        return self::of(Scheme::CELEX, $value);
    }

    public static function eli(string $value): self
    {
        return self::of(Scheme::ELI, $value);
    }

    public static function nor(string $value): self
    {
        return self::of(Scheme::NOR, $value);
    }

    public static function pourvoi(string $value): self
    {
        return self::of(Scheme::POURVOI, $value);
    }

    /**
     * One of Légifrance's identifiers, its scheme read from its prefix.
     *
     * @throws \InvalidArgumentException
     */
    public static function legifrance(string $value): self
    {
        $upper = strtoupper(trim($value));
        if (!preg_match(self::LEGIFRANCE, $upper, $m)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a Légifrance identifier.', $value));
        }

        return new self(Scheme::from(strtolower($m[1])), $upper);
    }

    /** "ecli:ECLI:FR:CCASS:2018:C301117": what collections key on. */
    public function key(): string
    {
        return $this->scheme->value.':'.$this->value;
    }

    public function equals(self $other): bool
    {
        return $this->scheme === $other->scheme && $this->value === $other->value;
    }

    /** An ECLI's country code ("FR", "EU"); an ELI's ("FR", "EU"). */
    public function country(): ?string
    {
        return match ($this->scheme) {
            Scheme::ECLI => explode(':', $this->value)[1],
            Scheme::ELI => str_starts_with($this->value, 'http://data.europa.eu/') ? 'EU' : 'FR',
            Scheme::CELEX => 'EU',
            Scheme::NOR, Scheme::POURVOI => 'FR',
            default => 'FR',
        };
    }

    /** An ECLI's court code: "CCASS", "CECHS", "C". */
    public function court(): ?string
    {
        return Scheme::ECLI === $this->scheme ? explode(':', $this->value)[2] : null;
    }

    /** The year an ECLI or a CELEX number carries. */
    public function year(): ?int
    {
        return match ($this->scheme) {
            Scheme::ECLI => (int) explode(':', $this->value)[3],
            Scheme::CELEX => (int) substr($this->value, 1, 4),
            default => null,
        };
    }

    /** A CELEX number's sector: "3" legislation, "6" case law, "0" consolidated versions, "1" treaties... */
    public function sector(): ?string
    {
        return Scheme::CELEX === $this->scheme ? $this->value[0] : null;
    }

    /** The day a consolidated CELEX number (02016R0679-20160504) is the state of. */
    public function consolidatedOn(): ?\DateTimeImmutable
    {
        if (Scheme::CELEX !== $this->scheme || !preg_match('~-(\d{8})$~', $this->value, $m)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!Ymd', $m[1], new \DateTimeZone('UTC')) ?: null;
    }

    /**
     * The page that shows it, when the identifier alone tells which: EUR-Lex
     * for a CELEX number and an EU ECLI, the ELI itself, Légifrance for the
     * identifiers whose page does not depend on the kind of text. Null
     * otherwise (a LEGITEXT is a code or a law, each with its own page: the
     * models carry their url).
     */
    public function url(): ?string
    {
        return match ($this->scheme) {
            Scheme::CELEX => 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:'.rawurlencode($this->value),
            Scheme::ECLI => 'EU' === $this->country() ? 'https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=ecli:'.rawurlencode($this->value) : null,
            Scheme::ELI => $this->value,
            Scheme::JORFTEXT => 'https://www.legifrance.gouv.fr/jorf/id/'.$this->value,
            Scheme::JORFARTI => 'https://www.legifrance.gouv.fr/jorf/article_jo/'.$this->value,
            Scheme::JORFCONT => 'https://www.legifrance.gouv.fr/jorf/jo/id/'.$this->value,
            Scheme::JURITEXT => 'https://www.legifrance.gouv.fr/juri/id/'.$this->value,
            Scheme::CETATEXT => 'https://www.legifrance.gouv.fr/ceta/id/'.$this->value,
            Scheme::CONSTEXT => 'https://www.legifrance.gouv.fr/cons/id/'.$this->value,
            default => null,
        };
    }

    public function __toString(): string
    {
        return $this->key();
    }

    private static function parseUrl(string $url): ?self
    {
        $host = strtolower((string) parse_url($url, \PHP_URL_HOST));
        $path = rawurldecode((string) parse_url($url, \PHP_URL_PATH));
        $query = rawurldecode((string) parse_url($url, \PHP_URL_QUERY));

        if (str_ends_with($host, 'legifrance.gouv.fr')) {
            if (str_starts_with($path, '/eli/')) {
                return self::tryOf(Scheme::ELI, $path);
            }

            // A page of an article names its text too: the article is what the page shows.
            if (!preg_match_all(self::LEGIFRANCE_ANYWHERE, strtoupper($path.' '.$query), $all, \PREG_SET_ORDER)) {
                return null;
            }
            usort($all, static fn (array $a, array $b) => str_ends_with($b[1], 'ARTI') <=> str_ends_with($a[1], 'ARTI'));

            return new self(Scheme::from(strtolower($all[0][1])), $all[0][1].$all[0][2]);
        }
        if (str_ends_with($host, 'eur-lex.europa.eu')) {
            if (preg_match('~uri=(?:CELEX|celex)[:=]([^&\s]+)~', $query, $m)) {
                return self::tryOf(Scheme::CELEX, $m[1]);
            }
            if (preg_match('~uri=ecli:([^&\s]+)~i', $query, $m)) {
                return self::tryOf(Scheme::ECLI, $m[1]);
            }
            if (str_starts_with($path, '/eli/')) {
                return self::tryOf(Scheme::ELI, 'http://data.europa.eu'.$path);
            }

            return null;
        }
        if ('data.europa.eu' === $host && str_starts_with($path, '/eli/')) {
            return self::tryOf(Scheme::ELI, $url);
        }
        if (str_ends_with($host, 'publications.europa.eu')) {
            if (preg_match('~^/resource/celex/(.+)$~', $path, $m)) {
                return self::tryOf(Scheme::CELEX, $m[1]);
            }
            if (preg_match('~^/resource/ecli/(.+)$~', $path, $m)) {
                return self::tryOf(Scheme::ECLI, $m[1]);
            }
        }

        return null;
    }

    private static function normalizeEcli(string $value): ?string
    {
        $value = strtoupper((string) preg_replace('~\s+~', '', rawurldecode($value)));
        if (!str_starts_with($value, 'ECLI:')) {
            $value = 'ECLI:'.$value;
        }

        return preg_match(self::ECLI, $value) ? $value : null;
    }

    private static function normalizeCelex(string $value): ?string
    {
        $value = strtoupper((string) preg_replace('~^CELEX\s*[:=]?\s*|\s+~i', '', rawurldecode($value)));

        return preg_match(self::CELEX, $value) ? $value : null;
    }

    /**
     * An ELI as its URI: the Publications Office's for EU law, Légifrance's
     * for French law (its ELIs name a publication in the Journal officiel:
     * .../jo/texte, .../jo/article_1).
     */
    private static function normalizeEli(string $value): ?string
    {
        $path = (string) (preg_match('~^https?://~i', $value) ? parse_url($value, \PHP_URL_PATH) : $value);
        $path = '/'.ltrim(rawurldecode($path), '/');
        if (!preg_match('~^/eli/[A-Za-z_]+/[^\s]+$~', $path)) {
            return null;
        }
        $path = rtrim($path, '/');
        $host = strtolower((string) parse_url($value, \PHP_URL_HOST));
        $french = str_ends_with($host, 'legifrance.gouv.fr') || ('' === $host && preg_match('~/jo(/|$)~', $path));
        if (!$french && '' !== $host && 'data.europa.eu' !== $host && !str_ends_with($host, 'eur-lex.europa.eu')) {
            return null;
        }

        return ($french ? 'https://www.legifrance.gouv.fr' : 'http://data.europa.eu').$path;
    }

    private static function normalizePourvoi(string $value): ?string
    {
        $value = trim((string) preg_replace('~^(?:pourvoi\s*)?(?:n[°o]\.?\s*)?~iu', '', trim($value)));

        return preg_match(self::POURVOI, strtoupper($value), $m) ? "$m[1]-$m[2].$m[3]" : null;
    }
}
