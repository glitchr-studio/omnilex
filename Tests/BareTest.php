<?php

namespace Omnilex\Tests;

use Omnilex\Bridge\Symfony\OmnilexBundle;
use Omnilex\Registry;
use Omnilex\Eurlex\EurlexSourceFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Omnilex outside Symfony: the harness's bare script (docker/harness/bin/bare)
 * run in a PHP process of its own - this one has loaded the bundle's tests -
 * builds the registry by hand, reads a regulation as in force at a date
 * through omnilex/eurlex from recorded answers, and reports every class and
 * file PHP loaded on the way. None may be a framework's.
 */
final class BareTest extends TestCase
{
    private const FRAMEWORK = '~^(?:Symfony\\\\Component\\\\(?:DependencyInjection|Config|HttpKernel|HttpFoundation)|Symfony\\\\Bundle|Doctrine|Twig)\\\\~';
    private const FRAMEWORK_FILES = '~/vendor/(?:symfony/(?:dependency-injection|config|http-kernel|http-foundation|[a-z-]*bundle)|doctrine|twig)/~';

    public function testTheRegistryIsBuiltByHandAndNoClassOfAFrameworkIsLoaded(): void
    {
        [$status, $report] = self::php([__DIR__.'/../docker/harness/bin/bare', '--recorded', '--json']);

        self::assertSame(0, $status);
        self::assertContains(Registry::class, $report['symbols'], 'the registry was built there');
        self::assertSame([], self::framework($report), 'no class nor file of a framework');
        $installed = array_values(array_filter(array_column(require __DIR__.'/../docker/harness/plugins.php', 1), 'class_exists'));
        foreach ($installed as $factory) {
            self::assertContains($factory, $report['symbols'], 'every source package installed, its factory built');
        }
        self::assertCount(\count($installed), $report['sources']);
    }

    public function testASourceReadsATextAtADateFromRecordedAnswersWithNoClassOfAFrameworkLoaded(): void
    {
        if (!class_exists(EurlexSourceFactory::class)) {
            self::markTestSkipped('omnilex/eurlex is not installed.');
        }
        [$status, $report] = self::php([__DIR__.'/../docker/harness/bin/bare', '--recorded', '--json']);

        self::assertSame(0, $status);
        self::assertTrue($report['recorded']);
        self::assertSame('32016R0679', $report['text']['id']);
        self::assertStringStartsWith('Règlement (UE) 2016/679 du Parlement européen et du Conseil du 27 avril 2016', $report['text']['title']);
        self::assertSame(['REG', '2016-04-27', true], [$report['text']['type'], $report['text']['date'], $report['text']['in_force']]);
        self::assertSame(['id' => '02016R0679-20160504', 'from' => '2016-05-04', 'status' => 'in_force'], $report['text']['version'], 'the consolidated version of 1 June 2018');
        self::assertSame(['32016R0679', '02016R0679-20160504'], $report['text']['versions']);
        self::assertSame(['search', 'text', 'article', 'decision', 'citations', 'recent'], $report['sources']['eurlex']);
        self::assertContains('Symfony\\Component\\HttpClient\\MockHttpClient', $report['symbols'], 'the answers came through the HTTP client given');
        self::assertSame([], self::framework($report), 'no class nor file of a framework');
    }

    /** The check is not blind: the same report, once the bundle is loaded, names the framework. */
    public function testTheBundleDoesLoadTheFramework(): void
    {
        if (!class_exists(AbstractBundle::class)) {
            self::markTestSkipped('symfony/http-kernel is not installed.');
        }
        [$status, $report] = self::php(['-r', 'require getenv("OMNILEX_AUTOLOAD"); class_exists($argv[1]) || exit(2); echo json_encode(["symbols" => [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()], "files" => get_included_files()]);', '--', OmnilexBundle::class]);

        self::assertSame(0, $status);
        $framework = self::framework($report);
        self::assertContains(AbstractBundle::class, $framework);
        self::assertNotEmpty(preg_grep('~/symfony/http-kernel/~', $framework));
    }

    /**
     * @param array{symbols: list<string>, files: list<string>} $report
     *
     * @return list<string> the classes, interfaces, traits and files of a framework among those loaded
     */
    private static function framework(array $report): array
    {
        return [...array_values(preg_grep(self::FRAMEWORK, $report['symbols'])), ...array_values(preg_grep(self::FRAMEWORK_FILES, $report['files']))];
    }

    /**
     * Runs PHP apart, on the autoloader of this run.
     *
     * @param list<string> $arguments
     *
     * @return array{int, array<string, mixed>} the exit status, the JSON printed
     */
    private static function php(array $arguments): array
    {
        $autoload = \dirname((string) (new \ReflectionClass(\Composer\Autoload\ClassLoader::class))->getFileName(), 2).'/autoload.php';
        $process = proc_open([\PHP_BINARY, ...$arguments], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['OMNILEX_AUTOLOAD' => $autoload] + getenv());
        self::assertIsResource($process);
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        $status = proc_close($process);
        $report = json_decode($out, true);
        self::assertIsArray($report, 'PHP exited '.$status.': '.$err.$out);

        return [$status, $report];
    }
}
