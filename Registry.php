<?php

namespace Omnilex;

use Omnilex\Exception\InvalidConfigException;
use Omnilex\Exception\NotSupportedException;
use Omnilex\Model\Capabilities;
use Omnilex\Model\Kind;
use Omnilex\Model\Scheme;
use Omnilex\Source\ArticleReaderInterface;
use Omnilex\Source\CitationsInterface;
use Omnilex\Source\DecisionReaderInterface;
use Omnilex\Source\RecentInterface;
use Omnilex\Source\SearchInterface;
use Omnilex\Source\SourceFactoryInterface;
use Omnilex\Source\SourceInterface;
use Omnilex\Source\TextReaderInterface;

/**
 * The sources by name, each built once from its factory and options:
 *
 *   new Registry([new LegifranceSourceFactory($http), new EurlexSourceFactory($http)], [
 *       'legifrance' => ['factory' => 'legifrance', 'options' => ['client_id' => '...', 'client_secret' => '...']],
 *       'eurlex' => ['factory' => 'eurlex', 'options' => ['language' => 'fr']],
 *   ]);
 *
 * Nothing is built, nor checked, before a source is asked for: a site
 * whose PISTE credentials are not there yet still boots.
 */
final class Registry
{
    /** @var array<string, SourceFactoryInterface> */
    private array $factories = [];

    /** @var array<string, SourceInterface> */
    private array $sources = [];

    /**
     * @param iterable<SourceFactoryInterface>                                       $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): SourceInterface
    {
        if (isset($this->sources[$name])) {
            return $this->sources[$name];
        }
        $source = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" source; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));
        $factory = $this->factories[$source['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" source; installed: %s.', $source['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));

        return $this->sources[$name] = $factory->create($source['options'] ?? []);
    }

    /** @throws NotSupportedException when the source does not search */
    public function search(string $name): SearchInterface
    {
        return $this->typed($name, SearchInterface::class, 'search');
    }

    /** @throws NotSupportedException when the source does not read texts */
    public function texts(string $name): TextReaderInterface
    {
        return $this->typed($name, TextReaderInterface::class, 'read texts');
    }

    /** @throws NotSupportedException when the source does not read articles */
    public function articles(string $name): ArticleReaderInterface
    {
        return $this->typed($name, ArticleReaderInterface::class, 'read articles');
    }

    /** @throws NotSupportedException when the source does not read decisions */
    public function decisions(string $name): DecisionReaderInterface
    {
        return $this->typed($name, DecisionReaderInterface::class, 'read decisions');
    }

    /** @throws NotSupportedException when the source does not follow links */
    public function citations(string $name): CitationsInterface
    {
        return $this->typed($name, CitationsInterface::class, 'follow the links between documents');
    }

    /** @throws NotSupportedException when the source does not list what is new */
    public function recent(string $name): RecentInterface
    {
        return $this->typed($name, RecentInterface::class, 'list what is new');
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    /** @return list<string> the configured sources, in the configured order */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return array<string, SourceInterface> in the configured order */
    public function all(): array
    {
        $all = [];
        foreach (array_keys($this->config) as $name) {
            $all[$name] = $this->get($name);
        }

        return $all;
    }

    /**
     * The sources that can be built: those whose options are complete. A
     * source declared without its credentials yet (PISTE) is left out,
     * where all() would refuse it.
     *
     * @return array<string, SourceInterface> in the configured order
     */
    public function usable(): array
    {
        $usable = [];
        foreach (array_keys($this->config) as $name) {
            try {
                $usable[$name] = $this->get($name);
            } catch (InvalidConfigException) {
            }
        }

        return $usable;
    }

    /**
     * The usable sources that do one thing: an operation ("search", "text",
     * "article", "decision", "citations", "recent") or its interface.
     *
     * @return array<string, SourceInterface>
     */
    public function having(string $operation): array
    {
        $interface = Capabilities::OPERATIONS[$operation] ?? $operation;

        return array_filter($this->usable(), static fn (SourceInterface $source) => $source instanceof $interface);
    }

    /**
     * The usable sources that read that kind of identifier, or hold that kind of document.
     *
     * @return array<string, SourceInterface>
     */
    public function reading(Scheme|Kind $what): array
    {
        return array_filter($this->usable(), static fn (SourceInterface $source) => $what instanceof Scheme ? $source->capabilities()->reads($what) : $source->capabilities()->holds($what));
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    /** The options a source is configured with (its factory's defaults not included). */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /**
     * @template T of SourceInterface
     *
     * @param class-string<T> $interface
     *
     * @return T
     */
    private function typed(string $name, string $interface, string $operation): SourceInterface
    {
        $source = $this->get($name);

        return $source instanceof $interface ? $source : throw NotSupportedException::operation($name, $operation);
    }
}
