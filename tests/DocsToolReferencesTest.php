<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Documentation that tells an administrator to run a script must name a
 * script that exists.
 *
 * docs/admin-guide.md, faq.md, security.md and troubleshooting.md all told
 * administrators to run `php tools/send_test_email.php` to test their mail
 * configuration. That file was never committed -- it lived on one developer's
 * machine, gitignored, with personal addresses hardcoded in it -- so no
 * release has ever contained it and the instruction has always failed with
 * "No such file or directory". All four documents ship.
 *
 * Only docs/ is checked, and not docs/archive/. A changelog or a roadmap that
 * names a file in order to record its removal is doing its job; a manual that
 * does it is wrong.
 */
final class DocsToolReferencesTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * @return array<string, list<string>> file => scripts it names
   */
  private function referencedScripts(): array
  {
    $found = [];

    $docs = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(self::ROOT . '/docs')
    );

    foreach ($docs as $doc) {
      if (!$doc->isFile() || $doc->getExtension() !== 'md') {
        continue;
      }

      $path = (string) $doc->getPathname();
      if (str_contains($path, '/archive/')) {
        continue;
      }

      $text = (string) file_get_contents($path);
      // Matches tools/x.php and bin/x.php wherever they appear, including
      // inside an example path such as /some/path/here/tools/x.php.
      if (!preg_match_all('#\b(tools|bin)/([A-Za-z0-9_.-]+\.php)#',
        $text, $matches, PREG_SET_ORDER)) {
        continue;
      }

      $rel = substr($path, strlen(self::ROOT) + 1);
      foreach ($matches as $match) {
        $script = $match[1] . '/' . $match[2];
        if (!in_array($script, $found[$rel] ?? [], true)) {
          $found[$rel][] = $script;
        }
      }
    }

    return $found;
  }

  public function testDocumentationNamesOnlyScriptsThatExist(): void
  {
    $missing = [];

    foreach ($this->referencedScripts() as $doc => $scripts) {
      foreach ($scripts as $script) {
        if (!is_file(self::ROOT . '/' . $script)) {
          $missing[] = "$doc names $script, which does not exist";
        }
      }
    }

    self::assertSame([], $missing, count($missing) . ' documented script(s) '
      . "are missing from the repository. Either the script should be "
      . "committed or the documentation should stop asking for it:\n  "
      . implode("\n  ", $missing));
  }

  /**
   * Existing locally is not enough. A script the documentation names has to
   * be in git, or it reaches nobody but the developer who wrote it -- which
   * is exactly how tools/send_test_email.php stayed missing for years while
   * four manuals told people to run it.
   */
  public function testDocumentedScriptsAreTracked(): void
  {
    $tracked = [];
    $status = 0;
    @exec('git -C ' . escapeshellarg(self::ROOT) . ' ls-files 2>/dev/null',
      $tracked, $status);

    if ($status !== 0 || $tracked === []) {
      self::markTestSkipped('git ls-files unavailable.');
    }

    $tracked = array_flip($tracked);
    $untracked = [];

    foreach ($this->referencedScripts() as $doc => $scripts) {
      foreach ($scripts as $script) {
        if (is_file(self::ROOT . '/' . $script)
          && !isset($tracked[$script])) {
          $untracked[] = "$doc names $script, which is not in git";
        }
      }
    }

    self::assertSame([], $untracked, implode("\n  ", $untracked));
  }
}
