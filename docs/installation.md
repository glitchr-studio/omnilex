# Installation and first calls

```sh
composer require glitchr/omnilex omnilex/eurlex omnilex/justice-administrative
composer require omnilex/legifrance omnilex/judilibre    # with a PISTE account
```

PHP 8.2 or later. The core needs only `symfony/http-client-contracts`; each source package
brings `symfony/http-client`.

## One source

```php
use Omnilex\Eurlex\EurlexSourceFactory;
use Omnilex\Model\Kind;
use Omnilex\Model\Query;
use Symfony\Component\HttpClient\HttpClient;

$eurlex = (new EurlexSourceFactory(HttpClient::create()))->create(['language' => 'fr']);

$text = $eurlex->text('32016R0679', new DateTimeImmutable('2018-06-01'));
$text->title;                            // Règlement (UE) 2016/679 ...
$text->version->id;                      // 02016R0679-20160504
$text->isInForce();                      // true

$article = $eurlex->article('32016R0679', '17');
$article->title;                         // Droit à l'effacement («droit à l'oubli»)

$results = $eurlex->search(new Query(text: 'protection des données', kind: Kind::TEXT, types: ['REG'], from: '2016-01-01'));
foreach ($results as $reference) {
    echo $reference->date?->format('Y-m-d'), ' ', $reference->id, ' ', $reference->title, "\n";
}
$next = $eurlex->search(new Query(text: 'protection des données', cursor: $results->next));   // the next page
```

A document is named by an `Identifier` or a string the source reads: a normalised identifier
(`'32016R0679'`, `'ECLI:EU:C:2014:317'`, `'JUSC1732516D'`, `'LEGIARTI000033219357'`), the URL of
its page, or the source's own identifier - the `id` its search gave.

## Several sources

```php
use Omnilex\Registry;

$registry = new Registry(
    [new EurlexSourceFactory($http), new JusticeAdministrativeSourceFactory($http), new LegifranceSourceFactory($http)],
    [
        'eurlex' => ['factory' => 'eurlex', 'options' => ['language' => 'fr']],
        'administratif' => ['factory' => 'justice-administrative'],
        'legifrance' => ['factory' => 'legifrance', 'options' => ['client_id' => '...', 'client_secret' => '...']],
    ],
);

$registry->decisions('eurlex')->decision('ECLI:EU:C:2014:317');   // typed: a DecisionReaderInterface
$registry->recent('administratif')->recent(new DateTimeImmutable('-7 days'));
$registry->having('recent');                 // the sources a watch can ask
$registry->reading(Scheme::ECLI);            // the sources that read an ECLI
```

Nothing is built before a source is asked for: a registry whose PISTE credentials are missing
still works for the other sources. `having()`, `reading()` and `usable()` leave out the sources
whose options are incomplete; `get()` and `all()` refuse them (`InvalidConfigException`).

## A legal watch

```php
foreach ($registry->having('recent') as $name => $source) {
    try {
        $new = $source->recent($lastRun, new Query(kind: Kind::DECISION));
    } catch (UnavailableException $e) {
        continue;   // down, refused or slowed: try again later - not "nothing new"
    }
    // store $new->items for validation; follow $new->next for the pages after
}
```

Each source says which date `recent()` reads: the day a decision was added to the base
(administrative courts, Judilibre), the day a text was published (Légifrance), the date of the
document (EUR-Lex).

## Errors

| Exception | When |
|---|---|
| `NotSupportedException` | the source does not do that, cannot read that identifier, cannot apply that criterion |
| `AuthenticationException` | the credentials are refused: wrong client id or secret, API not subscribed, terms not accepted |
| `RateLimitedException` | 429: slow down (`$retryAfter` seconds, when the service says) |
| `UnavailableException` | 5xx, 423, a timeout, a network error (the two above extend it): never "nothing" |
| `ProviderException` | any other error answer (a malformed query: 400) |
| `InvalidConfigException` | a source not configured, a factory not installed, an option missing |

All implement `Omnilex\Exception\OmnilexException`. Not found is `null` or an empty page.

## Rate limits

Every source takes a `throttle` option: the seconds left between two calls (0.5 by default, 1
for the administrative courts' open data). The PISTE quotas are per application and shown on
the portal; a quota spent comes back as a `RateLimitedException`. Cache what the sources answer
- a version of a text that is no longer in force never changes - but never an
`UnavailableException`.
