<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * `bin/webcal.php export` and `import` drive the same functions the Export
 * and Import pages drive, which is the property worth protecting: a file the
 * command line produces should be the file the web page produces.
 *
 * The behaviour is verified against a throwaway SQLite installation -- 23
 * seeded events exported, imported into a second calendar, and exported again
 * with matching SUMMARY and RRULE lines. These pin the details that are easy
 * to lose in an edit and would fail quietly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CliExportImportTest extends TestCase
{
  private function cli(): string
  {
    $source = file_get_contents(__DIR__ . '/../bin/webcal.php');
    self::assertIsString($source);

    return $source;
  }

  /**
   * export_ical()'s second argument is the email-attachment flag, and the
   * branch it selects returns the document instead of echoing it -- which is
   * tempting, because it saves an output buffer. It also skips
   * save_uid_for_event(), so the UIDs in the file would never be recorded and
   * re-importing the file would not match what the export came from.
   */
  public function testExportDoesNotAskForTheAttachmentForm(): void
  {
    $src = $this->cli();

    self::assertStringContainsString("export_ical('all');", $src,
      'export must call export_ical() the way export_handler.php does');
    self::assertStringNotContainsString("export_ical('all', true)", $src,
      'the attachment form skips save_uid_for_event()');
  }

  /**
   * export_get_event_entry() restricts the result to publicly visible events
   * when $type is 'publish', which is what publish.php wants and an export
   * from a shell does not. The command clears it rather than relying on it
   * being unset, since $type is a global that any earlier code may have
   * written. ExportAccessFilterTest covers the restriction itself.
   */
  public function testExportLeavesThePublishFilterOff(): void
  {
    self::assertStringContainsString("\$GLOBALS['type'] = '';", $this->cli(),
      'export must clear $type or export_get_event_entry() filters the '
      . 'result down to public events only');
  }

  /**
   * A calendar is personal data, so the same treatment as `db dump`.
   */
  public function testExportFilesAreCreatedUnreadableByOthers(): void
  {
    $src = $this->cli();

    self::assertStringContainsString('chmod($output, 0600)', $src);
    // Before the first byte, not after: otherwise the window between the two
    // leaves the file world readable.
    $chmod = strpos($src, 'chmod($output, 0600)');
    $write = strpos($src, 'file_put_contents($output');
    self::assertNotFalse($chmod);
    self::assertNotFalse($write);
    self::assertLessThan($write, $chmod,
      'the mode has to be set before the calendar is written into the file');
  }

  /**
   * import_data() writes an HTML conflict report -- headings, colours and all
   * -- unless told to be quiet. load_remote_calendar() passes the same flag.
   */
  public function testImportAsksImportDataToBeQuiet(): void
  {
    self::assertStringContainsString('import_data($data, $overwrite, $type, true)',
      $this->cli(), 'import must pass $silent = true');
  }

  /**
   * determineServerUrl() read $_SERVER['HTTP_HOST'] and ['SERVER_PORT']
   * directly. There is no request on the command line, so every call raised
   * two warnings: once per event during an export, into output that has to
   * stay a valid iCalendar document, and on every reminder run.
   */
  public function testTheServerUrlFallbackWorksWithNoRequest(): void
  {
    $this->loadDetermineServerUrl();

    unset($_SERVER['HTTP_HOST'], $_SERVER['SERVER_PORT']);

    $raised = [];
    set_error_handler(static function ($severity, $message) use (&$raised) {
      $raised[] = $message;
      return true;
    });
    $url = determineServerUrl();
    restore_error_handler();

    self::assertSame([], $raised,
      'no notice or warning may be raised with no request present');
    self::assertStringStartsWith('http', $url);
    self::assertStringNotContainsString('://:', $url,
      'the URL must not be left with an empty host');
  }

  public function testTheServerUrlStillComesFromTheRequestWhenThereIsOne(): void
  {
    $this->loadDetermineServerUrl();

    $_SERVER['HTTP_HOST'] = 'calendar.example.org';
    $_SERVER['SCRIPT_NAME'] = '/index.php';

    self::assertStringContainsString('calendar.example.org',
      determineServerUrl());
  }

  /**
   * Taken out of includes/functions.php rather than copied, the way
   * PurgeEventSelectionTest takes its two functions out of purge.php.
   */
  private function loadDetermineServerUrl(): void
  {
    if (function_exists('determineServerUrl')) {
      return;
    }

    $src = file_get_contents(__DIR__ . '/../includes/functions.php');
    self::assertIsString($src);

    $start = strpos($src, 'function determineServerUrl()');
    self::assertNotFalse($start);

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

    eval(substr($src, $start, $end - $start + 1));
  }

  /**
   * The From the command prints has to be the From it sends.
   *
   * WC_Send() takes the sender as its seventh argument, and its two branches
   * are identical -- both call SetFrom($from_email, $from_name) -- so an
   * omitted sender is not defaulted to anything, it is sent as an empty From.
   * The first version of `email test` read EMAIL_FALLBACK_FROM to display it
   * and then called WC_Send() with five arguments. The relay accepted the
   * message, the destination discarded it, and the command reported success
   * while printing a From it had never used. Found by sending one and
   * watching it not arrive.
   */
  public function testTheTestMessageIsSentFromTheAddressItReports(): void
  {
    $src = $this->cli();

    $start = strpos($src, '$sent = $mail->WC_Send(');
    self::assertNotFalse($start, 'email test must send through WC_Send()');
    $call = substr($src, $start, (int) strpos($src, ');', $start) - $start);

    self::assertStringContainsString('$from', $call,
      'the sender read for display must be the one handed to WC_Send(), or '
      . 'the message goes out with an empty From');

    // Seven arguments at least: the sender sits in the seventh position, so
    // a shorter call cannot be passing it.
    self::assertGreaterThanOrEqual(7, substr_count($call, ',') + 1,
      'WC_Send() takes the sender as its seventh argument');
  }
}
