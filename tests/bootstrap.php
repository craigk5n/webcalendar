<?php

declare(strict_types=1);

/**
 * Serialises test runs on this machine.
 *
 * Parts of the suite use fixed, machine-wide resources: McpIntegrationTest
 * installs into sys_get_temp_dir() . '/mcp_test.sqlite' and serves it from
 * `php -S localhost:8099`, and the other MCP tests use 8100-8104. Two runs at
 * once therefore delete each other's database and fight over the ports, which
 * surfaces as a scatter of unrelated errors rather than anything that points
 * at the cause -- 25 of them, in the incident that prompted this.
 *
 * The lock is global rather than per-checkout on purpose: those resources are
 * global, so two different clones collide just as surely as two runs in one.
 *
 * It fails fast rather than waiting. A developer who has just started a second
 * run wants to be told, not to watch an apparently hung terminal. The lock is
 * held by an open file handle, so the operating system releases it when the
 * process ends, including when it crashes -- there is no stale lock to clear.
 *
 * Tests annotated @runTestsInSeparateProcesses matter here. PHPUnit spawns a
 * child PHP process for each one, and the child re-runs this file. It must not
 * try to take a lock its own parent already holds, so the parent records the
 * fact in the environment, which the child inherits.
 *
 * Set WEBCAL_TEST_ALLOW_PARALLEL=1 to skip this, if you have isolated the
 * fixtures and know what you are doing.
 */

// Shared test fixtures. Loaded here rather than from each test file, which
// drifted: the require reached only the classes whose existing requires
// happened to use the same quoting style.
require_once __DIR__ . '/McpServerFixture.php';

(static function (): void {
  if (getenv('WEBCAL_TEST_ALLOW_PARALLEL') === '1') {
    return;
  }

  // A PHPUnit separate-process child, running under a parent that already
  // holds the lock.
  if (getenv('WEBCAL_TEST_LOCK_HELD') === '1') {
    return;
  }

  $lockFile = sys_get_temp_dir() . '/webcalendar-phpunit.lock';
  $handle = @fopen($lockFile, 'c');

  if ($handle === false) {
    // A read-only temp directory is not a reason to refuse to run tests.
    fwrite(STDERR, "Note: could not open $lockFile; running without the "
      . "concurrency lock.\n");
    return;
  }

  if (!flock($handle, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, <<<TXT
      Another WebCalendar test run is already in progress on this machine.

      The suite cannot run concurrently with itself: McpIntegrationTest uses a
      fixed SQLite file in the temp directory and serves it on port 8099, and
      the other MCP tests use 8100-8104. Two runs corrupt each other and report
      errors that have nothing to do with the code.

      Wait for the other run to finish, or set WEBCAL_TEST_ALLOW_PARALLEL=1 if
      you have isolated the fixtures.

      TXT);
    exit(1);
  }

  // Held for the lifetime of the process. Without a reference the handle would
  // be collected, the lock released, and the guard would do nothing.
  $GLOBALS['__webcalendar_test_lock'] = $handle;

  // Inherited by separate-process children so they do not block on us.
  putenv('WEBCAL_TEST_LOCK_HELD=1');
})();
