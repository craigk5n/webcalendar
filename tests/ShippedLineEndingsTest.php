<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * No shipped text file may use CRLF line endings.
 *
 * `.editorconfig` has said `end_of_line = lf` for years, and every text file in
 * the release obeyed it except `includes/zone.tab`, which was committed with
 * CRLF on all 407 lines.
 *
 * Nothing read it wrongly -- the parser does `trim()` then
 * `preg_split('/[\s,]+/')`, so CRLF and LF yield the same 383 timezones. The
 * cost was to the signed manifest. MANIFEST.sha256 records the bytes that ship,
 * so any deployment pipeline that normalises line endings -- an extraction in
 * text mode, a packaging step, an editor -- turns that one file into a
 * permanent "modified file" warning on the Security Audit page, on an
 * installation where nothing was modified. Reported as #788 against v1.9.24,
 * where the reporter's hash was exactly ours with the CRs stripped, and the
 * 407-byte size difference (17927 against 17520) gave it away.
 *
 * A file is treated as binary when it contains a NUL byte, which is the same
 * heuristic git uses. The five `\r`-bearing files in the release -- a favicon,
 * three GIFs and a TrueType font -- all contain NUL and so are skipped; no text
 * file in the tree does.
 */
final class ShippedLineEndingsTest extends TestCase
{
  private const ROOT = __DIR__ . '/..';

  /**
   * The shipped set, from the release manifest rather than from a glob: a file
   * that does not ship cannot reach a user's Security Audit page.
   *
   * @return list<string>
   */
  private function shippedFiles(): array
  {
    $list = file_get_contents(self::ROOT . '/release-files');
    self::assertIsString($list, 'release-files must be readable');

    $files = [];
    foreach (explode("\n", $list) as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) {
        continue;
      }
      $files[] = $line;
    }

    self::assertNotEmpty($files, 'release-files listed nothing');

    return $files;
  }

  public function testNoShippedTextFileUsesCrlf(): void
  {
    $offenders = [];

    foreach ($this->shippedFiles() as $rel) {
      $path = self::ROOT . '/' . $rel;
      if (!is_file($path)) {
        // ReleaseFilesConsistencyTest owns missing entries; not this test's
        // business, and failing here too would just double the noise.
        continue;
      }

      $bytes = (string) file_get_contents($path);
      if (str_contains($bytes, "\0")) {
        continue;
      }

      $crlf = substr_count($bytes, "\r\n");
      if ($crlf > 0) {
        $offenders[] = "$rel ($crlf CRLF line(s))";
      }
    }

    self::assertSame([], $offenders, count($offenders) . ' shipped text file(s) '
      . "use CRLF. MANIFEST.sha256 records the bytes that ship, so any pipeline "
      . "that normalises line endings turns these into permanent \"modified "
      . "file\" warnings on the Security Audit page (#788). Convert to LF:\n  "
      . implode("\n  ", $offenders));
  }

  /**
   * The file that prompted this, pinned by name.
   *
   * The general test above would catch a regression here, but only while
   * zone.tab still ships. This says outright that it must be LF, so removing it
   * from release-files cannot quietly retire the check.
   */
  public function testZoneTabIsLf(): void
  {
    $path = self::ROOT . '/includes/zone.tab';
    self::assertFileExists($path, 'includes/zone.tab is read by '
      . 'display_tz_selection() and ships in the release');

    $bytes = (string) file_get_contents($path);

    self::assertSame(0, substr_count($bytes, "\r\n"),
      'includes/zone.tab was committed with CRLF on every line, which made it '
      . 'the only shipped text file whose hash changed under line-ending '
      . 'normalisation (#788)');
    self::assertSame(0, substr_count($bytes, "\r"),
      'no bare CR either');
  }

  /**
   * And the timezone list has to survive whatever the line endings are, since
   * that is the reason converting the file was safe.
   */
  public function testTheTimezoneListParsesFromBothForms(): void
  {
    $lf = (string) file_get_contents(self::ROOT . '/includes/zone.tab');
    $crlf = str_replace("\n", "\r\n", $lf);

    self::assertNotSame($lf, $crlf, 'the two forms must actually differ');
    self::assertSame($this->parseZones($lf), $this->parseZones($crlf),
      'display_tz_selection() splits on \s, so both forms yield the same list; '
      . 'if that ever stops being true, converting the file is no longer safe');
    self::assertGreaterThan(300, count($this->parseZones($lf)),
      'zone.tab should yield several hundred timezones');
  }

  /**
   * The parse in display_tz_selection() (includes/functions.php), applied to a
   * string rather than a file handle.
   *
   * @return list<string>
   */
  private function parseZones(string $contents): array
  {
    $zones = [];

    foreach (explode("\n", $contents) as $line) {
      $line .= "\n";
      if (substr(trim($line), 0, 1) === '#' || strlen($line) <= 2) {
        continue;
      }
      $hash = strrchr($line, '#');
      $line = trim($line, $hash === false ? " \t\n\r\0\x0B" : $hash);
      $parts = preg_split('/[\s,]+/', trim($line));
      if (isset($parts[2])) {
        $zones[] = $parts[2];
      }
    }

    sort($zones);

    return $zones;
  }
}
