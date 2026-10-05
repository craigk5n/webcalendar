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
}
