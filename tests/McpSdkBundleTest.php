<?php
/**
 * The MCP SDK ships inside the tree, in includes/classes/mcp-sdk/, copied
 * out of vendor/ by tools/build-mcp-sdk.php (`make mcp-sdk`). These tests
 * keep that copy honest.
 *
 * Why a copy at all: neither the release zip nor the Docker image runs
 * Composer, so until the bundle existed the MCP server was unavailable on
 * every install that was not a git checkout (issue #796). The copy is what
 * ships; vendor/ is only the source it is refreshed from.
 *
 * What can go wrong, and the test that catches it:
 *  - Dependabot bumps mcp/sdk but nobody reruns the build: the versions in
 *    packages.json no longer match composer.lock, or a file differs from
 *    vendor/ (testBundleMatchesComposerLock, testBundleFilesMatchVendor).
 *  - A file is added to or removed from the bundle by hand, so release-files
 *    and the directory disagree (testReleaseFilesListsExactlyTheBundle).
 *  - The generated autoloader cannot load a class the SDK needs. PHPUnit
 *    itself loads Composer's autoloader, which would mask that in-process,
 *    so the check runs in a child PHP with only the bundle's autoloader
 *    (testBundleLoadsWithoutComposer). That is the situation of every
 *    release install, and the gap the old hand-written loader had: it mapped
 *    three namespaces and the SDK imports from a dozen.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../tools/build-mcp-sdk.php';

class McpSdkBundleTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';
  private const BUNDLE = self::ROOT . '/' . MCP_SDK_BUNDLE_DIR;

  public function testBundleMatchesComposerLock(): void
  {
    $packages = mcp_sdk_lock_packages(self::ROOT . '/composer.lock');
    $expected = [];
    foreach (mcp_sdk_closure($packages) as $name) {
      $expected[$name] = $packages[$name]['version'];
    }

    $actual = json_decode((string) file_get_contents(self::BUNDLE . '/packages.json'), true);

    $this->assertSame($expected, $actual,
      'includes/classes/mcp-sdk/packages.json does not match the mcp/sdk closure in '
      . 'composer.lock. Run `composer install` then `make mcp-sdk` and commit the result.');
  }

  public function testBundleFilesMatchVendor(): void
  {
    $vendor = self::ROOT . '/vendor';
    if (!is_dir($vendor . '/' . MCP_SDK_ROOT_PACKAGE)) {
      self::markTestSkipped('vendor/ is not installed; cannot compare the bundle to its source.');
    }
    $packages = mcp_sdk_lock_packages(self::ROOT . '/composer.lock');

    $copied = [];
    foreach (mcp_sdk_closure($packages) as $name) {
      foreach (mcp_sdk_package_files($vendor . '/' . $name) as $relative) {
        $copied[] = MCP_SDK_BUNDLE_DIR . '/' . $name . '/' . $relative;
      }
    }
    $expected = $copied;
    $expected[] = MCP_SDK_BUNDLE_DIR . '/autoload.php';
    $expected[] = MCP_SDK_BUNDLE_DIR . '/packages.json';
    sort($expected);

    $this->assertSame($expected, mcp_sdk_bundle_files(self::ROOT),
      'The set of files in includes/classes/mcp-sdk/ is not what tools/build-mcp-sdk.php '
      . 'would copy from vendor/. Run `make mcp-sdk` and commit the result.');

    // Only the copied files have a vendor/ counterpart; autoload.php and
    // packages.json are generated, and vendor/autoload.php is Composer's.
    $stale = [];
    foreach ($copied as $path) {
      $inVendor = preg_replace('#^' . preg_quote(MCP_SDK_BUNDLE_DIR, '#') . '/#', $vendor . '/', $path);
      if (hash_file('sha256', $inVendor) !== hash_file('sha256', self::ROOT . '/' . $path)) {
        $stale[] = $path;
      }
    }
    $this->assertSame([], $stale,
      'These bundled files differ from vendor/. Run `make mcp-sdk` and commit the result.');
  }

  public function testReleaseFilesListsExactlyTheBundle(): void
  {
    $lines = file(self::ROOT . '/release-files', FILE_IGNORE_NEW_LINES);
    $this->assertNotFalse($lines);
    $begin = array_search(MCP_SDK_MARK_BEGIN, $lines, true);
    $end = array_search(MCP_SDK_MARK_END, $lines, true);
    $this->assertNotFalse($begin, 'release-files has no mcp-sdk BEGIN marker.');
    $this->assertNotFalse($end, 'release-files has no mcp-sdk END marker.');
    $this->assertGreaterThan($begin, $end);

    $listed = array_slice($lines, $begin + 1, $end - $begin - 1);
    $this->assertSame(mcp_sdk_bundle_files(self::ROOT), $listed,
      'The mcp-sdk block in release-files does not list exactly the files under '
      . 'includes/classes/mcp-sdk/. Run `make mcp-sdk` rather than editing it by hand.');
  }

  public function testBundleLoadsWithoutComposer(): void
  {
    // One class from every bundled package that has classes, plus the
    // polyfill's function-defining bootstrap, resolved by the bundle's
    // autoloader alone in a fresh PHP process.
    $classes = [
      'Mcp\Server',
      'Mcp\Server\Protocol',
      'Mcp\Server\Transport\StdioTransport',
      'Mcp\Capability\Discovery\SchemaValidator',
      'Doctrine\Deprecations\Deprecation',
      'Opis\JsonSchema\Validator',
      'Opis\String\UnicodeString',
      'Opis\Uri\Uri',
      'Http\Discovery\Psr17Factory',
      'phpDocumentor\Reflection\DocBlockFactory',
      'phpDocumentor\Reflection\Types\Context',
      'phpDocumentor\Reflection\TypeResolver',
      'PHPStan\PhpDocParser\Lexer\Lexer',
      'Psr\Clock\ClockInterface',
      'Psr\Container\ContainerInterface',
      'Psr\EventDispatcher\EventDispatcherInterface',
      'Psr\Http\Client\ClientInterface',
      'Psr\Http\Message\RequestFactoryInterface',
      'Psr\Http\Message\ResponseInterface',
      'Psr\Http\Server\RequestHandlerInterface',
      'Psr\Http\Server\MiddlewareInterface',
      'Psr\Log\NullLogger',
      'Symfony\Component\Uid\Uuid',
      'Symfony\Polyfill\Uuid\Uuid',
      'Webmozart\Assert\Assert',
    ];
    $script = <<<'PHP'
      require $argv[1];
      $out = [];
      foreach (array_slice($argv, 2) as $class) {
        $out[$class] = class_exists($class) || interface_exists($class);
      }
      // opis/string reads its case tables from res/ at runtime, which is
      // outside src/ and so outside a naive "copy the PSR-4 directory" rule.
      $out['opis-res'] = (string) \Opis\String\UnicodeString::from('abc')->toUpper() === 'ABC';
      $out['builder'] = \Mcp\Server::builder() instanceof \Mcp\Server\Builder;
      echo json_encode($out);
      PHP;
    $cmd = escapeshellarg(PHP_BINARY) . ' -d display_errors=stderr -r ' . escapeshellarg($script)
      . ' ' . escapeshellarg(self::BUNDLE . '/autoload.php')
      . ' ' . implode(' ', array_map('escapeshellarg', $classes)) . ' 2>&1';
    exec($cmd, $lines, $status);
    $output = implode("\n", $lines);
    $this->assertSame(0, $status, "Child PHP failed:\n$output");

    $result = json_decode($output, true);
    $this->assertIsArray($result, "Child PHP did not print JSON:\n$output");
    $missing = array_keys(array_filter($result, static fn($ok): bool => $ok !== true));
    $this->assertSame([], $missing,
      'The bundle autoloader could not resolve these with vendor/autoload.php absent.');
  }

  public function testLoaderRequiresTheBundleUnconditionally(): void
  {
    // The pre-#796 loader guarded every require with file_exists(), which is
    // how a missing SDK stayed silent. The loader must now fail loudly.
    $source = (string) file_get_contents(self::ROOT . '/includes/mcp-loader.php');
    $this->assertStringContainsString(
      "require_once __DIR__ . '/classes/mcp-sdk/autoload.php';", $source);
    $this->assertStringNotContainsString('file_exists', $source);
    $this->assertStringNotContainsString('is_file', $source);
  }

  /** @dataProvider loaderOrders */
  public function testComposerAndBundleExecuteSharedBootstrapsOnce(bool $composerFirst): void
  {
    $loaders = [self::ROOT . '/vendor/autoload.php', self::ROOT . '/includes/mcp-loader.php'];
    if (!$composerFirst) {
      $loaders = array_reverse($loaders);
    }
    $script = <<<'PHP'
      set_error_handler(static function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
      });
      require $argv[1];
      require $argv[2];
      $bootstraps = array_values(array_filter(get_included_files(), static fn ($file) =>
        str_ends_with($file, '/symfony/polyfill-uuid/bootstrap.php')));
      echo json_encode([
        'bootstraps' => $bootstraps,
        'registered' => !empty($GLOBALS['__composer_autoload_files'][md5('symfony/polyfill-uuid:bootstrap.php')]),
        'uuid' => (string) Symfony\Component\Uid\Uuid::v4(),
        'builder' => Mcp\Server::builder() instanceof Mcp\Server\Builder,
      ]);
      PHP;
    $result = $this->runChild($script, $loaders);
    self::assertCount(1, $result['bootstraps']);
    self::assertStringContainsString($composerFirst ? '/vendor/' : '/includes/classes/mcp-sdk/', $result['bootstraps'][0]);
    self::assertTrue($result['registered']);
    self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $result['uuid']);
    self::assertTrue($result['builder']);
  }

  public static function loaderOrders(): array
  {
    return ['composer first' => [true], 'bundle first' => [false]];
  }

  public function testGeneratedFilesHonorComposerRegistryAndLoadIndependently(): void
  {
    $directory = sys_get_temp_dir() . '/mcp-loader-' . bin2hex(random_bytes(8));
    mkdir($directory . '/fixture/bootstrap', 0700, true);
    try {
      $packages = ['fixture/bootstrap' => ['autoload' => ['files' => ['one.php', 'two.php']]]];
      file_put_contents($directory . '/fixture/bootstrap/one.php', '<?php $GLOBALS["runs"][] = "one";');
      file_put_contents($directory . '/fixture/bootstrap/two.php', '<?php $GLOBALS["runs"][] = "two";');
      file_put_contents($directory . '/autoload.php', mcp_sdk_autoloader_source(mcp_sdk_autoload_map($packages, array_keys($packages))));
      $script = <<<'PHP'
        $GLOBALS['runs'] = [];
        if ($argv[2] === 'preloaded') {
          $GLOBALS['__composer_autoload_files'][md5('fixture/bootstrap:one.php')] = true;
        }
        require $argv[1];
        require $argv[1];
        echo json_encode([
          'runs' => $GLOBALS['runs'],
          'registered' => !empty($GLOBALS['__composer_autoload_files'][md5('fixture/bootstrap:two.php')]),
        ]);
        PHP;
      self::assertSame(['runs' => ['two'], 'registered' => true], $this->runChild($script, [$directory . '/autoload.php', 'preloaded']));
      self::assertSame(['runs' => ['one', 'two'], 'registered' => true], $this->runChild($script, [$directory . '/autoload.php', 'empty']));
    } finally {
      mcp_sdk_remove_dir($directory);
    }
  }

  public function testGeneratedLoaderMatchesGeneratorAndComposerFileIdentifiers(): void
  {
    $packages = mcp_sdk_lock_packages(self::ROOT . '/composer.lock');
    $map = mcp_sdk_autoload_map($packages, mcp_sdk_closure($packages));
    self::assertSame(mcp_sdk_autoloader_source($map), file_get_contents(self::BUNDLE . '/autoload.php'));
    $composerFiles = require self::ROOT . '/vendor/composer/autoload_files.php';
    foreach ($map['files'] as $identifier => $relative) {
      self::assertArrayHasKey($identifier, $composerFiles);
      self::assertSame(realpath(self::ROOT . '/vendor/' . $relative), realpath($composerFiles[$identifier]));
    }
  }

  private function runChild(string $script, array $arguments): array
  {
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=stderr', '-r', $script, ...$arguments],
      [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    self::assertIsResource($process);
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    self::assertSame(0, proc_close($process), $errors);
    self::assertSame('', $errors);
    $result = json_decode($output, true);
    self::assertIsArray($result, $output);
    return $result;
  }
}
