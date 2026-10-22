<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for AUTH-4: failed-login logging and brute-force throttling.
 *
 * Before these fixes:
 *   - $showLoginFailureReason used '=' (assignment) instead of '==', so failure
 *     reasons (user enumeration) leaked whenever mode was non-empty.
 *   - The failed-login activity_log() call was unreachable in production
 *     because an `echo "ERROR"; exit;` ran before it — no audit trail.
 *   - There was no throttle on repeated failed logins.
 *
 * Source-structure tests: login.php is a procedural page that exits.
 */
final class LoginThrottleTest extends TestCase
{
  private string $login;
  private string $functions;

  protected function setUp(): void
  {
    $l = file_get_contents(__DIR__ . '/../login.php');
    $f = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertNotFalse($l, 'login.php must exist');
    self::assertNotFalse($f, 'includes/functions.php must exist');
    $this->login = $l;
    $this->functions = $f;
  }

  public function testFailureReasonUsesComparisonNotAssignment(): void
  {
    self::assertStringNotContainsString(
      "\$settings['mode'] = 'dev')",
      $this->login,
      "The showLoginFailureReason line must use '==' (comparison), not '=' "
      . '(assignment), to avoid forcing failure-reason disclosure.'
    );
    self::assertMatchesRegularExpression(
      "/\\\$settings\['mode'\]\s*==\s*'dev'/",
      $this->login,
      'showLoginFailureReason must compare mode with ==.'
    );
  }

  public function testThrottleHelperExists(): void
  {
    self::assertMatchesRegularExpression(
      '/function\s+login_recent_failure_count\s*\(/',
      $this->functions,
      'login_recent_failure_count() helper must exist in functions.php.'
    );
    // It must count only LOG_LOGIN_FAILURE rows for the given login.
    self::assertMatchesRegularExpression(
      '/cal_type\s*=\s*\?\s*AND\s*cal_user_cal\s*=\s*\?/',
      $this->functions,
      'The throttle query must filter by cal_type and cal_user_cal.'
    );
  }

  public function testLoginEnforcesThrottle(): void
  {
    self::assertMatchesRegularExpression(
      '/login_recent_failure_count\s*\(\s*\$logLogin\s*,\s*\$loginFailWindow\s*\)\s*>=\s*\$loginMaxFailures/',
      $this->login,
      'login.php must block once recent failures reach the threshold.'
    );
  }

  public function testFailureIsLoggedBeforeExit(): void
  {
    // The activity_log(... LOG_LOGIN_FAILURE ...) call must appear BEFORE the
    // `echo "ERROR: $error"; exit;` in the invalid-login branch, otherwise the
    // failure is never recorded (and the throttle has nothing to count).
    $logPos = strpos($this->login, 'LOG_LOGIN_FAILURE');
    self::assertNotFalse($logPos, 'A LOG_LOGIN_FAILURE activity_log call must exist.');

    // Find the invalid-login branch's exit that follows the log call.
    $tail = substr($this->login, $logPos);
    self::assertMatchesRegularExpression(
      '/LOG_LOGIN_FAILURE.*?echo\s+"ERROR:/s',
      $this->login,
      'The failure must be logged before the ERROR/exit response.'
    );
  }
}
