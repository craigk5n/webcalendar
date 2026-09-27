<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SourceText.php';

/**
 * Regression tests for purge.php, covering a cluster of bugs that between
 * them left the page unable to purge anything -- and, once it could, able to
 * purge more than the admin asked for.  Reported by Tom (shycat.net).
 *
 * 1. The Delete button carried no value attribute, so browsers submitted
 *    "delete=" and the $do_purge guard was never true: the page did nothing.
 * 2. The page read end_year/end_month/end_day, but date_selection() renders a
 *    single "end__YMD" input, so the date cutoff was always "00000000".
 * 3. The purge-all query referenced weu.cal_status with no webcal_entry_user
 *    in its FROM clause -- a SQL error whenever "purge deleted only" was set.
 * 4. The date query joined webcal_entry_user with no join condition, so it
 *    returned a cartesian product and reported inflated row counts.
 * 5. The "ALL users" branch overwrote $tail instead of appending to it,
 *    silently discarding the "deleted only" restriction. An admin asking to
 *    purge deleted events would have purged every event before the cutoff.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class PurgeEventSelectionTest extends TestCase
{
  private string $dbFile;

  protected function setUp(): void
  {
    $this->dbFile = tempnam(sys_get_temp_dir(), 'wc_purge_');

    $GLOBALS['db_type'] = 'sqlite3';
    $GLOBALS['db_persistent'] = false;
    $GLOBALS['sqlLog'] = '';

    require_once __DIR__ . '/../includes/dbi4php.php';

    dbi_connect('', '', '', $this->dbFile, false);
    $this->createSchema();
    $this->seedFixture();
    $this->loadPurgeFunctions();
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

  /**
   * purge.php is a page, not a library: it redirects non-admins on include.
   * Pull just the two functions under test out of the source so we exercise
   * the real code rather than a copy of it.
   */
  private function loadPurgeFunctions(): void
  {
    if (function_exists('get_purge_ids')) {
      return;
    }
    $src = file_get_contents(__DIR__ . '/../purge.php');
    self::assertNotFalse($src);

    $fns = '';
    foreach (['get_purge_ids', 'get_ids'] as $name) {
      $fn = SourceText::phpFunction($src, $name);
      self::assertIsString($fn, "purge.php must define $name()");
      $fns .= $fn . "\n";
    }
    eval($fns);
  }

  private function createSchema(): void
  {
    $ddl = [
      'CREATE TABLE webcal_entry (
         cal_id INTEGER PRIMARY KEY,
         cal_create_by TEXT,
         cal_date INTEGER,
         cal_type TEXT,
         cal_name TEXT
       )',
      'CREATE TABLE webcal_entry_user (
         cal_id INTEGER,
         cal_login TEXT,
         cal_status TEXT
       )',
      'CREATE TABLE webcal_entry_repeats (
         cal_id INTEGER,
         cal_end INTEGER
       )',
    ];
    foreach ($ddl as $sql) {
      self::assertTrue((bool) dbi_execute($sql), 'schema DDL failed');
    }
  }

  /**
   * 101 old, alice only, accepted
   * 102 old, alice + bob  -> shared, so a single-user purge must skip it
   * 103 old, bob only, marked deleted
   * 104 recent (2026), alice only -- must never match a 2021 cutoff
   * 105 old repeating event ending 2020, alice only
   */
  private function seedFixture(): void
  {
    $events = [
      [101, 'alice', 20200101, 'E'],
      [102, 'alice', 20200102, 'E'],
      [103, 'bob',   20200103, 'E'],
      [104, 'alice', 20260101, 'E'],
      [105, 'alice', 20200104, 'M'],
    ];
    foreach ($events as [$id, $by, $date, $type]) {
      dbi_execute(
        'INSERT INTO webcal_entry (cal_id, cal_create_by, cal_date, cal_type, cal_name)
         VALUES (?, ?, ?, ?, ?)',
        [$id, $by, $date, $type, "event $id"]
      );
    }

    $participants = [
      [101, 'alice', 'A'],
      [102, 'alice', 'A'],
      [102, 'bob',   'A'],
      [103, 'bob',   'D'],
      [104, 'alice', 'A'],
      [105, 'alice', 'A'],
    ];
    foreach ($participants as [$id, $login, $status]) {
      dbi_execute(
        'INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status)
         VALUES (?, ?, ?)',
        [$id, $login, $status]
      );
    }

    dbi_execute('INSERT INTO webcal_entry_repeats (cal_id, cal_end) VALUES (?, ?)',
      [105, 20200601]);
  }

  private function ids($purge_all, $username, $end_date, $purge_deleted): array
  {
    $ids = get_purge_ids($purge_all, $username, $end_date, $purge_deleted);
    sort($ids);
    return array_map('intval', $ids);
  }

  // ---- Bug 5: the dangerous one -------------------------------------

  public function test_deleted_only_filter_survives_the_all_users_branch(): void
  {
    $ids = $this->ids('', 'ALL', '20210101', 'Y');

    self::assertSame(
      [103],
      $ids,
      'Only event 103 is marked deleted. Returning the others means an admin '
      . 'who asked to purge deleted events would have purged every event '
      . 'before the cutoff -- irreversibly.'
    );
  }

  public function test_deleted_only_filter_survives_for_a_named_user(): void
  {
    self::assertSame([103], $this->ids('', 'bob', '20210101', 'Y'));
  }

  // ---- Bug 4: cartesian product -------------------------------------

  public function test_each_event_is_returned_exactly_once(): void
  {
    $ids = $this->ids('', 'ALL', '20210101', '');

    self::assertSame(
      [101, 102, 103, 105],
      $ids,
      'Event 102 has two participants. Without a join condition the query '
      . 'returns a cartesian product and each event repeats, inflating the '
      . 'row counts shown to the admin.'
    );
    self::assertSame($ids, array_values(array_unique($ids)), 'duplicate ids');
  }

  public function test_events_after_the_cutoff_are_never_selected(): void
  {
    self::assertNotContains(104, $this->ids('', 'ALL', '20210101', ''));
  }

  public function test_repeating_events_are_selected_by_their_end_date(): void
  {
    self::assertContains(105, $this->ids('', 'ALL', '20210101', ''));
    self::assertNotContains(105, $this->ids('', 'ALL', '20200101', ''));
  }

  // ---- Bug 3: purge-all query referenced weu with no join -----------

  public function test_purge_all_for_a_user_with_deleted_filter_runs(): void
  {
    self::assertSame(
      [103],
      $this->ids('Y', 'bob', '', 'Y'),
      'This combination used to reference weu.cal_status with no '
      . 'webcal_entry_user in the FROM clause, producing a SQL error.'
    );
  }

  public function test_purge_all_for_a_user_skips_shared_events(): void
  {
    // 102 is shared with bob, so the "no other participants" rule protects it.
    // 105 is alice's repeating event and purge_all means every event she owns.
    self::assertSame([101, 104, 105], $this->ids('Y', 'alice', '', ''));
  }

  public function test_purge_all_for_every_user_is_a_wildcard(): void
  {
    self::assertSame(['ALL'], get_purge_ids('Y', 'ALL', '', ''));
  }

  public function test_no_criteria_selects_nothing(): void
  {
    self::assertSame([], get_purge_ids('', 'alice', '', ''));
  }

  // ---- Parameter binding --------------------------------------------

  public function test_username_is_bound_not_interpolated(): void
  {
    $ids = get_purge_ids('Y', "alice' OR '1'='1", '', '');

    self::assertSame(
      [],
      $ids,
      'A quote in the username must not break out of the query. If this '
      . 'returns rows, the value is being interpolated rather than bound.'
    );
  }

  // ---- Bugs 1 and 2: the form/handler contract ----------------------

  public function test_delete_button_submits_a_non_empty_value(): void
  {
    $src = file_get_contents(__DIR__ . '/../purge.php');

    self::assertMatchesRegularExpression(
      '/<button[^>]*\bname="delete"[^>]*\bvalue="[^"]+"/',
      $src,
      'A submit button with no value attribute submits an empty string, so '
      . 'the ! empty($delete) guard never fires and the page silently does '
      . 'nothing when Delete is clicked.'
    );
  }

  public function test_page_reads_the_field_that_date_selection_renders(): void
  {
    require_once __DIR__ . '/../includes/functions.php';

    $html = date_selection('end_', '20260101');
    self::assertMatchesRegularExpression('/name="([^"]+)"/', $html);
    preg_match('/name="([^"]+)"/', $html, $m);
    $rendered = $m[1];

    $src = file_get_contents(__DIR__ . '/../purge.php');
    self::assertStringContainsString(
      "getPostValue ( '$rendered' )",
      $src,
      "purge.php must read the field date_selection() actually renders "
      . "($rendered). It previously read end_year/end_month/end_day, which "
      . 'the form has not contained since the date picker became a single '
      . 'HTML5 date input.'
    );
  }
}
