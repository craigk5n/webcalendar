<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * `bin/webcal.php db dump` hands the work to mysqldump, pg_dump or sqlite3
 * rather than writing SQL itself. Getting quoting, ordering and constraints
 * right on three backends is how a project ends up with backups that do not
 * restore, and those tools already do it.
 *
 * The behaviour needs real databases and is verified against containers -- a
 * MySQL and a SQLite dump were each restored into a scratch database. These
 * pin the properties that are easy to lose in an edit.
 */
final class DbDumpTest extends TestCase
{
  private function source(): string
  {
    $src = file_get_contents(__DIR__ . '/../bin/webcal.php');
    self::assertIsString($src);
    return $src;
  }

  /**
   * A password in the argument list is visible in ps output to every user on
   * the machine, which is the whole reason the reset-password command reads
   * standard input.
   */
  public function testCredentialsNeverReachTheArgumentList(): void
  {
    $src = $this->source();

    $this->assertStringNotContainsString("'-p' . \$password", $src);
    $this->assertStringNotContainsString('--password=', $src,
      'mysqldump --password= would expose the secret in ps output');
    $this->assertStringContainsString('PGPASSWORD', $src,
      'PostgreSQL credentials belong in the environment');
    $this->assertStringContainsString('defaults-extra-file', $src,
      'MySQL credentials belong in a defaults file, not the command line');
  }

  /**
   * That file holds the database password in clear text for the life of the
   * dump, so it must not be readable by anyone else.
   */
  public function testTheTemporaryCredentialsFileIsPrivateAndRemoved(): void
  {
    $src = $this->source();

    $this->assertMatchesRegularExpression('/chmod\(\$file, 0600\)/', $src);
    $this->assertStringContainsString('@unlink($file)', $src,
      'the credentials file must be removed once the dump finishes');
  }

  /**
   * A dump is every event, every user and every password hash.
   */
  public function testAnOutputFileIsCreatedPrivate(): void
  {
    $this->assertMatchesRegularExpression('/chmod\(\$output, 0600\)/',
      $this->source());
  }

  /**
   * The configured database may hold other applications' tables, which are
   * not WebCalendar's to copy.
   */
  public function testOnlyWebcalTablesAreIncluded(): void
  {
    $src = $this->source();

    $this->assertStringContainsString("str_starts_with(\$name, 'webcal_')", $src,
      'the table list must be filtered to the webcal_ prefix');
  }

  /**
   * Writing the SQL here instead would be a second, worse implementation of
   * three well-tested tools.
   */
  public function testTheDumpIsDelegatedToTheDatabaseTools(): void
  {
    $src = $this->source();

    foreach (['mysqldump', 'pg_dump', 'sqlite3'] as $tool) {
      $this->assertStringContainsString("'" . $tool . "'", $src,
        $tool . ' must be used rather than a hand-written dumper');
    }
    $this->assertStringNotContainsString('INSERT INTO webcal_', $src,
      'this command must not generate SQL itself');
  }
}
