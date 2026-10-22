<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wizard/WizardDatabase.php';

final class WizardAdminCountSqlite extends SQLite3
{
  public ?string $countFailure = null;
  public int $insertAttempts = 0;

  public function query(string $query): SQLite3Result|false
  {
    if ($query === "SELECT COUNT(*) FROM webcal_user WHERE cal_is_admin = 'Y'") {
      if ($this->countFailure === 'exception') {
        throw new RuntimeException('SELECT permission denied');
      }
      if ($this->countFailure === 'false') {
        return false;
      }
      if ($this->countFailure === 'empty-result') {
        return parent::query('SELECT cal_login FROM webcal_user WHERE 1 = 0');
      }
    }
    return parent::query($query);
  }

  public function prepare(string $query): SQLite3Stmt|false
  {
    if (str_starts_with($query, 'INSERT INTO webcal_user')) {
      $this->insertAttempts++;
    }
    return parent::prepare($query);
  }
}

final class WizardAdminCountTest extends TestCase
{
  private WizardAdminCountSqlite $sqlite;
  private WizardState $state;
  private WizardDatabase $database;

  protected function setUp(): void
  {
    $this->sqlite = new WizardAdminCountSqlite(':memory:');
    foreach (require __DIR__ . '/../wizard/shared/tables-sqlite3.php' as $sql) {
      $this->sqlite->exec($sql);
    }
    $this->state = new WizardState();
    $this->state->dbType = 'sqlite3';
    $this->database = new WizardDatabase($this->state);
    $connection = new ReflectionProperty(WizardDatabase::class, 'connection');
    $connection->setAccessible(true);
    $connection->setValue($this->database, $this->sqlite);
  }

  protected function tearDown(): void
  {
    $this->database->closeConnection();
  }

  public static function countFailures(): array
  {
    return [['false'], ['exception'], ['empty-result']];
  }

  /** @dataProvider countFailures */
  public function testCountFailurePreventsInsertion(string $failure): void
  {
    $this->sqlite->exec("INSERT INTO webcal_user (cal_login, cal_is_admin) VALUES ('existing', 'Y')");
    $this->sqlite->countFailure = $failure;

    self::assertFalse($this->database->createAdminUser('newadmin', 'New-admin-password'));
    self::assertSame(0, $this->sqlite->insertAttempts);
    self::assertSame(1, $this->sqlite->querySingle('SELECT COUNT(*) FROM webcal_user'));
    self::assertStringContainsString('administrator', (string) $this->database->getError());
  }

  /** @dataProvider countFailures */
  public function testDatabaseCheckReportsCountFailure(string $failure): void
  {
    $this->sqlite->countFailure = $failure;
    $this->state->dbConnectionSuccess = true;
    $this->state->adminUserCount = 2;

    $this->database->checkDatabase();

    self::assertFalse($this->state->dbConnectionSuccess);
    self::assertSame(2, $this->state->adminUserCount);
    self::assertStringContainsString('administrator', (string) $this->state->dbConnectionError);
  }

  public function testConfirmedZeroCountAllowsInitialAdministrator(): void
  {
    self::assertTrue($this->database->createAdminUser('firstadmin', 'First-admin-password'));
    self::assertSame(1, $this->sqlite->insertAttempts);
    self::assertSame(1, $this->sqlite->querySingle('SELECT COUNT(*) FROM webcal_user'));
  }

  public function testExistingAdministratorPreventsAnotherInsertion(): void
  {
    $this->sqlite->exec("INSERT INTO webcal_user (cal_login, cal_is_admin) VALUES ('existing', 'Y')");
    self::assertFalse($this->database->createAdminUser('newadmin', 'New-admin-password'));
    self::assertSame(0, $this->sqlite->insertAttempts);
  }
}
