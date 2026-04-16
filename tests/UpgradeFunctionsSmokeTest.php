<?php

use PHPUnit\Framework\TestCase;

/**
 * Tier 1 smoke tests for the restored wizard upgrade helper functions.
 *
 * These confirm each function loads, is callable, and doesn't crash
 * against a minimal empty schema.  They do NOT verify data-migration
 * correctness -- that's Tier 2 (focused behavior tests) or Tier 3
 * (end-to-end via web-upgrade-test.sh).
 *
 * We run each test in a separate process so dbi4php's file-level
 * globals (`$db_connection_info`, `$sqlite3_c`) don't leak between
 * tests.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class UpgradeFunctionsSmokeTest extends TestCase
{
  private string $dbFile;

  protected function setUp(): void
  {
    $this->dbFile = tempnam(sys_get_temp_dir(), 'wcsmoke_');

    $GLOBALS['db_type'] = 'sqlite3';
    $GLOBALS['db_persistent'] = false;

    require_once __DIR__ . '/../wizard/shared/upgrade-functions.php';

    dbi_connect('', '', '', $this->dbFile, false);
    $this->createMinimalSchema();
  }

  protected function tearDown(): void
  {
    if (isset($GLOBALS['sqlite3_c']) && $GLOBALS['sqlite3_c'] instanceof SQLite3) {
      @$GLOBALS['sqlite3_c']->close();
    }
    if (!empty($this->dbFile) && file_exists($this->dbFile)) {
      @unlink($this->dbFile);
    }
  }

  private function createMinimalSchema(): void
  {
    $ddl = [
      'CREATE TABLE webcal_entry_user (cal_id INTEGER, cal_category INTEGER, cal_login TEXT)',
      'CREATE TABLE webcal_categories (cat_id INTEGER PRIMARY KEY, cat_name TEXT, cat_owner TEXT, cat_icon_mime TEXT, cat_icon_blob BLOB)',
      'CREATE TABLE webcal_entry_categories (cal_id INTEGER, cat_id INTEGER, cat_order INTEGER, cat_owner TEXT)',
      'CREATE TABLE webcal_config (cal_setting TEXT PRIMARY KEY, cal_value TEXT)',
      'CREATE TABLE webcal_user_pref (cal_login TEXT, cal_setting TEXT, cal_value TEXT)',
      'CREATE TABLE webcal_entry (cal_id INTEGER PRIMARY KEY, cal_priority INTEGER)',
      'CREATE TABLE webcal_entry_repeats (cal_id INTEGER, cal_days TEXT, cal_byday TEXT, cal_type TEXT, cal_end TEXT)',
      'CREATE TABLE webcal_entry_repeats_not (cal_id INTEGER, cal_exdate INTEGER)',
      'CREATE TABLE webcal_site_extras (cal_id INTEGER, cal_type TEXT, cal_data TEXT)',
      'CREATE TABLE webcal_reminders (cal_id INTEGER, cal_date INTEGER, cal_offset INTEGER, cal_last_sent INTEGER, cal_times_sent INTEGER)',
    ];
    foreach ($ddl as $sql) {
      dbi_execute($sql);
    }
  }

  public function test_do_v11b_updates_runs_cleanly_on_empty_schema(): void
  {
    $this->assertTrue(do_v11b_updates());
  }

  public function test_do_v11e_updates_runs_cleanly_on_empty_schema(): void
  {
    $this->assertTrue(do_v11e_updates());
  }

  public function test_do_v11e_updates_is_idempotent_on_empty_schema(): void
  {
    $this->assertTrue(do_v11e_updates());
    $this->assertTrue(do_v11e_updates());
  }

  public function test_do_v1_9_11_updates_returns_true_when_icon_dir_missing(): void
  {
    $missing = sys_get_temp_dir() . '/wcsmoke_no_such_dir_' . uniqid();
    $this->assertFalse(is_dir($missing));
    $this->assertTrue(do_v1_9_11_updates(null, null, $missing));
  }

  public function test_do_v1_9_11_updates_returns_true_on_empty_icon_dir(): void
  {
    $emptyDir = sys_get_temp_dir() . '/wcsmoke_empty_' . uniqid();
    mkdir($emptyDir);
    try {
      $this->assertTrue(do_v1_9_11_updates(null, null, $emptyDir));
    } finally {
      @rmdir($emptyDir);
    }
  }
}
