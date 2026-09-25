<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Scripts under tools/ are command-line utilities, but they live inside the
 * web root and two of them ship in release-files: send_reminders.php and
 * reload_remotes.php.  A third, convert_passwords.php, shipped until it was
 * deleted: it rewrote every stored hash to md5 for a 0.9.43 upgrade and was
 * reachable by URL.
 *
 * .htaccess cannot be relied on to block them.  Debian and Ubuntu ship
 * AllowOverride None for /var/www, which makes the whole file a no-op, and
 * the security audit already warns administrators about these scripts rather
 * than assuming they are unreachable.
 *
 * Each script therefore checks the SAPI itself, before anything else runs.
 *
 * send_reminders.php is the one script with a sanctioned web path: hosts with
 * no shell can run it by URL once an administrator generates a token, so its
 * guard records the request and defers the decision to verify_web_trigger()
 * rather than exiting outright.  ReminderWebTriggerTest covers that check and
 * pins it ahead of any reminder work.
 *
 * This is a source-structure regression test: it fails the build when a new
 * script is added without the guard, or when an existing guard is removed or
 * moved below code that would already have run.
 */
final class CliOnlyGuardTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * @return array<int, string>
   */
  private function cliScripts(): array
  {
    // bin/ holds extensionless executables (bin/webcal), so glob on
    // everything there and keep what is actually PHP.
    $bin = array_filter(glob(self::ROOT . '/bin/*') ?: [], static function ($f) {
      return is_file($f)
        && str_starts_with((string) file_get_contents($f), '<?php');
    });

    return array_merge(glob(self::ROOT . '/tools/*.php') ?: [], $bin);
  }

  /**
   * A PHP file without a .php extension is served as a static download by
   * Apache, so the SAPI guard inside it never runs and the source is handed
   * out verbatim. bin/webcal was extensionless at first and returned 200 with
   * its own body; only the rename to bin/webcal.php made the guard fire.
   */
  public function testCliScriptsAreNamedSoThePhpEngineRunsThem(): void
  {
    $offenders = [];

    foreach ($this->cliScripts() as $file) {
      if (!str_ends_with($file, '.php')) {
        $offenders[] = basename($file);
      }
    }

    $this->assertSame([], $offenders, 'CLI scripts the web server would serve '
      . 'as source rather than execute: ' . implode(', ', $offenders));
  }

  public function testEveryCliScriptRefusesNonCliSapi(): void
  {
    $offenders = [];

    foreach ($this->cliScripts() as $file) {
      $src = file_get_contents($file);
      if ($src === false) {
        $offenders[] = basename($file) . ' (unreadable)';
        continue;
      }
      if (!preg_match('/\bPHP_SAPI\b|\bphp_sapi_name\s*\(/', $src)) {
        $offenders[] = basename($file);
      }
    }

    $this->assertSame([], $offenders, 'CLI scripts with no SAPI guard: '
      . implode(', ', $offenders));
  }

  /**
   * A guard placed after a require, a database connect or any output is
   * decoration.  Nothing that can execute or emit may precede it; a
   * declare() and the leading comments are the only things allowed.
   */
  public function testGuardRunsBeforeAnythingElse(): void
  {
    $offenders = [];

    foreach ($this->cliScripts() as $file) {
      $src = file_get_contents($file);
      if ($src === false) {
        continue;
      }

      $pos = strpos($src, 'PHP_SAPI');
      if ($pos === false) {
        continue; // already reported by the previous test
      }

      $before = $this->stripComments(substr($src, 0, $pos));

      if (preg_match('/\b(require|require_once|include|include_once|echo|print|'
        . 'define|ini_set|dbi_connect|session_start|header)\b/i', $before)) {
        $offenders[] = basename($file);
      }
    }

    $this->assertSame([], $offenders, 'Guard runs too late in: '
      . implode(', ', $offenders));
  }

  /**
   * Comments mentioning require() would otherwise read as executable code.
   */
  private function stripComments(string $php): string
  {
    $out = '';
    foreach (token_get_all($php) as $token) {
      if (is_array($token)) {
        if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
          continue;
        }
        $out .= $token[1];
        continue;
      }
      $out .= $token;
    }

    return $out;
  }
}
