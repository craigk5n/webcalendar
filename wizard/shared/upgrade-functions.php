<?php
/**
 * PHP upgrade helpers referenced from wizard/shared/upgrade-sql.php.
 *
 * Historically these lived in install/install_functions.php and ran under
 * dbi4php's global connection.  When the installer was rewritten as the
 * new wizard (#608) the SQL-only portions moved over but the three
 * PHP-function upgrade steps (do_v11b_updates, do_v11e_updates,
 * do_v1_9_11_updates) were dropped on the floor, so any upgrade path that
 * crossed v1.1.0c, v1.1.0e, or v1.9.11 quietly skipped critical data
 * migrations (recurring event byday rewrites, reminder migration from
 * site_extras, category-icon move from filesystem to DB).
 *
 * This file restores the original function bodies with minimal changes:
 *   - Each function is booted through dbi4php using the wizard's DB
 *     settings so the dbi_* calls work without requiring config.php
 *   - die_miserable_death() is replaced with a logged skip so a single
 *     bad icon file doesn't abort the whole upgrade
 *   - The wc-icons path is updated for the new location (wizard/shared/
 *     instead of install/)
 *
 * IDEMPOTENCY NOTE
 * ----------------
 * Schema-based version detection (see WizardDatabase::checkDatabase) can
 * accurately recover from partial/failed upgrades for SQL-only blocks
 * because CREATE TABLE IF NOT EXISTS and the isIgnorableSchemaError()
 * whitelist make re-running those blocks safe.  The functions below are
 * NOT all idempotent -- notably do_v11b_updates' "cal_end + 1 day" loop
 * would double-bump rows if re-run after partial completion.  If a
 * function aborts mid-flight we can end up with silently corrupted data.
 *
 * New migration helpers added here should be written so they can be run
 * more than once safely -- guard with a WHERE clause that matches only
 * unmigrated rows, or record a migration marker in webcal_config.
 */

require_once __DIR__ . '/../../includes/dbi4php.php';

/**
 * Ensure dbi4php is connected using the wizard's current database
 * credentials.  Safe to call repeatedly; only connects once per request.
 */
function wizard_upgrade_bootstrap_dbi($state): void
{
  static $connected = false;
  if ($connected) return;

  $GLOBALS['db_type'] = $state->dbType;
  $GLOBALS['db_persistent'] = false;
  if (!isset($GLOBALS['db_cachedir'])) {
    $GLOBALS['db_cachedir'] = '';
  }

  dbi_connect(
    $state->dbHost,
    $state->dbLogin,
    $state->dbPassword,
    $state->dbDatabase,
    false
  );
  $connected = true;
}

/**
 * v1.1.0c-CVS upgrade: migrate legacy per-event category to
 * webcal_entry_categories, rewrite recurring-event day strings into the
 * new cal_byday format, convert exclusive end dates, and remap priority
 * values.  Originally install/install_functions.php::do_v11b_updates.
 */
function do_v11b_updates($connection = null, $state = null): bool
{
  if ($state !== null) {
    wizard_upgrade_bootstrap_dbi($state);
  }

  $res = dbi_execute('SELECT weu.cal_id, cal_category, cat_owner
    FROM webcal_entry_user weu, webcal_categories wc
    WHERE weu.cal_category = wc.cat_id');
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      dbi_execute('INSERT INTO webcal_entry_categories ( cal_id, cat_id,'
        . (empty($row[2]) ? 'cat_order' : 'cat_owner')
        . ' ) VALUES ( ?, ?, ? )', [
        $row[0], $row[1],
        (empty($row[2]) ? 99 : $row[2])
      ]);
    }
    dbi_free_result($res);
  }

  // Update LANGUAGE settings from Browser-Defined to none.
  dbi_execute('UPDATE webcal_config SET cal_value = \'none\'
    WHERE cal_setting = \'LANGUAGE\' AND cal_value = \'Browser-defined\'');
  dbi_execute('UPDATE webcal_user_pref SET cal_value = \'none\'
    WHERE cal_setting = \'LANGUAGE\' AND cal_value = \'Browser-defined\'');

  // Clear old category values.
  dbi_execute('UPDATE webcal_entry_user SET cal_category = NULL');
  // Mark existing exclusions as new exclusion type.
  dbi_execute('UPDATE webcal_entry_repeats_not SET cal_exdate = 1');
  // Change cal_days format to cal_byday format.
  // Deprecate monthlyByDayR to simply monthlyByDay.
  dbi_execute('UPDATE webcal_entry_repeats SET cal_type = \'monthlyByDay\'
    WHERE cal_type = \'monthlyByDayR\'');

  $res = dbi_execute('SELECT cal_id, cal_days FROM webcal_entry_repeats');
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      if (!empty($row[1]) && $row[1] != 'yyyyyyy' && $row[1] != 'nnnnnnn') {
        $byday = [];
        if (substr($row[1], 0, 1) == 'y') $byday[] = 'SU';
        if (substr($row[1], 1, 1) == 'y') $byday[] = 'MO';
        if (substr($row[1], 2, 1) == 'y') $byday[] = 'TU';
        if (substr($row[1], 3, 1) == 'y') $byday[] = 'WE';
        if (substr($row[1], 4, 1) == 'y') $byday[] = 'TH';
        if (substr($row[1], 5, 1) == 'y') $byday[] = 'FR';
        if (substr($row[1], 6, 1) == 'y') $byday[] = 'SA';
        $bydays = implode(',', $byday);
        dbi_execute('UPDATE webcal_entry_repeats SET cal_byday = ?
          WHERE cal_id = ?', [$bydays, $row[0]]);
      }
    }
    dbi_free_result($res);
  }

  // Repeat end dates are now exclusive so we need to add 1 day to each.
  $res = dbi_execute('SELECT cal_end, cal_id FROM webcal_entry_repeats');
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      if (!empty($row[0])) {
        $dY = substr($row[0], 0, 4);
        $dm = substr($row[0], 4, 2);
        $dd = substr($row[0], 6, 2);
        $new_date = date('Ymd', gmmktime(0, 0, 0, $dm, $dd, $dY) + 86400);
        dbi_execute('UPDATE webcal_entry_repeats SET cal_end = ?
          WHERE cal_id = ?', [$new_date, $row[1]]);
      }
    }
    dbi_free_result($res);
  }

  // Update Priority to new values.
  // Old High=3, Low=1; New Highest=1, Lowest=9.
  // We leave 3 alone and change 1,2 to 7,5.
  dbi_execute('UPDATE webcal_entry SET cal_priority = 7 WHERE cal_priority = 1');
  dbi_execute('UPDATE webcal_entry SET cal_priority = 5 WHERE cal_priority = 2');

  return true;
}

/**
 * v1.1.0e-CVS upgrade: convert reminder data stored in webcal_site_extras
 * (cal_type = '7') into the new webcal_reminders table and drop the
 * obsolete webcal_reminder_log.  Originally
 * install/install_functions.php::do_v11e_updates.
 */
function do_v11e_updates($connection = null, $state = null): bool
{
  if ($state !== null) {
    wizard_upgrade_bootstrap_dbi($state);
  }

  $reminder_log_exists = false;
  $res = dbi_execute('SELECT cal_id, cal_data
    FROM webcal_site_extras WHERE cal_type = \'7\'');
  $done = [];
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      if (!empty($done[$row[0]])) continue;

      $date = $last_sent = $offset = $times_sent = 0;
      if (strlen($row[1]) == 8) {
        // cal_data is probably a date
        $date = mktime(
          0, 0, 0,
          substr($row[1], 4, 2),
          substr($row[1], 6, 2),
          substr($row[1], 0, 4)
        );
      } else {
        $offset = $row[1];
      }

      $res2 = dbi_execute(
        'SELECT cal_last_sent FROM webcal_reminder_log
          WHERE cal_id = ? AND cal_last_sent > 0',
        [$row[0]],
        false,
        false
      );
      if ($res2) {
        $reminder_log_exists = true;
        $row2 = dbi_fetch_row($res2);
        $times_sent = 1;
        $last_sent = (!empty($row2[0]) ? $row2[0] : 0);
        dbi_free_result($res2);
      }

      dbi_execute(
        'INSERT INTO webcal_reminders ( cal_id, cal_date,
          cal_offset, cal_last_sent, cal_times_sent ) VALUES ( ?, ?, ?, ?, ? )',
        [$row[0], $date, $offset, $last_sent, $times_sent]
      );
      $done[$row[0]] = true;
    }
    dbi_free_result($res);

    // Remove reminders from site_extras.
    dbi_execute('DELETE FROM webcal_site_extras
      WHERE webcal_site_extras.cal_type = \'7\'');
    // Remove obsolete webcal_reminder_log table, if present.
    if ($reminder_log_exists) {
      dbi_execute('DELETE FROM webcal_reminder_log', [], false, false);
      dbi_execute('DROP TABLE webcal_reminder_log', [], false, false);
    }
  }

  return true;
}

/**
 * v1.9.11 upgrade: migrate category icons from the wc-icons/ directory
 * into the webcal_categories.cat_icon_blob column so the full site can
 * be backed up by backing up the database alone.  Originally
 * install/install_functions.php::do_v1_9_11_updates.
 */
function do_v1_9_11_updates($connection = null, $state = null, ?string $iconDir = null): bool
{
  if ($state !== null) {
    wizard_upgrade_bootstrap_dbi($state);
  }

  // wizard/shared/ -> repo root -> wc-icons/ (overridable for tests)
  $icon_path = $iconDir ?? __DIR__ . '/../../wc-icons/';
  if (!is_dir($icon_path)) {
    // No icon directory on disk; nothing to migrate.
    return true;
  }

  $iconFiles = array_merge(
    glob($icon_path . 'cat-*.gif') ?: [],
    glob($icon_path . 'cat-*.png') ?: []
  );

  foreach ($iconFiles as $iconFile) {
    if (!preg_match('/cat-(\d+)\.(gif|png)/', $iconFile, $matches)) {
      continue;
    }
    $catId = $matches[1];
    $fileType = $matches[2];

    $iconData = @file_get_contents($iconFile);
    if ($iconData === false) {
      error_log("do_v1_9_11_updates: unable to read $iconFile, skipping");
      continue;
    }

    $iconMime = 'image/' . $fileType;

    $res = dbi_execute(
      'UPDATE webcal_categories SET cat_icon_mime = ? WHERE cat_id = ?',
      [$iconMime, $catId]
    );
    if (!$res) {
      error_log("do_v1_9_11_updates: update cat_icon_mime failed for cat $catId: " . dbi_error());
      continue;
    }

    if (!dbi_update_blob(
      'webcal_categories',
      'cat_icon_blob',
      "cat_id = $catId",
      $iconData
    )) {
      error_log("do_v1_9_11_updates: update cat_icon_blob failed for cat $catId: " . dbi_error());
      continue;
    }

    // Delete the source file so we don't repeat on re-run.
    if (!@unlink($iconFile)) {
      error_log("do_v1_9_11_updates: unable to delete $iconFile after migration");
    }
  }

  return true;
}
