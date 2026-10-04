<?php

namespace Omnilex\Tests;

use Omnilex\Model\Identifier;
use Omnilex\Model\Identifiers;
use Omnilex\Model\Kind;
use Omnilex\Model\Scheme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    /** @return iterable<array{string, string|null}> */
    public static function parsed(): iterable
    {
        // ECLI
        yield ['ECLI:FR:CCASS:2018:C301117', 'ecli:ECLI:FR:CCASS:2018:C301117'];
        yield ['ecli:fr:ccass:2018:c301117', 'ecli:ECLI:FR:CCASS:2018:C301117'];
        yield ['ECLI:FR:CECHS:2026:505010.20261002', 'ecli:ECLI:FR:CECHS:2026:505010.20261002'];
        yield ['ECLI:EU:C:2014:317', 'ecli:ECLI:EU:C:2014:317'];
        yield ['https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=ecli:ECLI%3AEU%3AC%3A2014%3A317', 'ecli:ECLI:EU:C:2014:317'];
        yield ['http://publications.europa.eu/resource/ecli/ECLI%3AEU%3AC%3A2014%3A317', 'ecli:ECLI:EU:C:2014:317'];
        yield ['ECLI:FR:CCASS:18:C301117', null];
        yield ['ECLI:FRA:CCASS:2018:C301117', null];
        yield ['ECLI:FR:CCASS:2018:', null];
        // CELEX
        yield ['32016R0679', 'celex:32016R0679'];
        yield ['CELEX:32016R0679', 'celex:32016R0679'];
        yield ['celex:32006l0112', 'celex:32006L0112'];
        yield ['02016R0679-20160504', 'celex:02016R0679-20160504'];
        yield ['32016R0679R(02)', 'celex:32016R0679R(02)'];
        yield ['62012CJ0131', 'celex:62012CJ0131'];
        yield ['12012E/TXT', 'celex:12012E/TXT'];
        yield ['12007P008', 'celex:12007P008'];
        yield ['52016AG0006(01)', 'celex:52016AG0006(01)'];
        yield ['https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32016R0679', 'celex:32016R0679'];
        yield ['https://eur-lex.europa.eu/legal-content/EN/TXT/HTML/?uri=CELEX%3A02016R0679-20160504&qid=1', 'celex:02016R0679-20160504'];
        yield ['http://publications.europa.eu/resource/celex/32016R0679', 'celex:32016R0679'];
        yield ['3201R0679', null];
        yield ['celex:2016/679', null];
        // ELI
        yield ['http://data.europa.eu/eli/reg/2016/679/oj', 'eli:http://data.europa.eu/eli/reg/2016/679/oj'];
        yield ['https://eur-lex.europa.eu/eli/reg/2016/679/oj', 'eli:http://data.europa.eu/eli/reg/2016/679/oj'];
        yield ['/eli/decret/2018/2/13/JUSC1732516D/jo/texte', 'eli:https://www.legifrance.gouv.fr/eli/decret/2018/2/13/JUSC1732516D/jo/texte'];
        yield ['https://www.legifrance.gouv.fr/eli/decret/2021/7/13/PRMD2117108D/jo/article_1', 'eli:https://www.legifrance.gouv.fr/eli/decret/2021/7/13/PRMD2117108D/jo/article_1'];
        yield ['https://example.org/eli/reg/2016/679/oj', null];
        // NOR
        yield ['JUSC1732516D', 'nor:JUSC1732516D'];
        yield ['nor: maej9830052d', 'nor:MAEJ9830052D'];
        yield ['JUSC173251D', null];
        // Pourvoi
        yield ['17-18.194', 'pourvoi:17-18.194'];
        yield ['n° 17-18.194', 'pourvoi:17-18.194'];
        yield ['pourvoi:1718194', 'pourvoi:17-18.194'];
        yield ['1718194', null];
        yield ['17/030701', null];
        // Légifrance
        yield ['LEGITEXT000006070721', 'legitext:LEGITEXT000006070721'];
        yield ['legiarti000006419320', 'legiarti:LEGIARTI000006419320'];
        yield ['JORFTEXT000038261631', 'jorftext:JORFTEXT000038261631'];
        yield ['https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000038261631', 'jorftext:JORFTEXT000038261631'];
        yield ['https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000006419320/2018-01-01', 'legiarti:LEGIARTI000006419320'];
        yield ['https://www.legifrance.gouv.fr/affichCodeArticle.do?cidTexte=LEGITEXT000006074224&idArticle=LEGIARTI000029733417', 'legiarti:LEGIARTI000029733417'];
        yield ['https://www.legifrance.gouv.fr/juri/id/JURITEXT000037999394', 'juritext:JURITEXT000037999394'];
        yield ['https://www.legifrance.gouv.fr/ceta/id/CETATEXT000042065744', 'cetatext:CETATEXT000042065744'];
        yield ['LEGITEXT00000607072', null];
        yield ['legitext:JORFTEXT000038261631', null];
        // Nothing
        yield ['Code civil', null];
        yield ['', null];
    }

    #[DataProvider('parsed')]
    public function testParse(string $input, ?string $key): void
    {
        self::assertSame($key, Identifier::parse($input)?->key());
    }

    public function testNormalisationMakesSpellingsEqual(): void
    {
        self::assertTrue(Identifier::ecli('fr:ccass:2018:c301117')->equals(Identifier::ecli('ECLI:FR:CCASS:2018:C301117')));
        self::assertTrue(Identifier::celex('CELEX: 32016r0679')->equals(Identifier::celex('32016R0679')));
        self::assertSame('17-18.194', Identifier::pourvoi('Pourvoi n° C 17-18.194')->value);
        self::assertSame('17-18.194', Identifier::pourvoi('17 18 194')->value);
        self::assertSame('16-21.165', Identifier::pourvoi('16-21165')->value);
        self::assertSame(Scheme::LEGIARTI, Identifier::legifrance('legiarti000006419320')->scheme);
        self::assertSame('nor:JUSC1732516D', (string) Identifier::nor('jusc1732516d'));
    }

    public function testAnInvalidIdentifierIsRefused(): void
    {
        self::assertNull(Identifier::tryOf(Scheme::ECLI, 'ECLI:FR:CCASS'));
        self::assertNull(Identifier::tryOf('nope', 'x'));
        self::assertNull(Identifier::tryOf(Scheme::LEGITEXT, 'LEGIARTI000006419320'), 'the prefix is the scheme');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"2016/679" is not a valid CELEX number.');
        Identifier::celex('2016/679');
    }

    public function testWhatAnIdentifierTells(): void
    {
        $ecli = Identifier::ecli('ECLI:FR:CCASS:2018:C301117');
        self::assertSame('FR', $ecli->country());
        self::assertSame('CCASS', $ecli->court());
        self::assertSame(2018, $ecli->year());
        self::assertNull($ecli->url(), 'a French ECLI has no resolver the identifier alone tells');
        self::assertSame('EU', Identifier::ecli('ECLI:EU:C:2014:317')->country());
        self::assertSame('https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=ecli:ECLI%3AEU%3AC%3A2014%3A317', Identifier::ecli('ECLI:EU:C:2014:317')->url());

        $celex = Identifier::celex('02016R0679-20160504');
        self::assertSame('0', $celex->sector());
        self::assertSame(2016, $celex->year());
        self::assertSame('2016-05-04', $celex->consolidatedOn()?->format('Y-m-d'));
        self::assertNull(Identifier::celex('32016R0679')->consolidatedOn());
        self::assertSame('3', Identifier::celex('32016R0679')->sector());
        self::assertSame('https://eur-lex.europa.eu/legal-content/FR/TXT/?uri=CELEX:32016R0679', Identifier::celex('32016R0679')->url());

        self::assertSame('https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000038261631', Identifier::legifrance('JORFTEXT000038261631')->url());
        self::assertNull(Identifier::legifrance('LEGITEXT000006070721')->url(), 'a code or a law: the page depends on which');
        self::assertSame('EU', Identifier::eli('http://data.europa.eu/eli/reg/2016/679/oj')->country());
        self::assertSame('FR', Identifier::eli('/eli/decret/2018/2/13/JUSC1732516D/jo/texte')->country());

        self::assertSame(Kind::DECISION, Scheme::ECLI->kind());
        self::assertSame(Kind::ARTICLE, Scheme::LEGIARTI->kind());
        self::assertNull(Scheme::CELEX->kind(), 'an act or a judgment');
        self::assertTrue(Scheme::JORFTEXT->isLegifrance());
        self::assertFalse(Scheme::NOR->isLegifrance());
    }

    public function testACollectionKeepsEachOnce(): void
    {
        $identifiers = Identifiers::of(Identifier::ecli('ECLI:FR:CCASS:2018:C301117'), null, Identifier::pourvoi('17-18.194'), Identifier::pourvoi('16-21.165'), Identifier::pourvoi('1718194'));

        self::assertCount(3, $identifiers);
        self::assertSame('ECLI:FR:CCASS:2018:C301117', $identifiers->value(Scheme::ECLI));
        self::assertSame(['17-18.194', '16-21.165'], $identifiers->values(Scheme::POURVOI));
        self::assertTrue($identifiers->has(Scheme::POURVOI));
        self::assertFalse($identifiers->has(Scheme::CELEX));
        self::assertEquals($identifiers, Identifiers::fromArray($identifiers->toArray()));
    }
}
