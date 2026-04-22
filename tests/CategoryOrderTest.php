<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression test for GitHub issue #493: categories attached to an event
 * must be returned in the user-chosen order (cat_order), not in cat_id order.
 *
 * The write side (edit_entry_handler.php) assigns cat_order = $j++ as it walks
 * the comma-separated cat_id submission string; the read side
 * (get_categories_by_id) uses ORDER BY wec.cat_order. This test locks that
 * round trip down end-to-end via a temporary SQLite DB.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class CategoryOrderTest extends TestCase
{
  private string $dbFile;

  protected function setUp(): void
  {
    $this->dbFile = tempnam(sys_get_temp_dir(), 'wc_catorder_');

    $GLOBALS['db_type'] = 'sqlite3';
    $GLOBALS['db_persistent'] = false;
    $GLOBALS['login'] = 'tester';

    // dbi4php exposes dbi_connect / dbi_execute. functions.php carries
    // get_categories_by_id but does not itself include dbi4php.
    require_once __DIR__ . '/../includes/dbi4php.php';
    require_once __DIR__ . '/../includes/functions.php';

    dbi_connect('', '', '', $this->dbFile, false);
    $this->createSchema();
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

  private function createSchema(): void
  {
    $ddl = [
      'CREATE TABLE webcal_categories (
         cat_id INTEGER PRIMARY KEY,
         cat_name TEXT,
         cat_owner TEXT
       )',
      'CREATE TABLE webcal_entry_categories (
         cal_id INTEGER,
         cat_id INTEGER,
         cat_order INTEGER,
         cat_owner TEXT
       )',
    ];
    foreach ($ddl as $sql) {
      $this->assertTrue((bool) dbi_execute($sql), 'schema DDL failed');
    }
  }

  /**
   * Insert categories in a deliberately-shuffled cat_id order, then verify
   * get_categories_by_id returns them in the order we inserted them.
   */
  public function test_get_categories_by_id_honors_cat_order(): void
  {
    // Create three categories with non-sequential IDs so a "sort by cat_id"
    // regression would produce a different order than our inserts.
    $catalog = [
      [10, 'Zulu'],
      [20, 'Alpha'],
      [30, 'Mike'],
    ];
    foreach ($catalog as [$cid, $name]) {
      $this->assertTrue((bool) dbi_execute(
        'INSERT INTO webcal_categories (cat_id, cat_name, cat_owner) VALUES (?, ?, ?)',
        [$cid, $name, 'tester']
      ));
    }

    // Simulate edit_entry_handler.php writing cat_id="20,30,10" (user's
    // chosen order). cat_order is assigned 1,2,3 by position.
    $cal_id = 100;
    $userOrder = [20, 30, 10];
    foreach ($userOrder as $i => $cid) {
      $this->assertTrue((bool) dbi_execute(
        'INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_order, cat_owner) VALUES (?, ?, ?, ?)',
        [$cal_id, $cid, $i + 1, 'tester']
      ));
    }

    $result = get_categories_by_id($cal_id, 'tester');

    // Names, in the order returned.
    $actualNames = array_values($result);
    $this->assertSame(
      ['Alpha', 'Mike', 'Zulu'],
      $actualNames,
      'get_categories_by_id must honor cat_order, not cat_id'
    );
  }

  /**
   * A second event with a different order must not bleed into the first one.
   */
  public function test_get_categories_by_id_scopes_per_event(): void
  {
    foreach ([[10, 'Zulu'], [20, 'Alpha']] as [$cid, $name]) {
      dbi_execute(
        'INSERT INTO webcal_categories (cat_id, cat_name, cat_owner) VALUES (?, ?, ?)',
        [$cid, $name, 'tester']
      );
    }
    dbi_execute(
      'INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_order, cat_owner) VALUES (?, ?, ?, ?)',
      [101, 20, 1, 'tester']
    );
    dbi_execute(
      'INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_order, cat_owner) VALUES (?, ?, ?, ?)',
      [101, 10, 2, 'tester']
    );
    dbi_execute(
      'INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_order, cat_owner) VALUES (?, ?, ?, ?)',
      [102, 10, 1, 'tester']
    );
    dbi_execute(
      'INSERT INTO webcal_entry_categories (cal_id, cat_id, cat_order, cat_owner) VALUES (?, ?, ?, ?)',
      [102, 20, 2, 'tester']
    );

    $this->assertSame(['Alpha', 'Zulu'], array_values(get_categories_by_id(101, 'tester')));
    $this->assertSame(['Zulu', 'Alpha'], array_values(get_categories_by_id(102, 'tester')));
  }
}
