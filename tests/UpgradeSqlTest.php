<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wizard/shared/upgrade-sql.php';
require_once __DIR__ . '/../wizard/WizardDatabase.php';
require_once __DIR__ . '/../wizard/WizardState.php';

/**
 * Regression tests for wizard upgrade SQL generation.
 *
 * Covers GitHub issue #639: upgrading from v1.3.0 through v1.9.6 failed with
 * "Multiple primary key defined" because the v1.9.6 step re-added a PRIMARY KEY
 * that has existed on webcal_entry_categories since v1.1.0c-CVS.
 */
final class UpgradeSqlTest extends TestCase
{
  public function test_v196_does_not_readd_entry_categories_pk_mysql(): void
  {
    $sql = getSqlUpdates('v1.3.0', 'mysql', false);
    $joined = implode("\n", $sql);
    $this->assertStringNotContainsStringIgnoringCase(
      'ADD PRIMARY KEY (cal_id, cat_id, cat_order, cat_owner)',
      $joined,
      'v1.9.6 upgrade must not re-add a PK that already exists since v1.1.0c-CVS'
    );
  }

  public function test_v196_does_not_readd_entry_categories_pk_postgres(): void
  {
    $sql = getSqlUpdates('v1.3.0', 'postgresql', false);
    $joined = implode("\n", $sql);
    $this->assertStringNotContainsStringIgnoringCase(
      'ADD CONSTRAINT pkey_webcal_entry_categories',
      $joined,
      'Postgres v1.9.6 upgrade must not re-add a constraint for an existing PK'
    );
  }

  public function test_v196_still_cleans_null_cat_owner(): void
  {
    $sql = getSqlUpdates('v1.3.0', 'mysql', false);
    $joined = implode("\n", $sql);
    $this->assertStringContainsString(
      "UPDATE webcal_entry_categories SET cat_owner = '' WHERE cat_owner IS NULL",
      $joined,
      'v1.9.6 must retain the defensive cat_owner NULL cleanup'
    );
  }

  /**
   * Fix A: the v1.9.11 MODIFY that makes webcal_categories.cat_owner NOT NULL
   * must be preceded by the UPDATE that clears NULLs, otherwise MariaDB in
   * strict mode returns "Data truncated for column 'cat_owner' at row 1".
   */
  public function test_v1911_clears_null_cat_owner_before_modify(): void
  {
    $sql = getSqlUpdates('v1.9.0', 'mysql', false);
    $joined = implode("\n", $sql);

    $updatePos = stripos($joined, "UPDATE webcal_categories SET cat_owner = '' WHERE cat_owner IS NULL");
    $modifyPos = stripos($joined, 'MODIFY cat_owner VARCHAR(25) DEFAULT \'\' NOT NULL');

    $this->assertNotFalse($updatePos, 'v1.9.11 must UPDATE NULL cat_owner rows first');
    $this->assertNotFalse($modifyPos, 'v1.9.11 must still MODIFY cat_owner NOT NULL');
    $this->assertLessThan($modifyPos, $updatePos, 'UPDATE must come before MODIFY');
  }

  public function test_multiple_primary_key_error_is_ignored(): void
  {
    $ref = new ReflectionClass(WizardDatabase::class);
    $method = $ref->getMethod('isIgnorableSchemaError');
    $method->setAccessible(true);

    $state = new WizardState();
    $db = new WizardDatabase($state);

    $this->assertTrue(
      $method->invoke($db, 'Multiple primary key defined'),
      'MariaDB/MySQL "Multiple primary key defined" must be treated as non-fatal'
    );
    $this->assertTrue(
      $method->invoke($db, 'ERROR 1068: Multiple primary key defined'),
      'Error string with numeric code prefix must still be detected'
    );
  }

  public function test_unrelated_schema_error_is_not_ignored(): void
  {
    $ref = new ReflectionClass(WizardDatabase::class);
    $method = $ref->getMethod('isIgnorableSchemaError');
    $method->setAccessible(true);

    $state = new WizardState();
    $db = new WizardDatabase($state);

    $this->assertFalse(
      $method->invoke($db, 'Table webcal_entry does not exist'),
      'Genuine errors must still abort the upgrade'
    );
  }

  /**
   * Fix C: executeCommand's error message must include the failing SQL so
   * the user can tell which upgrade step tripped.
   */
  public function test_formatCommandError_includes_failing_sql(): void
  {
    $ref = new ReflectionClass(WizardDatabase::class);
    $method = $ref->getMethod('formatCommandError');
    $method->setAccessible(true);

    $state = new WizardState();
    $db = new WizardDatabase($state);

    $formatted = $method->invoke(
      $db,
      "Data truncated for column 'cat_owner' at row 1",
      'ALTER TABLE webcal_categories MODIFY cat_owner VARCHAR(25) DEFAULT \'\' NOT NULL'
    );
    $this->assertStringContainsString("Data truncated for column 'cat_owner'", $formatted);
    $this->assertStringContainsString('ALTER TABLE webcal_categories MODIFY cat_owner', $formatted);
    $this->assertStringContainsString('Failed SQL:', $formatted);
  }

  public function test_formatCommandError_truncates_long_sql(): void
  {
    $ref = new ReflectionClass(WizardDatabase::class);
    $method = $ref->getMethod('formatCommandError');
    $method->setAccessible(true);

    $state = new WizardState();
    $db = new WizardDatabase($state);

    $longSql = 'INSERT INTO x VALUES (' . str_repeat('a', 1000) . ')';
    $formatted = $method->invoke($db, 'boom', $longSql);
    $this->assertLessThan(strlen($longSql) + 100, strlen($formatted));
    $this->assertStringContainsString('...', $formatted);
  }

  /**
   * Fix B: updateVersionInDb must propagate a failure (e.g. webcal_config
   * missing) instead of silently returning true.  Previously this masked a
   * post-install state where WEBCAL_PROGRAM_VERSION never got written and
   * the app looped back to the wizard on every request.
   */
  public function test_updateVersionInDb_reports_failure_when_table_missing(): void
  {
    $dbFile = tempnam(sys_get_temp_dir(), 'wcver_');
    $sqlite = new SQLite3($dbFile);
    // Intentionally no webcal_config table -- the INSERT must fail.

    $state = new WizardState();
    $state->dbType = 'sqlite3';
    $state->programVersion = 'v1.9.16';

    $db = new WizardDatabase($state);

    // Inject the connection via reflection (constructor doesn't take one).
    $cRef = new ReflectionProperty(WizardDatabase::class, 'connection');
    $cRef->setAccessible(true);
    $cRef->setValue($db, $sqlite);

    $mRef = new ReflectionMethod(WizardDatabase::class, 'updateVersionInDb');
    $mRef->setAccessible(true);

    try {
      $result = $mRef->invoke($db);
      $this->assertFalse($result, 'updateVersionInDb must return false when the INSERT fails');
      $this->assertNotNull($db->getError(), 'Error message must be populated on failure');
      $this->assertStringContainsString('WEBCAL_PROGRAM_VERSION', (string) $db->getError());
    } finally {
      $sqlite->close();
      @unlink($dbFile);
    }
  }

  /**
   * Every 'upgrade-function' named in the upgrade matrix must be defined
   * in wizard/shared/upgrade-functions.php (or elsewhere).  Silently
   * skipping an upgrade function caused data migrations for v1.1.0c,
   * v1.1.0e, and v1.9.11 to no-op during the v1.3.0 -> v1.9.x upgrade
   * path in #639.
   */
  public function test_every_upgrade_function_reference_resolves(): void
  {
    require_once __DIR__ . '/../wizard/shared/upgrade-functions.php';

    global $updates;
    $missing = [];
    foreach ($updates as $update) {
      if (!empty($update['upgrade-function'])) {
        $fn = $update['upgrade-function'];
        if (!function_exists($fn)) {
          $missing[] = $update['version'] . ' -> ' . $fn;
        }
      }
    }
    $this->assertSame([], $missing, 'Upgrade-function references without a matching definition');
  }

  /**
   * Runtime code must not fatal-error when the wizard/ directory has been
   * removed post-install (users are instructed to rename/remove it).
   * Regression for GitHub issue #639 500/redirect-loop.
   *
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function test_upgrade_requires_db_changes_survives_missing_wizard(): void
  {
    require_once __DIR__ . '/../includes/functions.php';

    $wizardSql = __DIR__ . '/../wizard/shared/upgrade-sql.php';
    $hidden = $wizardSql . '.hidden-for-test';
    $this->assertFileExists($wizardSql);
    rename($wizardSql, $hidden);

    try {
      $result = upgrade_requires_db_changes('mysql', 'v1.9.0', 'v1.9.16');
      $this->assertTrue(
        $result,
        'When wizard SQL is missing, must return true so caller shows re-install message'
      );
    } finally {
      rename($hidden, $wizardSql);
    }
  }
}
