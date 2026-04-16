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
