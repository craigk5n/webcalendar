<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

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
    $src = $this->source();

    $start = strpos($src, 'function ' . $name . '(');
    self::assertNotFalse($start, "bin/webcal.php must define $name()");

    $open = strpos($src, '{', $start);
    $depth = 0;
    $end = $open;
    for ($i = $open; $i < strlen($src); $i++) {
      if ($src[$i] === '{') {
        $depth++;
      }
      if ($src[$i] === '}') {
        $depth--;
        if ($depth === 0) {
          $end = $i;
          break;
        }
      }
    }

    $body = substr($src, $open, $end - $open + 1);
    $out = '';
    foreach (token_get_all('<?php ' . $body) as $token) {
      if (is_array($token)) {
        if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT
          || $token[0] === T_OPEN_TAG) {
          continue;
        }
        $out .= $token[1];
        continue;
      }
      $out .= $token;
    }

    return $out;
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
  public function testAlternativeAuthenticationBackendsAreRefused(): void
  {
    $src = $this->source();

    $this->assertMatchesRegularExpression(
      "/\\\$wcUserInc\s*!==\s*'user\.php'/", $src,
      'the command must refuse when another backend owns the password');

    // Textual order is not execution order here: wc_cmd_user() is defined
    // above the dispatch that calls it. The property that matters is that
    // within the dispatch, the refusal runs before the command is invoked.
    $caseAt = strpos($src, "case 'user':");
    $this->assertNotFalse($caseAt);
    $dispatch = substr($src, $caseAt);

    $refusalAt = strpos($dispatch, 'authenticates through');
    $invokeAt = strpos($dispatch, 'wc_cmd_user($argv)');
    $this->assertNotFalse($refusalAt, 'the dispatch must carry the refusal');
    $this->assertNotFalse($invokeAt);
    $this->assertLessThan($invokeAt, $refusalAt,
      'the refusal has to run before the command that writes to the database');
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
}
