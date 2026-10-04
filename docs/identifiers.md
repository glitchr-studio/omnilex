# Identifiers

`Omnilex\Model\Identifier` normalises an identifier so that two spellings of it compare equal,
and validates it **on its shape, without the network**: an identifier that parses is well
formed, not known to exist.

```php
use Omnilex\Model\Identifier;
use Omnilex\Model\Scheme;

Identifier::ecli('ecli:fr:ccass:2018:c301117')->value;   // ECLI:FR:CCASS:2018:C301117
Identifier::celex('CELEX:32016R0679')->value;            // 32016R0679
Identifier::pourvoi('Pourvoi n° C 17-18.194')->value;    // 17-18.194
Identifier::nor('jusc1732516d')->value;                  // JUSC1732516D
Identifier::legifrance('legiarti000006419320')->scheme;  // Scheme::LEGIARTI

Identifier::parse('https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000038261631');          // jorftext:JORFTEXT000038261631
Identifier::parse('https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32016R0679'); // celex:32016R0679
Identifier::parse('Code civil');                                                           // null
Identifier::tryOf(Scheme::ECLI, 'ECLI:FR:CCASS');                                          // null
Identifier::celex('2016/679');                                                             // InvalidArgumentException
```

| Scheme | Shape | Normalised as |
|---|---|---|
| `ECLI` | `ECLI:` country (2 letters) `:` court (1-7 characters) `:` year `:` ordinal (1-25 letters, digits, dots) | upper case, with its `ECLI:` prefix |
| `CELEX` | sector, year, descriptor, number; a corrigendum `R(02)`, a part `(01)`; a consolidation date `-20160504` | upper case, without `CELEX:` |
| `ELI` | `/eli/...`: of the Union (`http://data.europa.eu/eli/...`) or of Légifrance (`/eli/decret/.../jo/texte`) | its URI |
| `NOR` | 4 letters, 7 digits, 1 letter | upper case |
| `POURVOI` | `17-18.194`, also `1718194`, `17 18 194`, with `n°` or its key letter | `17-18.194` |
| `LEGITEXT`, `LEGIARTI`, `LEGISCTA`, `JORFTEXT`, `JORFARTI`, `JORFCONT`, `JURITEXT`, `CETATEXT`, `CONSTEXT` | the prefix and 12 digits | upper case |

`parse()` reads `scheme:value`, a page of Légifrance, EUR-Lex or the Publications Office, an ELI,
or a value whose shape tells its scheme. A bare seven-digit number is not read as a pourvoi (it
could be anything): give `pourvoi:1718194` or the written form `17-18.194`.

## What an identifier tells

```php
$ecli = Identifier::ecli('ECLI:FR:CCASS:2018:C301117');
$ecli->country();          // FR
$ecli->court();            // CCASS
$ecli->year();             // 2018

$celex = Identifier::celex('02016R0679-20160504');
$celex->sector();          // 0: a consolidated version (3: legislation, 6: case law, 1: treaties)
$celex->consolidatedOn();  // 2016-05-04
$celex->url();             // https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:02016R0679-20160504

$identifier->key();        // "celex:02016R0679-20160504"
$identifier->scheme->kind();  // Kind::DECISION for an ECLI, Kind::ARTICLE for a LEGIARTI, null when the scheme does not tell
```

`url()` gives a page only when the identifier alone tells which: EUR-Lex for a CELEX number and
an EU ECLI, the ELI itself, Légifrance for JORFTEXT, JURITEXT, CETATEXT, CONSTEXT. A LEGITEXT is
a code or a law, each with its own page: the models carry their `url`.

## Identifiers

A model's `identifiers` is an `Identifiers` collection: each identifier once.

```php
$decision->identifiers->value(Scheme::ECLI);      // ECLI:FR:CCASS:2018:C301117
$decision->identifiers->values(Scheme::POURVOI);  // ['17-18.194', '16-21.165']
$decision->identifiers->has(Scheme::ECLI);
$decision->identifiers->toArray();                // ['ecli' => [...], 'pourvoi' => [...]] for storage
Identifiers::fromArray($stored);
```
