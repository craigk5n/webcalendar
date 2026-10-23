<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/dbi4php.php';

/**
 * Regression test for a security-audit needs-validation lead (run-1).
 *
 * dbi_execute() does not use native prepared statements: it interpolates each
 * bound parameter into the SQL as '<dbi_escape_string($param)>'. That makes
 * dbi_escape_string() the application's SQL-injection boundary, so its
 * neutralization must be correct for every backend it claims to support.
 *
 * The ibm_db2 and odbc branches (and the default fallback) escaped with
 * addslashes(), which is MySQL-style: it turns ' into \'. DB2 and standard
 * ODBC/ANSI-SQL engines do not treat backslash as an escape character, so \'
 * is a backslash followed by a live, string-terminating quote -- the escaping
 * does not neutralize the quote at all, and a crafted value breaks out of the
 * literal (classic SQL injection). ANSI SQL neutralizes a quote by doubling
 * it ('' ), which is what the ibase/oracle branches already do.
 *
 * @covers ::dbi_escape_string
 */
final class DbiEscapeStringTest extends TestCase
{
  private string $savedDbType;

  protected function setUp(): void
  {
    $this->savedDbType = (string) ( $GLOBALS['db_type'] ?? '' );
  }

  protected function tearDown(): void
  {
    $GLOBALS['db_type'] = $this->savedDbType;
  }

  /**
   * @dataProvider ansiQuoteDoublingBackends
   */
  public function testQuoteIsDoubledNotBackslashed(string $dbType): void
  {
    $GLOBALS['db_type'] = $dbType;

    // A single quote must be doubled, never backslash-escaped.
    self::assertSame("O''Brien", dbi_escape_string("O'Brien"),
      "$dbType must double a single quote (ANSI SQL), not backslash it.");

    // A naive injection payload is fully neutralized: every quote is doubled,
    // so the value stays inside its string literal.
    self::assertSame(
      "'' OR ''1''=''1",
      dbi_escape_string("' OR '1'='1"),
      "$dbType must neutralize an injection payload by doubling quotes."
    );
  }

  /**
   * @dataProvider ansiQuoteDoublingBackends
   */
  public function testNoLiveTerminatingQuoteRemains(string $dbType): void
  {
    $GLOBALS['db_type'] = $dbType;
    $escaped = dbi_escape_string("x'; DROP TABLE webcal_entry; --");

    // The MySQL-style backslash-quote (which leaves a live quote on these
    // engines) must not appear in the escaped output.
    self::assertStringNotContainsString("\\'", $escaped,
      "$dbType must not produce backslash-escaped quotes.");
    // Every quote in the output is part of a doubled pair.
    self::assertSame(0, substr_count($escaped, "'") % 2,
      "$dbType must leave quotes only in doubled pairs.");
  }

  public static function ansiQuoteDoublingBackends(): array
  {
    return [
      'ibm_db2' => ['ibm_db2'],
      'odbc'    => ['odbc'],
      // Already correct before this change -- guard against regressing them.
      'ibase'   => ['ibase'],
      'oracle'  => ['oracle'],
    ];
  }
}
