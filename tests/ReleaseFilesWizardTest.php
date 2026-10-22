<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression guard for issue #667 — the wizard/ directory must ship.
 *
 * The release ZIP is assembled solely from release-files (release.yml copies
 * each listed path; build-manifest.php hashes the same staged tree). The
 * wizard/ directory — the installer AND the DB-upgrade path — was never added
 * to release-files, so every release from 1.9.13 through 1.9.20 shipped
 * without it. includes/config.php guards each wizard redirect behind
 * file_exists('wizard/index.php'), so the missing directory produced no fatal:
 * the app just silently never offered the upgrade, leaving the schema stale.
 *
 * That file_exists() guard is also why ReleaseArchiveSmokeTest cannot catch
 * this: its static require-resolver only follows require/include, and the
 * wizard is reached via a guarded header('Location: ...') redirect, not a
 * require. This test closes that gap by asserting the manifest CONTENT: every
 * git-tracked wizard/ file must be listed in release-files.
 */
final class ReleaseFilesWizardTest extends TestCase
{
  private const RELEASE_FILES = __DIR__ . '/../release-files';
  private const REPO_ROOT = __DIR__ . '/..';

  public function testEveryTrackedWizardFileIsInReleaseFiles(): void
  {
    $tracked = $this->gitTrackedWizardFiles();
    if ($tracked === null) {
      self::markTestSkipped('git ls-files unavailable (no .git dir).');
    }
    self::assertNotEmpty(
      $tracked,
      'Expected wizard/ to contain git-tracked files; found none.'
    );

    $listed = $this->listedPaths();

    $missing = [];
    foreach ($tracked as $rel) {
      if (!isset($listed[$rel])) {
        $missing[] = $rel;
      }
    }
    sort($missing);

    self::assertSame(
      [],
      $missing,
      count($missing) . " wizard/ file(s) tracked in git but NOT listed in "
      . "release-files. The wizard is the installer and the DB-upgrade path; "
      . "if it is not in the manifest it does not ship, and ZIP-based upgrades "
      . "silently skip DB migrations (issue #667). Add these to release-files:\n"
      . "  " . implode("\n  ", $missing)
    );
  }

  /** @return list<string>|null tracked wizard/ relpaths, or null if git unavailable */
  private function gitTrackedWizardFiles(): ?array
  {
    if (!is_dir(self::REPO_ROOT . '/.git')) {
      return null;
    }
    $cmd = 'git -C ' . escapeshellarg(self::REPO_ROOT) . ' ls-files wizard/';
    $output = [];
    $status = 0;
    @exec($cmd . ' 2>/dev/null', $output, $status);
    if ($status !== 0) {
      return null;
    }
    return array_values(array_filter($output, static fn($l) => $l !== ''));
  }

  /** @return array<string, true> keys are listed relpaths */
  private function listedPaths(): array
  {
    $contents = file_get_contents(self::RELEASE_FILES);
    self::assertNotFalse($contents, 'Could not read release-files');

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
}
