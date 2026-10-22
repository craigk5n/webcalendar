<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Release archive smoke test — the REVERSE of ReleaseFilesConsistencyTest.
 *
 * ReleaseFilesConsistencyTest checks the forward direction: every entry in
 * release-files resolves to a real, tracked file. It CANNOT catch a runtime
 * file that shipped code needs but that was never *added* to release-files —
 * there is nothing listed for it to check against. That reverse gap is what
 * broke releases in issue #666: includes/mcp-loader.php is require_once'd
 * unconditionally at includes/init.php:63, but was never in the manifest, so
 * every release fataled on startup. The same shape hid the signed-manifest
 * Security classes (required by the shipped security_audit.php).
 *
 * This test stages the release exactly as .github/workflows/release.yml does
 * (copy each release-files entry into a temp tree) and then:
 *
 *   (a) boots includes/init.php INSIDE the staged tree and fails on any
 *       "Failed opening required" fatal — the real #666 failure mode; catches
 *       gaps in the unconditional bootstrap require chain.
 *
 *   (b) statically resolves every require/include in shipped PHP against the
 *       staged tree — catches requires in function bodies that a plain boot
 *       never reaches (e.g. security_audit.php's Security\* classes).
 *
 * Limitation: (b) only resolves static string literals and `__DIR__ . '...'`.
 * Dynamic requires (variable/computed paths) are skipped — they cannot be
 * resolved without executing the code path, which is what (a) is for.
 * Anything under vendor/ is ignored in both checks: the composer tree is
 * intentionally never shipped (opt-in) and is handled gracefully at runtime.
 */
final class ReleaseArchiveSmokeTest extends TestCase
{
  private const REPO_ROOT = __DIR__ . '/..';

  private static ?string $stage = null;

  public static function setUpBeforeClass(): void
  {
    self::$stage = self::stageRelease();
  }

  public static function tearDownAfterClass(): void
  {
    if (self::$stage !== null) {
      self::rrmdir(self::$stage);
      self::$stage = null;
    }
  }

  /**
   * (a) Boot the staged init.php and assert no bootstrap require is missing.
   *
   * The staged tree has no settings.php, so config's do_config() (reached
   * at init.php:65) dies cleanly right after the bootstrap require chain has
   * run — it either redirects to the (now-shipped, issue #667) wizard or
   * dies for lack of config. A file missing from that chain — like
   * mcp-loader.php at line 63 — surfaces as a "Failed opening required"
   * fatal BEFORE that death, which is exactly what we key on.
   */
  public function testStagedInitBootsWithoutMissingIncludes(): void
  {
    $bootName = '__smoke_boot.php';
    $boot = <<<'PHP'
<?php
// Satisfy the direct-access guard at includes/init.php top.
$_SERVER['PHP_SELF'] = '/__smoke_boot.php';
error_reporting(E_ALL);
// A failed require is a fatal Error; capture its message on shutdown so the
// parent process can distinguish "missing include" from the expected
// no-database death that follows in do_config().
register_shutdown_function(static function () {
  $e = error_get_last();
  if ($e !== null && stripos($e['message'], 'Failed opening required') !== false) {
    fwrite(STDERR, "SMOKE_MISSING_INCLUDE: {$e['message']}\n");
  }
});
require 'includes/init.php';
PHP;
    file_put_contents(self::$stage . '/' . $bootName, $boot);

    [$output] = self::runPhp($bootName, self::$stage);

    $missing = [];
    foreach (explode("\n", $output) as $line) {
      if (stripos($line, 'Failed opening required') === false) {
        continue;
      }
      if (preg_match("#required '([^']+)'#", $line, $m)) {
        // vendor/ is composer-opt-in and never shipped — not a manifest bug.
        if (strpos($m[1], 'vendor/') !== false) {
          continue;
        }
        $missing[] = $m[1];
      } else {
        $missing[] = trim($line);
      }
    }
    $missing = array_values(array_unique($missing));

    self::assertSame(
      [],
      $missing,
      "Booting the staged release tree hit a missing require target: a file "
      . "needed during startup is absent from release-files. This is the "
      . "issue #666 failure mode.\nMissing:\n  " . implode("\n  ", $missing)
      . "\n\n--- staged boot output ---\n" . $output
    );
  }

  /**
   * (b) Every static require/include in shipped code must itself be shipped.
   *
   * Only flags when the required path resolves to a REAL file in the repo
   * that is absent from release-files — so mis-parsed or dynamic paths (which
   * resolve to nothing) never produce false positives.
   */
  public function testEveryStaticRequireInShippedCodeIsShipped(): void
  {
    $shipped = self::listedPaths();
    $missing = [];

    foreach (array_keys($shipped) as $rel) {
      if (substr($rel, -4) !== '.php') {
        continue;
      }
      $abs = self::REPO_ROOT . '/' . $rel;
      if (!is_file($abs)) {
        continue; // forward drift — ReleaseFilesConsistencyTest owns that.
      }
      foreach (self::staticRequireCandidates($abs, $rel) as $candidates) {
        $resolved = null;
        foreach ($candidates as $cand) {
          if ($cand !== '' && is_file(self::REPO_ROOT . '/' . $cand)) {
            $resolved = $cand;
            break;
          }
        }
        if ($resolved === null) {
          continue; // dynamic / points outside the repo — can't judge.
        }
        if (strpos($resolved, 'vendor/') === 0) {
          continue;
        }
        if (!isset($shipped[$resolved])) {
          $missing[] = "$rel  requires  $resolved  (real repo file, NOT in release-files)";
        }
      }
    }

    $missing = array_values(array_unique($missing));
    sort($missing);

    self::assertSame(
      [],
      $missing,
      count($missing) . " shipped file(s) require a real repo file that is not "
      . "in release-files. Add the target(s) to release-files:\n  "
      . implode("\n  ", $missing)
    );
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /** Copy every release-files entry into a fresh temp tree; return its path. */
  private static function stageRelease(): string
  {
    $stage = sys_get_temp_dir() . '/wc-release-stage-' . getmypid() . '-' . uniqid();
    if (!mkdir($stage, 0700, true) && !is_dir($stage)) {
      self::fail("Could not create staging dir: $stage");
    }
    foreach (array_keys(self::listedPaths()) as $rel) {
      $src = self::REPO_ROOT . '/' . $rel;
      if (!is_file($src)) {
        continue; // forward-drift concern, not this test's job.
      }
      $dst = $stage . '/' . $rel;
      $dir = dirname($dst);
      if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        self::fail("Could not create staged dir: $dir");
      }
      if (!copy($src, $dst)) {
        self::fail("Could not stage file: $rel");
      }
    }
    return $stage;
  }

  /** @return array<string, true> release-files entries (blank/comment skipped) */
  private static function listedPaths(): array
  {
    $contents = file_get_contents(self::REPO_ROOT . '/release-files');
    if ($contents === false) {
      self::fail('Could not read release-files');
    }
    $out = [];
    foreach (explode("\n", $contents) as $raw) {
      $line = trim($raw);
      if ($line === '' || $line[0] === '#') {
        continue;
      }
      $out[$line] = true;
    }
    return $out;
  }

  /**
   * Extract static require/include targets from a PHP file via the tokenizer
   * (so matches inside comments/strings are ignored).
   *
   * @return array<int, array<int, string>> one candidate-list per require;
   *   each inner list is repo-relative paths to try, first real file wins.
   */
  private static function staticRequireCandidates(string $absFile, string $relFile): array
  {
    $src = file_get_contents($absFile);
    if ($src === false) {
      return [];
    }
    $tokens = @token_get_all($src);
    $dirOfFile = dirname($relFile); // repo-relative dir, e.g. "includes"
    $requireIds = [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE];
    $result = [];
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
      $tok = $tokens[$i];
      if (!is_array($tok) || !in_array($tok[0], $requireIds, true)) {
        continue;
      }
      // Collect the argument tokens up to the statement terminator.
      $args = [];
      for ($j = $i + 1; $j < $count; $j++) {
        $tj = $tokens[$j];
        if ($tj === ';') {
          break;
        }
        if (is_array($tj)) {
          if ($tj[0] === T_WHITESPACE) {
            continue;
          }
          $args[] = $tj;
        } elseif ($tj !== '(' && $tj !== ')') {
          $args[] = $tj; // keep punctuation such as the '.' concat operator
        }
      }
      $candidates = self::interpretRequireArgs($args, $dirOfFile);
      if ($candidates !== []) {
        $result[] = $candidates;
      }
    }
    return $result;
  }

  /**
   * Turn require-argument tokens into repo-relative candidate paths.
   * Handles two shapes: a bare string literal, and `__DIR__ . '/rel'`.
   *
   * @param array<int, array{0:int,1:string}|string> $args
   * @return array<int, string>
   */
  private static function interpretRequireArgs(array $args, string $dirOfFile): array
  {
    // Bare literal: require_once 'includes/mcp-loader.php';
    if (count($args) === 1 && is_array($args[0]) && $args[0][0] === T_CONSTANT_ENCAPSED_STRING) {
      $lit = self::unquote($args[0][1]);
      return array_values(array_unique([
        self::normalize($lit),                       // relative to repo root
        self::normalize($dirOfFile . '/' . $lit),    // relative to the file
      ]));
    }
    // __DIR__ . '/includes/classes/Security/Foo.php';
    if (
      count($args) === 3
      && is_array($args[0]) && $args[0][0] === T_DIR
      && $args[1] === '.'
      && is_array($args[2]) && $args[2][0] === T_CONSTANT_ENCAPSED_STRING
    ) {
      $lit = ltrim(self::unquote($args[2][1]), '/');
      return [self::normalize($dirOfFile . '/' . $lit)];
    }
    return []; // dynamic / unrecognized — skip.
  }

  private static function unquote(string $token): string
  {
    return trim($token, "'\"");
  }

  /** Collapse "." and ".." segments in a repo-relative path. */
  private static function normalize(string $path): string
  {
    $path = str_replace('\\', '/', $path);
    $parts = [];
    foreach (explode('/', $path) as $seg) {
      if ($seg === '' || $seg === '.') {
        continue;
      }
      if ($seg === '..') {
        array_pop($parts);
        continue;
      }
      $parts[] = $seg;
    }
    return implode('/', $parts);
  }

  /**
   * Run a PHP script with CWD set to the staged tree so its relative includes
   * resolve against the staged copy — not the full repo working tree.
   *
   * @return array{0:string,1:int} combined stdout+stderr, exit code
   */
  private static function runPhp(string $scriptRelToCwd, string $cwd): array
  {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptRelToCwd);
    $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $desc, $pipes, $cwd);
    if (!is_resource($proc)) {
      self::fail('Could not launch PHP for the staged boot');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return [(string) $stdout . (string) $stderr, $code];
  }

  private static function rrmdir(string $dir): void
  {
    if (!is_dir($dir)) {
      return;
    }
    $items = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
      $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
  }
}
