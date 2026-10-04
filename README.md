# glitchr/omnilex

One contract for the sources of law - texts, codes, case law: searched with filters
(jurisdiction, date, subject, type), read **in the version in force at a given date**, their
links followed between decisions and texts, and what is new since a date listed for a legal
watch.

```php
$article = $legifrance->article('LEGITEXT000006070987', 'L36-11', new DateTimeImmutable('2018-01-01'));
$article->content;                      // the article as it read on 1 January 2018
$article->version->from;                // ... the day that wording started to apply

$gdpr = $eurlex->text('32016R0679', new DateTimeImmutable('2018-06-01'));
$gdpr->version->id;                     // 02016R0679-20160504: the consolidated version of that day

$decision = $eurlex->decision('ECLI:EU:C:2014:317');
$decision->citations;                   // interprets 31995L0046, cites 62012CJ0473...

$new = $administratif->recent(new DateTimeImmutable('-7 days'), new Query(jurisdictions: ['CE']));
```

This package holds the contract (`Source\*Interface`, `Source\SourceFactory`, `Registry`), the
models (`Text`, `Article`, `Version`, `Decision`, `Court`, `Citation`, `Reference`, `Query`,
`Results`, `Capabilities`), the identifiers (`Identifier`: ECLI, CELEX, ELI, NOR, numéro de
pourvoi, Légifrance's own - normalised and validated without the network), OAuth client
credentials for PISTE (`Auth\ClientCredentials`) and the Symfony bundle. It requires nothing but
`symfony/http-client-contracts`. Each source is a package of its own:

| Package | Source | Access |
|---|---|---|
| [`omnilex/legifrance`](https://github.com/glitchr-studio/omnilex-legifrance) | Légifrance: codes, laws, decrees, the Journal officiel, as in force at a date; case law | PISTE account (free), OAuth client credentials |
| [`omnilex/judilibre`](https://github.com/glitchr-studio/omnilex-judilibre) | Judilibre: the decisions of the Cour de cassation and of the judicial courts, pseudonymised | PISTE account (free), OAuth client credentials or API key |
| [`omnilex/eurlex`](https://github.com/glitchr-studio/omnilex-eurlex) | EUR-Lex: EU acts in their consolidated versions, the case law of the Court of Justice, their links | none (the Publications Office's open endpoint) |
| [`omnilex/justice-administrative`](https://github.com/glitchr-studio/omnilex-justice-administrative) | The Conseil d'État, the administrative courts of appeal and tribunals: decisions, pseudonymised | none |

## Capability interfaces

A source implements what its service does, and only that:

| Interface | Method | |
|---|---|---|
| `SearchInterface` | `search(Query): Results` | filters: jurisdiction, dates, subject, type, kind, in force at a date |
| `TextReaderInterface` | `text($id, $at): ?Text` | a text as in force on a day |
| `ArticleReaderInterface` | `article($id, $number, $at): ?Article` | one article as in force on a day |
| `DecisionReaderInterface` | `decision($id): ?Decision` | a court's decision |
| `CitationsInterface` | `citations($id): Citation[]` | the links between decisions and texts, both ways |
| `RecentInterface` | `recent($since, ?Query): Results` | what is new since a day |

`capabilities()` says the rest: the kinds of documents held, the identifiers read, the search
criteria applied. What a source cannot do is a `NotSupportedException` - a search criterion it
cannot apply is refused, never silently dropped.

## Three answers, never confused

- **Unknown** is `null` (or an empty page).
- **Down, refused or slowed** is an `UnavailableException` (`RateLimitedException`,
  `AuthenticationException`): never read as "not found", never cached, never "nothing new".
- **Not possible with this source** is a `NotSupportedException`.

## Pseudonymised decisions

Judilibre and the administrative courts publish their decisions with the names of the persons
removed. Omnilex gives these texts as published (`Decision::$pseudonymised`) and never tries to
put a name back on a person; an application built on it must not either.

## Scope

Omnilex reads **sources of law**. Legal scholarship (articles, theses) is read with
[`glitchr/omnischolar`](https://github.com/glitchr-studio/omnischolar) (`omnischolar/hal` on the
domain `shs.droit`); public registers (companies, VAT numbers) with `glitchr/omnistate`.

## Documentation

- [Installation and first calls](docs/installation.md)
- [Sources: what each can and cannot do](docs/sources.md)
- [Models](docs/models.md)
- [Identifiers: ECLI, CELEX, ELI, NOR, pourvoi, Légifrance](docs/identifiers.md)
- [The law at a date: versions](docs/versions.md)
- [Authentication: PISTE and OAuth client credentials](docs/authentication.md)
- [Symfony](docs/symfony.md)
- [The Docker harness](docs/harness.md)

## Symfony

```yaml
omnilex:
    sources:
        legifrance: { factory: legifrance, options: { client_id: '%env(PISTE_CLIENT_ID)%', client_secret: '%env(PISTE_CLIENT_SECRET)%' } }
        judilibre: { factory: judilibre, options: { client_id: '%env(PISTE_CLIENT_ID)%', client_secret: '%env(PISTE_CLIENT_SECRET)%' } }
        eurlex: { factory: eurlex, options: { language: fr } }
        administratif: { factory: justice-administrative }
```

```php
public function __construct(ArticleReaderInterface $legifrance, DecisionReaderInterface $judilibre, RecentInterface $administratif) {}
```

## Docker: every source, for real

```sh
cd docker && cp .env.dist .env
docker compose run --rm omnilex sources
docker compose run --rm omnilex text eurlex 32016R0679 --at 2018-06-01
docker compose run --rm omnilex recent administratif 2026-10-01 --jurisdiction CE
docker compose run --rm omnilex test
```

License: LGPL-3.0-or-later.
