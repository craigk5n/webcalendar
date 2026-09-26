<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

/**
 * tools/send_reminders.php is a command-line script, but hosts that offer no
 * shell need some way to run it.  The web trigger covers that case: it is off
 * until an administrator generates a token in Admin > Settings > Email, and
 * the fetched URL has to carry that token.
 *
 * Only the SHA-256 hash of the token is stored, so a database read does not
 * yield anything usable.  These tests pin that behaviour, including the cases
 * where the trigger must stay shut.
 */
final class ReminderWebTriggerTest extends TestCase
{
  private const TOKEN = 'a3f1c9d87e6b5a4938271605f4e3d2c1'
    . 'b0a9988776655443322110ffeeddccbb';

  private function hashOf(string $token): string
  {
    return hash('sha256', $token);
  }

  public function testCorrectTokenIsAccepted(): void
  {
    $this->assertTrue(reminder_web_trigger_allowed(
      self::TOKEN, $this->hashOf(self::TOKEN)));
  }

  public function testWrongTokenIsRejected(): void
  {
    $this->assertFalse(reminder_web_trigger_allowed(
      'not-the-token', $this->hashOf(self::TOKEN)));
  }

  /**
   * An unconfigured setting means the administrator never switched the trigger
   * on, so no token may open it -- including the empty one.
   */
  public function testTriggerIsClosedWhenNoTokenIsConfigured(): void
  {
    $this->assertFalse(reminder_web_trigger_allowed(self::TOKEN, ''));
    $this->assertFalse(reminder_web_trigger_allowed('', ''));
  }

  public function testEmptyPresentedTokenIsRejected(): void
  {
    $this->assertFalse(reminder_web_trigger_allowed(
      '', $this->hashOf(self::TOKEN)));
  }

  /**
   * The raw token must never be what is stored; a database read would
   * otherwise hand over a working credential.
   */
  public function testRawTokenDoesNotMatchAStoredRawToken(): void
  {
    $this->assertFalse(reminder_web_trigger_allowed(
      self::TOKEN, self::TOKEN));
  }

  public function testTokenComesFromQueryParameter(): void
  {
    $_GET = ['token' => self::TOKEN];
    $_SERVER = [];
    $this->assertSame(self::TOKEN, reminder_web_trigger_presented_token());
  }

  public function testTokenComesFromHeader(): void
  {
    $_GET = [];
    $_SERVER = ['HTTP_X_REMINDER_TOKEN' => self::TOKEN];
    $this->assertSame(self::TOKEN, reminder_web_trigger_presented_token());
  }

  public function testHeaderWinsOverQueryParameter(): void
  {
    $_GET = ['token' => 'from-query'];
    $_SERVER = ['HTTP_X_REMINDER_TOKEN' => self::TOKEN];
    $this->assertSame(self::TOKEN, reminder_web_trigger_presented_token());
  }

  public function testNoTokenPresentedYieldsEmptyString(): void
  {
    $_GET = [];
    $_SERVER = [];
    $this->assertSame('', reminder_web_trigger_presented_token());
  }

  /**
   * A token check that runs after the script has already read reminders or
   * sent mail is decoration.  This pins the call order in the script itself.
   */
  public function testVerifierRunsBeforeAnyReminderWork(): void
  {
    $src = file_get_contents(__DIR__ . '/../tools/send_reminders.php');
    self::assertIsString($src);

    $verify = strpos($src, 'verify_web_trigger();');
    self::assertNotFalse($verify,
      'send_reminders.php no longer calls verify_web_trigger()');

    foreach (['user_get_users', 'WebCalMailer', 'send_reminder'] as $work) {
      $at = strpos($src, $work . ' (');
      if ($at === false) {
        $at = strpos($src, $work . '(');
      }
      if ($at !== false) {
        self::assertLessThan($at, $verify,
          "verify_web_trigger() must run before $work()");
      }
    }
  }

  protected function tearDown(): void
  {
    $_GET = [];
    $_SERVER = [];
  }
}
