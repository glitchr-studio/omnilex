<?php

namespace Omnilex\Tests;

use Omnilex\Auth\TokenProviderInterface;
use Omnilex\Model\Capabilities;
use Omnilex\Model\Identifier;
use Omnilex\Model\Kind;
use Omnilex\Model\Query;
use Omnilex\Model\Reference;
use Omnilex\Model\Results;
use Omnilex\Model\Scheme;
use Omnilex\Model\Text;
use Omnilex\Source\HttpSource;
use Omnilex\Source\SearchInterface;
use Omnilex\Source\TextReaderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** A source over HTTP that searches (by text and kind only) and reads texts: GET search, GET text/<id>. */
final class StubSource extends HttpSource implements SearchInterface, TextReaderInterface
{
    public function __construct(private readonly string $name, HttpClientInterface $http, ?TokenProviderInterface $auth = null, float $throttle = 0.0)
    {
        parent::__construct($http, 'https://stub.test/api', ['User-Agent' => 'omnilex-tests'], $throttle, $auth);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(kinds: [Kind::TEXT], identifiers: [Scheme::NOR], criteria: ['text', 'kind'], jurisdictions: ['FR'], pageSize: 10);
    }

    public function search(Query $query): Results
    {
        $this->accept($query);
        $data = $this->getJson('search', ['q' => $query->text, 'type' => ['a', 'b'], 'empty' => null]) ?? [];

        return new Results(array_map(fn (array $item) => new Reference(Kind::TEXT, $item['id'], $item['title'], source: $this->name), $data['items'] ?? []), $data['next'] ?? null, $data['total'] ?? null);
    }

    public function text(Identifier|string $id, ?\DateTimeInterface $at = null): ?Text
    {
        $nor = self::identify($id, Scheme::NOR);
        $data = $this->postJson('text', ['id' => $nor?->value ?? (string) $id]);

        return null === $data ? null : new Text($data['id'], $data['title'], source: $this->name);
    }
}
