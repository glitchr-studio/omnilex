<?php

namespace Omnilex\Tests;

use Omnilex\Exception\InvalidConfigException;
use Omnilex\Exception\NotSupportedException;
use Omnilex\Model\Kind;
use Omnilex\Model\Scheme;
use Omnilex\Registry;
use Omnilex\Source\DecisionReaderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class RegistryTest extends TestCase
{
    private function registry(): Registry
    {
        return new Registry([new StubSourceFactory(new MockHttpClient()), new DecisionsStubFactory()], [
            'textes' => ['factory' => 'stub', 'options' => ['name' => 'textes']],
            'decisions' => ['factory' => 'decisions'],
        ]);
    }

    public function testSourcesByNameBuiltOnce(): void
    {
        $registry = $this->registry();

        self::assertSame(['textes', 'decisions'], $registry->names());
        self::assertSame(['stub', 'decisions'], $registry->factories());
        self::assertSame($registry->get('textes'), $registry->get('textes'));
        self::assertTrue($registry->has('decisions'));
        self::assertFalse($registry->has('eurlex'));
        self::assertSame(['name' => 'textes'], $registry->options('textes'));
        self::assertCount(2, $registry->all());

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No "eurlex" source; configured: textes, decisions.');
        $registry->get('eurlex');
    }

    public function testASourceIsAskedForWhatItDoes(): void
    {
        $registry = $this->registry();

        self::assertSame('textes', $registry->search('textes')->getName());
        self::assertSame('textes', $registry->texts('textes')->getName());
        self::assertInstanceOf(DecisionReaderInterface::class, $registry->decisions('decisions'));
        self::assertSame('decisions', $registry->recent('decisions')->getName());

        self::assertSame(['textes'], array_keys($registry->having('search')));
        self::assertSame(['decisions'], array_keys($registry->having(DecisionReaderInterface::class)));
        self::assertSame([], $registry->having('citations'));
        self::assertSame(['decisions'], array_keys($registry->reading(Scheme::ECLI)));
        self::assertSame(['textes'], array_keys($registry->reading(Kind::TEXT)));

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('The "decisions" source does not search.');
        $registry->search('decisions');
    }

    public function testASourceWithoutItsOptionsYetIsLeftOutOfTheLists(): void
    {
        $registry = new Registry([new StubSourceFactory(new MockHttpClient()), new DecisionsStubFactory()], [
            'codes' => ['factory' => 'stub'],
            'decisions' => ['factory' => 'decisions'],
        ]);

        self::assertSame(['decisions'], array_keys($registry->usable()));
        self::assertSame(['decisions'], array_keys($registry->having('recent')));
        self::assertSame([], $registry->having('search'), 'declared, not usable yet');
        self::assertTrue($registry->has('codes'));

        $this->expectException(InvalidConfigException::class);
        $registry->all();
    }

    public function testARequiredOption(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "stub" source needs: name.');
        (new Registry([new StubSourceFactory()], ['x' => ['factory' => 'stub']]))->get('x');
    }

    public function testAnUnknownFactory(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No "legifrance" factory for the "codes" source; installed: stub.');
        (new Registry([new StubSourceFactory()], ['codes' => ['factory' => 'legifrance']]))->get('codes');
    }
}
