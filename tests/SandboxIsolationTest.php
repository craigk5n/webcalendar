<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * The sandbox cannot write to the working copy.
 *
 * This matters more here than in most projects, because a WebCalendar
 * checkout is frequently also a live installation -- this one is. Bind
 * mounting the tree into a container and installing into it is how
 * tests/run-*-install-tests.sh deleted a live includes/settings.php and took
 * a calendar down on 2026-09-24.
 *
 * Two independent properties keep that from happening again, and both are
 * asserted here because either one silently regressing would restore the
 * hazard while everything still appeared to work:
 *
 *   The code is mounted read-only, so the kernel refuses the write.
 *   WEBCALENDAR_USE_ENV=true, so do_config() reads the database settings from
 *   the environment and never opens includes/settings.php at all -- a live
 *   configuration in the mount is ignored rather than merely protected.
 */
final class SandboxIsolationTest extends TestCase
{
  private const COMPOSE = __DIR__ . '/../docker/docker-compose-sqlite-dev.yml';
  private const MAKEFILE = __DIR__ . '/../Makefile';

  /**
   * The compose file with its comment lines removed.
   *
   * The notes at the top of that file name WEBCALENDAR_USE_ENV=true and the
   * read-only mount in order to explain them, so a search of the whole text
   * was satisfied by the explanation and would have stayed green with the
   * setting itself turned off. Found by turning it off.
   */
  private function compose(): string
  {
    $s = file_get_contents(self::COMPOSE);
    self::assertIsString($s, 'could not read the sandbox compose file');

    return SourceText::yaml($s);
  }

  public function testTheWorkingCopyIsMountedReadOnly(): void
  {
    self::assertMatchesRegularExpression(
      '#^\s*-\s*\.\.:/var/www/html/?:ro\s*$#m',
      $this->compose(),
      'the working copy must be mounted :ro, or a container can write to it'
    );
  }

  /**
   * The strongest of these: a writable bind mount from inside the tree is
   * what the previous version of this file had, with ../sqlite-data holding
   * the database.
   */
  public function testNothingFromTheWorkingCopyIsMountedWritable(): void
  {
    $offenders = [];

    foreach (explode("\n", $this->compose()) as $line) {
      if (preg_match('#^\s*-\s*(\.\.[^:]*):#', $line, $m) !== 1) {
        continue;
      }
      if (!str_ends_with(rtrim($line), ':ro')) {
        $offenders[] = trim($line);
      }
    }

    self::assertSame([], $offenders, 'writable bind mount(s) from the working '
      . "copy. Put anything the sandbox writes in a named volume:\n  "
      . implode("\n  ", $offenders));
  }

  /**
   * With this set, do_config() takes the database settings from the
   * environment and never reads includes/settings.php -- see the branch on
   * getenv('WEBCALENDAR_USE_ENV') in includes/config.php. headless.php
   * honours the same flag and skips writing that file.
   */
  public function testTheDatabaseSettingsComeFromTheEnvironment(): void
  {
    self::assertMatchesRegularExpression(
      '/WEBCALENDAR_USE_ENV=true/',
      $this->compose(),
      'without this the container reads includes/settings.php, which on this '
      . 'checkout points at a live database'
    );
  }

  public function testTheDatabaseLivesOutsideTheMountedTree(): void
  {
    $compose = $this->compose();

    self::assertMatchesRegularExpression(
      '#WEBCALENDAR_DB_DATABASE=/var/www/data/#',
      $compose,
      'the database must sit outside /var/www/html, which is mounted from the '
      . 'working copy'
    );
    self::assertMatchesRegularExpression('/^volumes:$/m', $compose,
      'the data directory must be a named volume, so running the sandbox '
      . 'leaves nothing in the working copy');
  }

  /**
   * The named volume arrives root-owned and Apache runs as www-data, so the
   * installer stops at "Database directory is not writable" without this.
   */
  public function testTheDataDirectoryIsHandedToTheWebUser(): void
  {
    self::assertStringContainsString('chown www-data:www-data /var/www/data',
      $this->compose(),
      'a named volume is created root-owned; the web user cannot create the '
      . 'database in it until it is chowned');
  }

  /**
   * `make sandbox` printed "installed" whether or not the installer had
   * succeeded, because the echo followed it with a semicolon. A target that
   * reports intent rather than outcome is worse than a noisy one.
   */
  public function testTheInstallStepFailsLoudly(): void
  {
    $makefile = file_get_contents(self::MAKEFILE);
    self::assertIsString($makefile);

    self::assertStringContainsString('install failed, see the output above',
      $makefile, 'make sandbox must stop when the installer fails');
    self::assertStringContainsString('but db check cannot reach', $makefile,
      'and must confirm the install rather than trusting its exit code');
  }
}
