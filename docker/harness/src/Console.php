<?php

namespace Omnilex\Harness;

use Omnilex\Exception\InvalidConfigException;
use Omnilex\Exception\OmnilexException;
use Omnilex\Model\Capabilities;
use Omnilex\Model\Citation;
use Omnilex\Model\Identifier;
use Omnilex\Model\Identifiers;
use Omnilex\Model\Kind;
use Omnilex\Model\Query;
use Omnilex\Model\Results;
use Omnilex\Registry;
use Omnilex\Source\SourceFactoryInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * The console that asks every source, for real: sources, search, text,
 * article, decision, citations, recent.
 */
final class Console
{
    /** @var array<string, array{factory: string, needs: list<string>, options: array<string, mixed>}> */
    private array $config;

    /** @var array<string, SourceFactoryInterface> */
    private array $factories = [];

    private Registry $registry;

    private function __construct()
    {
        $this->config = require __DIR__.'/../config/sources.php';
        $http = HttpClient::create(['timeout' => 90]);
        foreach (require __DIR__.'/../plugins.php' as [, $class]) {
            if (class_exists($class)) {
                $factory = new $class($http);
                $this->factories[$factory->getName()] = $factory;
            }
        }
        $usable = array_filter($this->config, fn (array $c) => isset($this->factories[$c['factory']]) && !$this->missing($c));
        $this->registry = new Registry($this->factories, array_map(static fn (array $c) => ['factory' => $c['factory'], 'options' => array_filter($c['options'], static fn ($v) => null !== $v)], $usable));
    }

    public static function create(): Application
    {
        $self = new self();
        $source = new InputArgument('source', InputArgument::REQUIRED, 'A configured source: legifrance, judilibre, eurlex, administratif');
        $at = new InputOption('at', null, InputOption::VALUE_REQUIRED, 'As in force on that day (YYYY-MM-DD); today when left out');
        $filters = [
            new InputOption('kind', 'k', InputOption::VALUE_REQUIRED, 'text, article or decision'),
            new InputOption('jurisdiction', 'j', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The source\'s own code: cc, ca_paris (Judilibre); CE, TA75 (administratif); CJ (EUR-Lex); judiciaire, administratif (Légifrance)'),
            new InputOption('subject', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The source\'s own subject: a code\'s name (Légifrance), a theme (Judilibre), a subject-matter code (EUR-Lex)'),
            new InputOption('type', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The source\'s own nature: LOI, DECRET, arret, REG, JUDG, Ordonnance...'),
            new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Results per page', '10'),
            new InputOption('cursor', null, InputOption::VALUE_REQUIRED, 'The "next" of the page before'),
            new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'table or json', 'table'),
        ];
        $app = new Application('omnilex', '1.x');
        $app->addCommand($self->command('sources', 'Which sources are installed, configured, and what each does', [], fn ($in, $out) => $self->sources($out)));
        $app->addCommand($self->command('search', 'Documents matching a text and filters in one source', [
            $source,
            new InputArgument('text', InputArgument::OPTIONAL, 'Free text; in double quotes, the exact expression'),
            new InputOption('title', null, InputOption::VALUE_REQUIRED, 'Words of the title'),
            new InputOption('number', null, InputOption::VALUE_REQUIRED, 'A number: of a text, of an article, of a case'),
            new InputOption('from', null, InputOption::VALUE_REQUIRED, 'Dated from that day'),
            new InputOption('to', null, InputOption::VALUE_REQUIRED, 'Dated up to that day'),
            $at,
            new InputOption('sort', null, InputOption::VALUE_REQUIRED, 'relevance, newest, oldest', Query::RELEVANCE),
            ...$filters,
        ], fn ($in, $out) => $self->search($in, $out)));
        $app->addCommand($self->command('recent', 'What is new in one source since a day', [$source, new InputArgument('since', InputArgument::REQUIRED, 'YYYY-MM-DD'), ...$filters], fn ($in, $out) => $self->recent($in, $out)));
        $app->addCommand($self->command('text', 'A text as in force at a date (JSON; its content cut unless --full)', [$source, new InputArgument('id', InputArgument::REQUIRED, 'A CELEX number, an ELI, a LEGITEXT or JORFTEXT identifier, a NOR'), $at, new InputOption('full', null, InputOption::VALUE_NONE, 'The whole content')], fn ($in, $out) => $self->text($in, $out)));
        $app->addCommand($self->command('article', 'One article as in force at a date (JSON)', [$source, new InputArgument('id', InputArgument::REQUIRED, 'The article (LEGIARTI...) or its text (LEGITEXT..., a CELEX number)'), new InputArgument('number', InputArgument::OPTIONAL, 'The article\'s number, when the id names the text'), $at], fn ($in, $out) => $self->article($in, $out)));
        $app->addCommand($self->command('decision', 'One decision (JSON; its content cut unless --full)', [$source, new InputArgument('id', InputArgument::REQUIRED, 'An ECLI, a JURITEXT identifier, or the source\'s own identifier'), new InputOption('full', null, InputOption::VALUE_NONE, 'The whole content')], fn ($in, $out) => $self->decision($in, $out)));
        $app->addCommand($self->command('citations', 'The links of a document, both ways', [$source, new InputArgument('id', InputArgument::REQUIRED), new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, 'At most that many each way', '20'), new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'table or json', 'table')], fn ($in, $out) => $self->citations($in, $out)));

        return $app;
    }

    /** @param list<InputArgument|InputOption> $definition */
    private function command(string $name, string $description, array $definition, \Closure $code): Command
    {
        $command = new Command($name);
        $command->setDescription($description)->setDefinition($definition);
        $command->setCode(function (InputInterface $in, OutputInterface $out) use ($code): int {
            try {
                return $code($in, $out) ?? Command::SUCCESS;
            } catch (InvalidConfigException|\InvalidArgumentException|\ValueError $e) {
                $out->writeln('<error>'.$e->getMessage().'</error>');

                return Command::INVALID;
            } catch (OmnilexException $e) {
                $out->writeln('<error>'.$e::class.': '.$e->getMessage().'</error>');

                return Command::FAILURE;
            }
        });

        return $command;
    }

    private function sources(OutputInterface $out): void
    {
        $table = new Table($out);
        $table->setHeaders(['Source', 'Factory', 'Installed', 'Configured', 'Does', 'Holds', 'Filters by', 'Reads']);
        foreach ($this->config as $name => $source) {
            $installed = isset($this->factories[$source['factory']]);
            $missing = $this->missing($source);
            $does = $holds = $filters = $reads = '';
            if ($installed && !$missing) {
                $built = $this->registry->get($name);
                $capabilities = $built->capabilities();
                $does = implode(', ', Capabilities::operations($built));
                $holds = implode(', ', array_map(static fn (Kind $k) => $k->value, $capabilities->kinds)).($capabilities->pseudonymised ? ' (pseudonymised)' : '');
                $filters = wordwrap(implode(', ', $capabilities->criteria), 30);
                $reads = wordwrap(implode(', ', array_map(static fn ($s) => $s->value, $capabilities->identifiers)) ?: 'its own ids', 30);
            }
            $table->addRow([$name, $source['factory'], $installed ? '<info>yes</info>' : '<comment>no</comment>', $missing ? '<comment>needs '.implode(', ', $missing).'</comment>' : ($installed ? '<info>yes</info>' : ''), $does, $holds, $filters, $reads]);
        }
        $table->render();
        $out->writeln('Factories installed: '.(implode(', ', array_keys($this->factories)) ?: 'none'));
    }

    private function search(InputInterface $in, OutputInterface $out): void
    {
        $query = $this->query($in)->with([
            'text' => $in->getArgument('text'),
            'title' => $in->getOption('title'),
            'number' => $in->getOption('number'),
            'from' => $in->getOption('from'),
            'to' => $in->getOption('to'),
            'at' => $in->getOption('at'),
            'sort' => (string) $in->getOption('sort'),
        ]);
        $this->render($in, $out, $this->registry->search((string) $in->getArgument('source'))->search($query));
    }

    private function recent(InputInterface $in, OutputInterface $out): void
    {
        $since = Query::day((string) $in->getArgument('since'));
        $this->render($in, $out, $this->registry->recent((string) $in->getArgument('source'))->recent($since, $this->query($in)));
    }

    private function text(InputInterface $in, OutputInterface $out): int
    {
        $text = $this->registry->texts((string) $in->getArgument('source'))->text($this->identifier((string) $in->getArgument('id')), Query::day($in->getOption('at')));

        return $this->show($in, $out, $text);
    }

    private function article(InputInterface $in, OutputInterface $out): int
    {
        $article = $this->registry->articles((string) $in->getArgument('source'))->article($this->identifier((string) $in->getArgument('id')), $in->getArgument('number'), Query::day($in->getOption('at')));

        return $this->show($in, $out, $article, true);
    }

    private function decision(InputInterface $in, OutputInterface $out): int
    {
        return $this->show($in, $out, $this->registry->decisions((string) $in->getArgument('source'))->decision($this->identifier((string) $in->getArgument('id'))));
    }

    private function citations(InputInterface $in, OutputInterface $out): void
    {
        $citations = $this->registry->citations((string) $in->getArgument('source'))->citations($this->identifier((string) $in->getArgument('id')), max(1, (int) $in->getOption('limit')));
        if ('json' === $in->getOption('format')) {
            $out->writeln(self::json($citations));

            return;
        }
        $table = new Table($out);
        $table->setHeaders(['Relation', 'Kind', 'Id', 'Date', 'Title']);
        foreach ($citations as $citation) {
            $table->addRow([$citation->relation->value, $citation->target->kind->value, $citation->target->id, $citation->target->date?->format('Y-m-d'), self::cut($citation->target->title, 80)]);
        }
        $table->render();
        $this->stderr($out)->writeln(\sprintf('<info>%d links.</info>', \count($citations)));
    }

    private function query(InputInterface $in): Query
    {
        return new Query(
            kind: null !== $in->getOption('kind') ? Kind::from((string) $in->getOption('kind')) : null,
            jurisdictions: array_values((array) $in->getOption('jurisdiction')),
            subjects: array_values((array) $in->getOption('subject')),
            types: array_values((array) $in->getOption('type')),
            limit: max(1, (int) $in->getOption('limit')),
            cursor: $in->getOption('cursor'),
        );
    }

    /** An identifier when the string reads as one, the string itself otherwise (a source's own identifier). */
    private function identifier(string $value): Identifier|string
    {
        return Identifier::parse($value) ?? $value;
    }

    private function render(InputInterface $in, OutputInterface $out, Results $results): void
    {
        if ('json' === $in->getOption('format')) {
            $out->writeln(self::json($results));

            return;
        }
        $table = new Table($out);
        $table->setHeaders(['Date', 'Kind', 'Type', 'Id', 'Identifiers', 'Title']);
        foreach ($results as $reference) {
            $table->addRow([$reference->date?->format('Y-m-d'), $reference->kind->value, $reference->type, $reference->id, self::cut(implode(' ', $reference->identifiers->keys()), 44), self::cut($reference->title, 70)]);
        }
        $table->render();
        $this->stderr($out)->writeln(\sprintf('<info>%d of %s%s.</info>', \count($results), $results->total ?? '?', null !== $results->next ? ', next: --cursor '.$results->next : ''));
    }

    private function show(InputInterface $in, OutputInterface $out, ?object $model, bool $full = false): int
    {
        if (null === $model) {
            $this->stderr($out)->writeln('<comment>Not found.</comment>');

            return Command::FAILURE;
        }
        $data = self::normalize($model);
        if (!$full && !($in->hasOption('full') && $in->getOption('full'))) {
            // The texts run to megabytes: their start, unless asked whole.
            unset($data['html'], $data['raw']);
            foreach (['content'] as $key) {
                if (\is_string($data[$key] ?? null) && mb_strlen($data[$key]) > 1200) {
                    $data[$key] = mb_substr($data[$key], 0, 1200).\sprintf(' […] (%d characters; --full for all)', mb_strlen($data[$key]));
                }
            }
            if (isset($data['articles']) && \is_array($data['articles'])) {
                $data['articles'] = \sprintf('%d articles (--full to list them)', \count($data['articles']));
            }
        } else {
            unset($data['html']);
        }
        $out->writeln((string) json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }

    private static function cut(string $text, int $width): string
    {
        return mb_strlen($text) > $width ? mb_substr($text, 0, $width - 1).'…' : $text;
    }

    /** @param array{needs: list<string>} $source */
    private function missing(array $source): array
    {
        return array_values(array_filter($source['needs'], static fn (string $key) => false === getenv($key) || '' === getenv($key)));
    }

    private function stderr(OutputInterface $out): OutputInterface
    {
        return $out instanceof ConsoleOutputInterface ? $out->getErrorOutput() : $out;
    }

    private static function json(mixed $value): string
    {
        return (string) json_encode(self::normalize($value), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    /** Models as JSON shows them: identifiers by scheme, enums as their value, dates as days. */
    private static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Identifiers => $value->toArray(),
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof Citation => ['relation' => $value->relation->value, 'target' => self::normalize($value->target), 'note' => $value->note],
            \is_object($value) => array_map(self::normalize(...), get_object_vars($value)),
            \is_array($value) => array_map(self::normalize(...), $value),
            default => $value,
        };
    }
}
