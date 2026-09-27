<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * `bin/webcal.php user reset-password` is the answer to the commonest support
 * request there was no answer to: an administrator locked out of their own
 * calendar. Passwords are bcrypt, so there is no hand-written UPDATE that
 * recovers the account.
 *
 * These are source-structure tests. The behaviour needs a database, a web
 * server and a login round trip, which is covered by hand against a container
 * rather than here; what these pin are the properties that are easy to lose
 * in an edit and expensive to notice.
 */
final class UserResetPasswordTest extends TestCase
{
  private const CLI = __DIR__ . '/../bin/webcal.php';

  private function source(): string
  {
    $src = file_get_contents(self::CLI);
    self::assertIsString($src);
    return $src;
  }

  /**
   * A password passed as an argument is visible in ps output and lands in
   * shell history, so the command generates one or reads standard input.
   */

  /**
   * The body of one function in bin/webcal.php, comments removed.
   *
   * File-wide assertions stopped meaning what they said once the command line
   * grew several commands that share an idiom: a check for the idiom passed
   * because another command still used it.
   */
  private function functionBody(string $name): string
  {
    $body = SourceText::phpFunctionBody($this->source(), $name);
    self::assertIsString($body, "bin/webcal.php must define $name()");

    return $body;
  }

  public function testThePasswordIsNeverACommandLineArgument(): void
  {
    $src = $this->source();

    $this->assertStringNotContainsString("'--password='", $src,
      'a --password option would expose the secret in ps and shell history');
    $this->assertStringContainsString('--stdin', $src);
    $this->assertStringContainsString('random_int', $src,
      'the generated password must come from a cryptographic source');

    // The literal alone is not enough. An option read through the generic
    // wc_opt() helper never spells '--password=' anywhere, so the check above
    // passed with one added. What matters is the mechanism: the only two
    // sources are the generator and standard input, and the only thing the
    // argument list may be consulted for is the --stdin flag.
    $body = $this->functionBody('wc_read_or_generate_password');

    $this->assertStringContainsString('STDIN', $body,
      'a supplied password must arrive on standard input');

    $withoutStdinFlag = str_replace("in_array('--stdin', \$argv, true)", '',
      $body);
    $this->assertStringNotContainsString('$argv', $withoutStdinFlag,
      'nothing but the --stdin flag may be read from the argument list: a '
      . 'password there is visible in ps output and shell history');
  }

  /**
   * Hashing belongs to includes/user.php. A second implementation here would
   * drift from the one login uses, which is how MCP ended up with three copies
   * of the event write path.
   */
  public function testHashingIsDelegatedRatherThanReimplemented(): void
  {
    $src = $this->source();

    $this->assertStringContainsString('user_update_user_password', $src);
    $this->assertStringNotContainsString('password_hash', $src,
      'hashing must stay in includes/user.php, not be repeated here');
  }

  /**
   * With LDAP, IMAP, NIS or Joomla the password lives in that system.
   * Writing webcal_user.cal_passwd would report success while the account
   * stayed locked out, which is worse than refusing.
   */
  /**
   * Passwords for LDAP, IMAP, NIS and Joomla live elsewhere, so writing
   * webcal_user.cal_passwd would report success while the user stayed locked
   * out.
   *
   * This was checked in the dispatch, before the action was looked at, which
   * also made `user list` unavailable on exactly those installations. It now
   * belongs to reset-password, so the property to hold is that it runs before
   * the write rather than before the command -- a stronger statement, since it
   * names what the refusal is protecting.
   */
  public function testAlternativeAuthenticationBackendsAreRefused(): void
  {
    $body = (string) SourceText::phpFunctionBody($this->source(), 'wc_cmd_user');

    $this->assertMatchesRegularExpression("/!==\s*'user\.php'/", $body,
      'reset-password must compare against the configured backend');

    $refusalAt = strpos($body, 'authenticates through');
    $writeAt = strpos($body, 'user_update_user_password');

    $this->assertNotFalse($refusalAt, 'the refusal must still be there');
    $this->assertNotFalse($writeAt, 'reset-password must still write a hash');
    $this->assertLessThan($writeAt, $refusalAt,
      'the refusal has to run before the password is written');
  }

  /**
   * An administrator finds out this happened by reading the activity log.
   */
  public function testTheResetIsRecordedInTheActivityLog(): void
  {
    $this->assertStringContainsString('activity_log(', $this->source());
    $this->assertStringContainsString('Password reset from the command line',
      $this->source());
  }

  /**
   * user_update_user_password() is a bare UPDATE and reports success when it
   * matches no rows, so a typo in the login would otherwise look like it had
   * worked.
   */
  public function testTheUserIsCheckedToExistFirst(): void
  {
    $src = $this->source();

    $lookupAt = strpos($src, 'user_load_variables');
    $updateAt = strpos($src, 'user_update_user_password');
    $this->assertNotFalse($lookupAt);
    $this->assertNotFalse($updateAt);
    $this->assertLessThan($updateAt, $lookupAt,
      'existence must be established before the update, which matches zero '
      . 'rows silently');
  }

  /**
   * `user list` reads webcal_user. A command that only reports is safe to run
   * on a production installation, and that is most of its value.
   */
  public function testListingChangesNothing(): void
  {
    $body = (string) SourceText::phpFunctionBody($this->source(), 'wc_user_list');

    foreach (['INSERT', 'UPDATE ', 'DELETE', 'ALTER', 'DROP'] as $verb) {
      self::assertStringNotContainsStringIgnoringCase($verb, $body,
        "user list must not issue $verb");
    }
    self::assertStringContainsString('SELECT cal_login', $body);
  }

  /**
   * Listing has to work whatever checks passwords.
   *
   * The refusal for LDAP, IMAP, NIS and Joomla used to sit in the dispatch,
   * before the action was even looked at, so `user list` was unavailable on
   * those installations -- the ones where an administrator most needs to see
   * which accounts exist and cannot simply reset a password to get in. It now
   * belongs to reset-password, which is the only part that writes a password
   * column.
   */
  public function testListingIsNotRefusedOnOtherAuthenticationBackends(): void
  {
    $body = (string) SourceText::phpFunctionBody($this->source(), 'wc_cmd_user');

    $list = strpos($body, "=== 'list'");
    $refusal = strpos($body, 'passwords are not stored in WebCalendar');

    self::assertNotFalse($list, 'wc_cmd_user() must handle the list action');
    self::assertNotFalse($refusal, 'reset-password must still refuse');
    self::assertLessThan($refusal, $list,
      'the list action has to return before the backend refusal, or listing '
      . 'is unavailable on exactly the installations that need it most');
  }

  /**
   * And the refusal must not have been left in the dispatch as well, where it
   * would apply to every action again.
   */
  public function testTheDispatchDoesNotRefuseOnTheBackend(): void
  {
    $code = SourceText::php($this->source());

    $start = strpos($code, "case 'user':");
    self::assertNotFalse($start);
    $end = strpos($code, "case 'help':", $start);
    self::assertNotFalse($end);

    self::assertStringNotContainsString('passwords are not stored',
      substr($code, $start, $end - $start),
      'the backend refusal belongs to reset-password, not to the dispatch');
  }
}
