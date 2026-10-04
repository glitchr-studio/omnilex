# Sources

Every source implements `Omnilex\Source\SourceInterface` - `getName()`, `capabilities()` - and
the capability interfaces of what its service does:

| Interface | Method | Answers |
|---|---|---|
| `SearchInterface` | `search(Query)` | `Results`: a page of `Reference`; `$results->next` is the next cursor |
| `TextReaderInterface` | `text($id, ?$at)` | a `Text` as in force on that day (today when null), or null |
| `ArticleReaderInterface` | `article($id, ?$number, ?$at)` | an `Article` as in force on that day, or null |
| `DecisionReaderInterface` | `decision($id)` | a `Decision`, or null |
| `CitationsInterface` | `citations($id, $limit)` | a list of `Citation`, both ways |
| `RecentInterface` | `recent($since, ?Query)` | `Results`: what is new since that day, newest first |

`$source instanceof ArticleReaderInterface` says whether a source reads articles;
`Capabilities::operations($source)` lists what it does; `Registry::articles('legifrance')` gives
it typed, or throws `NotSupportedException`.

## What each one does

| | legifrance | judilibre | eurlex | justice-administrative |
|---|---|---|---|---|
| Holds | texts, articles, decisions | decisions | texts, articles, decisions | decisions |
| Legal order | France | France (judicial courts) | European Union | France (administrative courts) |
| Access | PISTE, OAuth | PISTE, OAuth or API key | open | open |
| `search()` | yes | yes | titles only | yes |
| `text()` by | LEGITEXT, JORFTEXT, NOR, ELI | - | CELEX, ELI | - |
| `article()` by | LEGIARTI; LEGITEXT + number; ELI | - | CELEX or ELI + number | - |
| `decision()` by | JURITEXT (judicial courts) | Judilibre id | ECLI, CELEX | its id, an ECLI of the Conseil d'État |
| `citations()` of | an article (LEGIARTI), a judicial decision | a decision: texts applied, decision contested, case law brought together | an act or a judgment | - |
| `recent()` reads | the texts' publication date; the decisions' date | the date a decision was created or updated in the base | the date of the document | the date a decision was added to the base |
| Version at a date | yes: every version of an article; a text as consolidated that day | - | yes: consolidated versions | - |
| Pseudonymised | not said by the API | yes | not said | yes |
| Per page, at most | 100 | 50 (10 000 results) | 100 | 200, no paging |
| Verified against the real service | no (no credentials) | no (no credentials) | yes | yes |

## The search criteria

`Query` criteria a source cannot apply are refused (`NotSupportedException`), not dropped.

| Criterion | legifrance | judilibre | eurlex | justice-administrative |
|---|---|---|---|---|
| `text` | every word; `"..."` exact | every word; `"..."` exact | words of the title | every word; `"..."` exact |
| `title` | yes | - | words of the title | - |
| `number` | of a text, an article, a case | case number (a pourvoi normalised) | - | case number |
| `kind` | text (default), article, decision | decision | text, decision | decision |
| `jurisdictions` | decisions: `judiciaire`, `administratif` or `constitutionnel` (one) | `cc`, `ca`, `tj`, `tcom`, `cph`, or a seat (`ca_paris`) | a court of the Union: `CJ`, `GCEU` | one court: `CE`, `CAA69`, `TA75` |
| `from`, `to` | texts: signature; decisions: date | decision date | date of the document | date the decision was read |
| `subjects` | articles: the code's name | Judilibre themes | subject-matter codes (`PROT`) | - |
| `types` | texts: `LOI`, `DECRET`...; judicial decisions: `ARRET`... | `arret`, `qpc`, `ordonnance`... | resource types: `REG`, `DIR`, `JUDG` | `Décision` or `Ordonnance` (one) |
| `at` | texts and articles as in force that day | - | - | - |
| `sort` | relevance, newest, oldest | relevance, newest, oldest | newest, oldest | newest only |

The codes are each service's own: `capabilities()` tells which criteria apply, each package's
documentation where its codes come from (Judilibre: `JudilibreSource::taxonomy()`).

## What the services do not allow

- **Légifrance**: the documentation reads a decision of the judicial courts only
  (`/consult/juri`); the administrative and constitutional case law is searched, not read. A
  whole code comes in one answer: read its articles one by one. The quotas are not public.
- **Judilibre**: a decision is read by Judilibre's own identifier: an ECLI or a pourvoi is
  searched first (`Query::$number`). No texts.
- **EUR-Lex**: the open endpoint searches the titles and the metadata, not the full texts. Acts
  whose text the Office does not publish divided into articles cannot be read article by article.
  Consolidated versions are documentary: only the Official Journal is authentic.
- **Administrative courts**: no published contract for the search interface (the site documents
  archives to download); no paging, no subject filter, no links to texts; ECLIs for the Conseil
  d'État only.

## Writing a source

```php
final class HudocSource extends HttpSource implements SearchInterface, DecisionReaderInterface
{
    public function getName(): string { return 'hudoc'; }

    public function capabilities(): Capabilities
    {
        return new Capabilities(kinds: [Kind::DECISION], identifiers: [Scheme::ECLI], criteria: ['text', 'from', 'to'], jurisdictions: ['COE']);
    }

    public function search(Query $query): Results
    {
        $this->accept($query);                       // refuses the criteria not listed above
        $data = $this->getJson('search', [...]);     // errors mapped: 404 null, 429, 5xx, transport
        // ...
    }
}
```

`HttpSource` gives `getJson()`, `postJson()`, `get()`, the throttle, the signing of calls when a
`TokenProviderInterface` is passed, `identify()` to read an identifier from a string, `plain()` to
turn HTML into text and `day()` to read the services' dates. A `SourceFactory` declares its name,
its required options and their defaults.
