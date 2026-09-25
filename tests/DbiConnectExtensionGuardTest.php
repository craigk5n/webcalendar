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
  public function testTheGuardPrecedesTheDriverBranches(): void
  {
    $src = $this->source();

    $guardAt = strpos($src, 'is not loaded (');
    $firstDriverAt = strpos($src, "'ibase' ) == 0 ) {");
    $this->assertNotFalse($guardAt);
    $this->assertNotFalse($firstDriverAt);
    $this->assertLessThan($firstDriverAt, $guardAt,
      'the extension probe must run before any driver call');
  }
}
