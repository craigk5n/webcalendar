<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * dbi_connect() reaches straight into the PHP extension for the configured
 * backend. When that extension is not loaded the call raises a fatal Error
 * rather than failing the connection, and the caller dies outright.
 *
 * That took down `bin/webcal.php diagnose`, whose entire purpose is to run
 * when the installation is broken -- and a database type whose extension is
 * missing is one of the ways it breaks. Reported as a failed connection, the
 * diagnostic report still prints, with ext.pgsql and its neighbours showing
 * why.
 */
final class DbiConnectExtensionGuardTest extends TestCase
{
  private function source(): string
  {
    $src = file_get_contents(__DIR__ . '/../includes/dbi4php.php');
    self::assertIsString($src);
    return $src;
  }

  /**
   * @return array<string, array{0: string,1: string}>
   */
  public static function backendProvider(): array
  {
    return [
      'postgresql' => ['postgresql', 'pg_connect'],
      'mysqli' => ['mysqli', 'mysqli_connect'],
      'oracle' => ['oracle', 'oci_connect'],
      'ibm_db2' => ['ibm_db2', 'db2_connect'],
      'odbc' => ['odbc', 'odbc_connect'],
      'ibase' => ['ibase', 'ibase_connect'],
    ];
  }

  /**
   * @dataProvider backendProvider
   */
  public function testEveryBackendIsProbedBeforeUse(string $type, string $function): void
  {
    $src = $this->source();

    $this->assertMatchesRegularExpression(
      "/'" . preg_quote($type, '/') . "'\s*=>\s*'" . preg_quote($function, '/') . "'/",
      $src,
      $type . ' must be probed for ' . $function . ' before the driver runs');
  }

  /**
   * SQLite3 is a class rather than a function, so function_exists would miss it.
   */
  public function testSqliteIsProbedAsAClass(): void
  {
    $this->assertStringContainsString("class_exists( 'SQLite3' )", $this->source());
  }

  /**
   * A guard placed after the driver branches would never run.
   */
  /**
   * The map and the message are data. What turns them into a guard is the
   * condition that reads them and returns before any driver is called, and
   * nothing here tested that: replacing the condition with `if( false )` left
   * the map intact, the message in place and every assertion in this file
   * true, while the probe did nothing at all. Found by mutating dbi4php.php
   * on 2026-09-25.
   */
  public function testTheProbeIsLive(): void
  {
    $src = $this->source();

    $this->assertMatchesRegularExpression(
      '/if\s*\(\s*\$driver\s*!==\s*\x27\x27\s*&&\s*!\s*'
      . 'function_exists\s*\(\s*\$driver\s*\)\s*\)/',
      $src,
      'the probe has to evaluate function_exists($driver), not merely list '
      . 'the driver functions in a map');

    $this->assertMatchesRegularExpression(
      '/if\s*\([^)]*db_type[^)]*sqlite3[^)]*!\s*class_exists\s*\('
      . '\s*\x27SQLite3\x27\s*\)\s*\)/',
      $src,
      'the sqlite3 probe has to evaluate class_exists(), not merely mention '
      . 'it');
  }

  /**
   * Measured from the condition, not from the message it prints. The message
   * stays where it is when the condition around it is disabled.
   */
  public function testTheGuardPrecedesTheDriverBranches(): void
  {
    $src = $this->source();

    $guardAt = strpos($src, 'function_exists( $driver )');
    $sqliteAt = strpos($src, "class_exists( 'SQLite3' )");
    $firstDriverAt = strpos($src, "'ibase' ) == 0 ) {");

    $this->assertNotFalse($guardAt, 'no live extension probe');
    $this->assertNotFalse($sqliteAt, 'no live sqlite3 probe');
    $this->assertNotFalse($firstDriverAt);

    $this->assertLessThan($firstDriverAt, $guardAt,
      'the extension probe must run before any driver call');
    $this->assertLessThan($firstDriverAt, $sqliteAt,
      'the sqlite3 probe must run before any driver call');

    // A probe that does not return leaves the driver to be called anyway.
    $between = substr($src, $guardAt, $firstDriverAt - $guardAt);
    $this->assertSame(2, substr_count($between, 'return false;'),
      'each probe must return before the driver branches are reached');
  }
}
