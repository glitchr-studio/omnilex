<?php

namespace Omnilex\Tests;

use Omnilex\Model\Article;
use Omnilex\Model\Capabilities;
use Omnilex\Model\Kind;
use Omnilex\Model\Query;
use Omnilex\Model\Scheme;
use Omnilex\Model\Status;
use Omnilex\Model\Text;
use Omnilex\Model\Token;
use Omnilex\Model\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class ModelTest extends TestCase
{
    public function testTheVersionInForceOnADay(): void
    {
        $day = static fn (string $d) => new \DateTimeImmutable($d, new \DateTimeZone('UTC'));
        $versions = [
            new Version($day('2004-06-22'), $day('2016-09-30'), Status::MODIFIED, 'v1'),
            // Légifrance ends a version the day the next one starts: the later one wins that day.
            new Version($day('2016-10-01'), $day('2018-06-01'), Status::MODIFIED, 'v2'),
            new Version($day('2018-06-01'), null, Status::IN_FORCE, 'v3'),
        ];

        self::assertNull(Version::at($versions, $day('2000-01-01')), 'before the first version: nothing');
        self::assertSame('v1', Version::at($versions, $day('2010-05-05'))->id);
        self::assertSame('v2', Version::at($versions, $day('2016-10-01'))->id);
        self::assertSame('v3', Version::at($versions, $day('2018-06-01'))->id);
        self::assertSame('v3', Version::at($versions, $day('2030-01-01'))->id);
        self::assertFalse((new Version(null, null))->covers($day('2020-01-01')), 'an unknown start never covers');
    }

    public function testAQuerySaysWhichCriteriaItSets(): void
    {
        $query = new Query(text: 'bail commercial', kind: Kind::DECISION, jurisdictions: ['cc'], from: '2020-01-01', at: new \DateTimeImmutable('2021-03-04 15:00', new \DateTimeZone('Europe/Paris')));

        self::assertSame(['text', 'kind', 'jurisdictions', 'from', 'at'], $query->criteria());
        self::assertSame('2020-01-01T00:00:00+00:00', $query->from->format(\DATE_ATOM));
        self::assertSame('2021-03-04', $query->at->format('Y-m-d'), 'a day, whatever the hour');
        self::assertSame([], (new Query(limit: 50, sort: Query::NEWEST))->criteria(), 'limit, cursor and sort are not criteria');

        $next = $query->with(['cursor' => '2', 'jurisdictions' => []]);
        self::assertSame('2', $next->cursor);
        self::assertSame('bail commercial', $next->text);
        self::assertSame(['text', 'kind', 'from', 'at'], $next->criteria());
        self::assertEquals($query->from, $next->from);

        $this->expectException(\InvalidArgumentException::class);
        new Query(from: 'le mois dernier');
    }

    public function testATextFindsItsArticleWhateverTheSpelling(): void
    {
        $text = new Text('LEGITEXT000006070987', 'Code des postes et des communications électroniques', articles: [
            new Article('LEGIARTI000033219357', 'L36-11', 'L\'Autorité de régulation...', version: new Version(status: Status::IN_FORCE)),
            new Article('LEGIARTI000006465859', 'L36-12', '...'),
        ]);

        self::assertSame('LEGIARTI000033219357', $text->article('L. 36-11')?->id);
        self::assertSame('LEGIARTI000033219357', $text->article('l 36 11')?->id);
        self::assertNull($text->article('L36-13'));
        self::assertTrue($text->articles[0]->isInForce());
        self::assertFalse($text->isInForce(), 'no version known');
    }

    public function testCapabilitiesAndOperations(): void
    {
        $stub = new StubSource('stub', new MockHttpClient());
        $capabilities = $stub->capabilities();

        self::assertTrue($capabilities->holds(Kind::TEXT));
        self::assertFalse($capabilities->holds(Kind::DECISION));
        self::assertTrue($capabilities->reads(Scheme::NOR));
        self::assertTrue($capabilities->filters('text'));
        self::assertFalse($capabilities->filters('jurisdictions'));
        self::assertSame(['search', 'text'], Capabilities::operations($stub));
        self::assertSame(['decision', 'recent'], Capabilities::operations(new DecisionsStub()));
    }

    public function testATokenKnowsWhenItDies(): void
    {
        $now = new \DateTimeImmutable('2026-10-04 12:00:00');
        $token = new Token('abc', $now->modify('+3600 seconds'));

        self::assertFalse($token->isExpired(0, $now));
        self::assertFalse($token->isExpired(60, $now->modify('+3500 seconds')));
        self::assertTrue($token->isExpired(60, $now->modify('+3550 seconds')), 'dead within the leeway');
        self::assertFalse((new Token('abc'))->isExpired(), 'no expiry known');
        self::assertEquals($token, Token::fromArray($token->toArray()));
    }
}
