<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Issue #734 -- the installer must seed config defaults on UPGRADES, not
 * only on fresh installs.
 *
 * wizard/shared/upgrade-sql.php contains no `INSERT INTO webcal_config` at
 * all, and executeUpgrade() used to call loadDefaultConfig() only when the
 * database was empty. An upgraded site therefore never received a row for
 * any setting introduced after it was first installed, and those settings
 * stayed undefined at runtime.
 *
 * Seeding an existing database is only safe if it never overwrites what the
 * administrator already chose, so that is asserted here too.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WizardConfigSeedingTest extends TestCase
{
  private string $dbFile;

  protected function setUp(): void
  {
    $this->dbFile = tempnam(sys_get_temp_dir(), 'wc_seed_');

    require_once __DIR__ . '/../wizard/WizardDatabase.php';
    require_once __DIR__ . '/../includes/default_config.php';
  }

  protected function tearDown(): void
  {
    if (!empty($this->dbFile) && file_exists($this->dbFile)) {
      @unlink($this->dbFile);
    }
  }

  /**
   * Build a WizardDatabase wired to $sqlite, bypassing connect() so the test
   * controls the schema precisely.
   */
  private function wizardDatabaseFor(SQLite3 $sqlite, ?WizardState $state = null): object
  {
    if ($state === null) {
      $state = new WizardState();
    }
    $state->dbType = 'sqlite3';
    $state->dbDatabase = $this->dbFile;

    $db = new WizardDatabase($state);

    $connection = new ReflectionProperty(WizardDatabase::class, 'connection');
    $connection->setAccessible(true);
    $connection->setValue($db, $sqlite);

    return $db;
  }

  private function invokePrivate(object $db, string $method, array $args = [])
  {
    $m = new ReflectionMethod(WizardDatabase::class, $method);
    $m->setAccessible(true);
    return $m->invokeArgs($db, $args);
  }

  /**
   * @return array<string,string>
   */
  private function readConfig(SQLite3 $sqlite): array
  {
    $out = [];
    $res = $sqlite->query('SELECT cal_setting, cal_value FROM webcal_config');
    while ($row = $res->fetchArray(SQLITE3_NUM)) {
      $out[$row[0]] = $row[1];
    }
    return $out;
  }

  /**
   * The upgrade case: a database that already has webcal_config rows, as
   * every existing install does.
   */
  public function testSeedingAnExistingDatabaseFillsGapsWithoutOverwriting(): void
  {
    $sqlite = new SQLite3($this->dbFile);
    $sqlite->exec('CREATE TABLE webcal_config ( cal_setting VARCHAR(50)
      NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_setting ) )');
    // An old install: the version row, plus a setting the admin changed
    // away from its default.
    $sqlite->exec("INSERT INTO webcal_config VALUES ('WEBCAL_PROGRAM_VERSION', 'v1.9.20')");
    $sqlite->exec("INSERT INTO webcal_config VALUES ('ALLOW_VIEW_OTHER', 'N')");

    $db = $this->wizardDatabaseFor($sqlite);
    self::assertTrue($this->invokePrivate($db, 'loadDefaultConfig'),
      'Seeding an existing database must succeed: ' . (string) $db->getError());

    $config = $this->readConfig($sqlite);

    // The administrator's choice survives.
    self::assertSame('N', $config['ALLOW_VIEW_OTHER'],
      'Seeding must never overwrite a setting that already has a row.');

    // updateVersionInDb() owns this one; loadDefaultConfig must skip it or
    // it would stamp the new version before the upgrade actually ran.
    self::assertSame('v1.9.20', $config['WEBCAL_PROGRAM_VERSION']);

    // Settings the old install never had are now present at their
    // documented defaults instead of being absent.
    self::assertSame('N', $config['MCP_SERVER_ENABLED'] ?? null);
    self::assertSame('N', $config['PUBLIC_ACCESS_VIEW_PART'] ?? null);
    self::assertSame('Y', $config['CSRF_PROTECTION'] ?? null);

    // Every documented setting ends up with a row.
    $missing = array_diff(
      array_keys(webcal_config_defaults()),
      array_keys($config)
    );
    self::assertSame([], array_values($missing));
  }

  /**
   * Running it twice must be a no-op, since executeUpgrade() now calls it on
   * every run including fresh installs, where it has already run once.
   */
  public function testSeedingIsIdempotent(): void
  {
    $sqlite = new SQLite3($this->dbFile);
    $sqlite->exec('CREATE TABLE webcal_config ( cal_setting VARCHAR(50)
      NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_setting ) )');

    $db = $this->wizardDatabaseFor($sqlite);

    self::assertTrue($this->invokePrivate($db, 'loadDefaultConfig'));
    $first = $this->readConfig($sqlite);

    self::assertTrue($this->invokePrivate($db, 'loadDefaultConfig'),
      'A second pass must not fail on duplicate primary keys.');
    self::assertSame($first, $this->readConfig($sqlite));
  }

  /**
   * The wiring that actually matters. executeUpgrade() used to call
   * loadDefaultConfig() only inside its `if ($databaseIsEmpty)` branch, so
   * an upgrade run -- the case every existing site goes through -- never
   * seeded anything. Exercise the real entry point, not just the helper.
   */
  public function testUpgradeRunSeedsDefaults(): void
  {
    $sqlite = new SQLite3($this->dbFile);
    $sqlite->exec('CREATE TABLE webcal_config ( cal_setting VARCHAR(50)
      NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_setting ) )');
    $sqlite->exec("INSERT INTO webcal_config VALUES ('WEBCAL_PROGRAM_VERSION', 'v1.9.20')");
    $sqlite->exec("INSERT INTO webcal_config VALUES ('ALLOW_VIEW_OTHER', 'N')");

    $state = new WizardState();
    // An upgrade, not a fresh install -- the branch that used to skip
    // seeding. No schema work queued, so this isolates the config step.
    $state->databaseIsEmpty = false;
    $state->upgradeSqlCommands = [];

    $db = $this->wizardDatabaseFor($sqlite, $state);
    self::assertTrue($db->executeUpgrade(),
      'Upgrade run failed: ' . (string) $db->getError());

    $config = $this->readConfig($sqlite);

    self::assertSame('N', $config['ALLOW_VIEW_OTHER'],
      'An upgrade must not reset settings the admin already chose.');
    self::assertSame('N', $config['MCP_SERVER_ENABLED'] ?? null,
      'An upgrade must seed settings the old install never had.');
    self::assertArrayHasKey('CSRF_PROTECTION', $config);
  }

  /**
   * Regression for the MySQL CI failure on PR #735.
   *
   * getExistingConfigSettings() was missing the select_db() call that every
   * other mysqli query in this class makes, so on MySQL the lookup returned
   * an empty set, seeding tried to re-insert all ~160 rows, and the
   * duplicate-key error aborted executeUpgrade() -- taking the whole
   * install down. Seeding must survive a lookup that comes back blind,
   * because "the row already exists" is precisely the state it wanted.
   *
   * The duplicate error string is taken from the driver rather than
   * hand-written, so this fails if the pattern stops matching reality.
   */
  public function testSeedingToleratesRowsThatAlreadyExist(): void
  {
    $sqlite = new SQLite3($this->dbFile);
    $sqlite->exec('CREATE TABLE webcal_config ( cal_setting VARCHAR(50)
      NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_setting ) )');
    $sqlite->exec("INSERT INTO webcal_config VALUES ('ALLOW_VIEW_OTHER', 'Y')");

    $db = $this->wizardDatabaseFor($sqlite);

    $duplicate = "INSERT INTO webcal_config (cal_setting, cal_value) "
      . "VALUES ('ALLOW_VIEW_OTHER', 'Y')";
    self::assertFalse($this->invokePrivate($db, 'executeCommand', [$duplicate]),
      'Re-inserting an existing primary key should fail at the driver.');

    self::assertTrue(
      $this->invokePrivate($db, 'isDuplicateRowError', [(string) $db->getError()]),
      'The driver said: ' . (string) $db->getError() . ' -- seeding must '
      . 'recognise that as "row already exists", not abort the upgrade.'
    );
  }

  /**
   * The MySQL and PostgreSQL wordings cannot be produced locally, so pin
   * them as strings. These are what actually broke CI.
   *
   * @dataProvider duplicateRowErrorProvider
   */
  public function testDuplicateRowErrorsAreRecognisedPerDriver(string $error): void
  {
    $db = $this->wizardDatabaseFor(new SQLite3($this->dbFile));
    self::assertTrue($this->invokePrivate($db, 'isDuplicateRowError', [$error]));
  }

  /**
   * @return array<string,array{0:string}>
   */
  public function duplicateRowErrorProvider(): array
  {
    return [
      'MySQL' => ["Duplicate entry 'ALLOW_VIEW_OTHER' for key 'PRIMARY'"],
      'PostgreSQL' => ['ERROR: duplicate key value violates unique '
        . 'constraint "webcal_config_pkey"'],
      'SQLite 3' => ['UNIQUE constraint failed: webcal_config.cal_setting'],
    ];
  }

  /**
   * A real error must still abort, or the tolerance above would swallow
   * genuine failures.
   */
  public function testNonDuplicateErrorsAreStillFatal(): void
  {
    $db = $this->wizardDatabaseFor(new SQLite3($this->dbFile));
    self::assertFalse(
      $this->invokePrivate($db, 'isDuplicateRowError', ['no such table: webcal_config'])
    );
  }

  /**
   * Fresh install: webcal_config does not exist yet. The lookup must report
   * "nothing present" rather than letting the error escape.
   */
  public function testExistingSettingsLookupToleratesMissingTable(): void
  {
    $sqlite = new SQLite3($this->dbFile);
    $db = $this->wizardDatabaseFor($sqlite);

    self::assertSame([],
      $this->invokePrivate($db, 'getExistingConfigSettings'));
  }
}
