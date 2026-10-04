# Models

All models are `readonly`. Dates are `DateTimeImmutable` at midnight UTC: dates of law have no
hour. Every model carries `source` (the source's name) and `raw` (what the service answered,
for what the model does not carry).

## Text

A code, a law, a decree, a regulation, a directive - in one version.

| Property | |
|---|---|
| `id` | the source's identifier: what `text()` reads again |
| `title`, `type` | `type` is the source's own nature: `CODE`, `LOI`, `REG`, `DIR`... |
| `identifiers` | `Identifiers`: CELEX, ELI, NOR, LEGITEXT, JORFTEXT |
| `date`, `publishedOn` | signature or adoption; publication |
| `version` | the `Version` read: the one of the day asked for |
| `versions` | every version the source lists, oldest first |
| `content`, `html` | the whole text, plain and as published, when the source gives it |
| `articles` | `Article[]` in the text's order, when the source gives them apart |
| `subjects`, `citations`, `language`, `url` | |

`isInForce()`; `article('L. 36-11')` finds an article among those given, whatever the spelling
of its number.

## Article

| Property | |
|---|---|
| `id`, `number`, `title` | |
| `content`, `html` | plain text, and as published |
| `version`, `versions` | the version read; every version of the article, oldest first |
| `textId`, `textTitle`, `path` | the text it belongs to, the headings above it |
| `note` | the nota |
| `citations` | what amended it, what it cites |

## Version

`from`, `to` (the last day; null when no end is known), `status` (`Status`: `IN_FORCE`,
`FUTURE`, `MODIFIED`, `REPEALED`, `ANNULLED`, `EXPIRED`, `UNKNOWN`), `id` (what reads that
version), `label`, `url`. `covers($date)`; `Version::at($versions, $date)` picks the one that
applies on a day. See [versions](versions.md).

## Decision

| Property | |
|---|---|
| `id`, `title` | |
| `identifiers` | ECLI, numéros de pourvoi, JURITEXT, CELEX |
| `court` | `Court`: `name`, `code` (the source's key: `cc`, `CE`, `CJ`), `country`, `chamber`, `formation`, `location` |
| `date`, `updatedOn` | |
| `number`, `numbers` | the main case number, and all of them |
| `type`, `solution`, `publication` | the source's own words |
| `summary`, `subjects` | sommaire; titrage, themes |
| `content`, `html` | |
| `citations` | texts applied, decision contested, case law brought together |
| `pseudonymised` | true when the source publishes it with the names removed; null when it does not say |

There is no property for the parties: a pseudonymised decision stays so.

## Reference and Results

A `Reference` is what a search lists and what a citation leads to: `kind` (`Kind::TEXT`,
`ARTICLE`, `DECISION`), `id` (what the source's reader takes), `title`, `identifiers`, `date`,
`type`, `number`, `court`, `status`, `summary`, `url`.

`Results` is one page: `items`, `next` (the cursor of the next page, null on the last), `total`
(when the source counts). Iterable and countable.

## Citation

`relation` (`Relation`) and `target` (a `Reference`), read from the document asked for:
`CITES` / `CITED_BY`, `APPLIES` / `APPLIED_BY`, `INTERPRETS` / `INTERPRETED_BY`, `AMENDS` /
`AMENDED_BY`, `REPEALS` / `REPEALED_BY`, `BASED_ON` / `BASIS_OF`, `CONTESTS`, `FOLLOWED_BY`,
`RELATED`. `note` holds what the source says of the link.

## Query

```php
new Query(
    text: 'responsabilité du fait des choses',   // "..." for the exact expression
    title: null,
    number: null,                                 // of a text, an article, a case
    kind: Kind::DECISION,
    jurisdictions: ['cc'],                        // the source's own codes
    from: '2020-01-01', to: null,                 // strings or DateTimeInterface
    subjects: [], types: [],                      // the source's own codes
    at: null,                                     // texts and articles as in force that day
    limit: 25, cursor: null, sort: Query::RELEVANCE,
);
$query->with(['cursor' => $results->next]);
$query->criteria();                               // ['text', 'kind', 'jurisdictions', 'from']
```

## Capabilities

`kinds`, `identifiers` (the schemes read besides the source's own), `criteria` (the `Query`
criteria applied), `jurisdictions` (`FR`, `EU`), `versions`, `pageSize`, `pseudonymised`.
`holds(Kind)`, `reads(Scheme)`, `filters('subjects')`; `Capabilities::operations($source)`.
