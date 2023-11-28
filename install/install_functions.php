<?php

/**
 * The file contains all the functions used in the installation script
 */
/**
 * Developer debug log (
 */
function X_do_debug($msg)
{
  // log to /tmp/webcal-debug.log
  // error_log ( date ( "Y-m-d H:i:s" ) . "> $msg\n",
  // 3, "d:\php\logs\debug.txt" );
}

function isEmptyDatabase()
{
  global $debugInstaller;
  try {
    // If we have 1 or more users in webcal_user, the db is not empty
    $res = dbi_execute('SELECT COUNT(*) FROM webcal_config', [], false, false);
    if ($res) {
      $row = dbi_fetch_row($res);
      dbi_free_result($res);
      return $row[0] == 0;
    }
  } catch (Exception $e) {
    if ($debugInstaller) {
      echo "Error: " . $e->getMessage() . "<br>";
    }
    // Error connecting to db
  }
  return true;
}

function getDbVersion()
{
  $dbVersion = 'Unknown';
  $sql = 'SELECT cal_value FROM webcal_config WHERE cal_setting = ?';
  $res = dbi_execute(
    $sql,
    ['WEBCAL_PROGRAM_VERSION'],
    false,
    false
  );
  if ($res) {
    $row = dbi_fetch_row($res);
    if ($row) {
      $dbVersion = $row[0];
    }
  }
  return $dbVersion;
}

/**
 * Change string to uppercase
 */
function make_uppercase()
{
  // Make sure all cal_setting are UPPERCASE.
  if (!dbi_execute('UPDATE webcal_config
    SET cal_setting = UPPER( cal_setting )'))
    echo str_replace(
      ['XXX', 'YYY'],
      ['webcal_config', dbi_error()],
      translate('Error updating table XXX')
    );

  if (!dbi_execute('UPDATE webcal_user_pref
    SET cal_setting = UPPER( cal_setting )'))
    echo str_replace(
      ['XXX', 'YYY'],
      ['webcal_user_pref', dbi_error()],
      translate('Error updating table XXX')
    );
}
/**
 * db_load_admin (needs description)
 */
function db_load_admin()
{
  $res = dbi_execute('SELECT cal_login FROM webcal_user
    WHERE cal_login = "admin"', [], false, false);
  $sql = 'INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname,
    cal_firstname, cal_is_admin ) VALUES ( \'admin\',
    \'21232f297a57a5a743894a0e4a801fc3\', \'ADMINISTRATOR\', \'DEFAULT\', \'Y\' )';
  // Preload access_function premissions.
  $sql2 = 'INSERT INTO webcal_access_function ( cal_login, cal_permissions )
    VALUES ( \'admin\', \'YYYYYYYYYYYYYYYYYYYYYYYYYYY\' )';
  if (!$res) {
    dbi_execute($sql);
    dbi_execute($sql2);
  } else { // Sqlite returns $res always.
    $row = dbi_fetch_row($res);
    if (!isset($row[0])) {
      dbi_execute($sql);
      dbi_execute($sql2);
    }
    dbi_free_result($res);
  }
}

/**
 * Update the version of WebCalendar in the database, which is stored in the
 * webcal_config table.
 */
function updateVersionInDatabase()
{
  global $PROGRAM_VERSION;
  dbi_execute(
    'UPDATE webcal_config SET cal_value = ? WHERE cal_setting = ?',
    ['WEBCAL_PROGRAM_VERSION', $PROGRAM_VERSION]
  );
}

/**
 * do_v11b_updates (needs description)
 */
function do_v11b_updates()
{
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
  // Change cal_days format to cal_cal_byday format.
  // Deprecate monthlyByDayR to simply monthlyByDay.
  dbi_execute('UPDATE webcal_entry_repeats SET cal_type = \'monthlyByDay\'
    WHERE cal_type = \'monthlyByDayR\'');
  $res = dbi_execute('SELECT cal_id, cal_days FROM webcal_entry_repeats ');
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      if (!empty($row[1]) && $row[1] != 'yyyyyyy' && $row[1] != 'nnnnnnn') {
        $byday = [];
        if (substr($row[1], 0, 1) == 'y')
          $byday[] = 'SU';

        if (substr($row[1], 1, 1) == 'y')
          $byday[] = 'MO';

        if (substr($row[1], 2, 1) == 'y')
          $byday[] = 'TU';

        if (substr($row[1], 3, 1) == 'y')
          $byday[] = 'WE';

        if (substr($row[1], 4, 1) == 'y')
          $byday[] = 'TH';

        if (substr($row[1], 5, 1) == 'y')
          $byday[] = 'FR';

        if (substr($row[1], 6, 1) == 'y')
          $byday[] = 'SA';

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
  // Update Priority to new values
  //Old High=3, Low = 1....New Highest =1 Lowest =9
  //We will leave 3 alone and change 1,2 to 7,5
  dbi_execute('UPDATE webcal_entry SET cal_priority = 7
    WHERE cal_priority = 1');
  dbi_execute('UPDATE webcal_entry SET cal_priority = 5
    WHERE cal_priority = 2');
}

/**
 * Convert site_extra reminders to webcal_reminders.
 */
function do_v11e_updates()
{
  $reminder_log_exists = false;
  $res = dbi_execute('SELECT cal_id, cal_data
    FROM webcal_site_extras WHERE cal_type = \'7\'');
  $done = [];
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      if (!empty($done[$row[0]]))
        // Already did this one;
        // must have had two site extras for reminder ignore the 2nd one.
        continue;

      $date = $last_sent = $offset = $times_sent = 0;
      if (strlen($row[1]) == 8) // cal_data is probably a date.
        $date = mktime(
          0,
          0,
          0,
          substr($row[1], 4, 2),
          substr($row[1], 6, 2),
          substr($row[1], 0, 4)
        );
      else
        $offset = $row[1];

      $res2 = dbi_execute(
        'SELECT cal_last_sent
        FROM webcal_reminder_log WHERE cal_id = ? AND cal_last_sent > 0',
        [$row[0]]
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
    // Remove entries from webcal_reminder_log.
    if ($reminder_log_exists == true) {
      dbi_execute('DELETE FROM webcal_reminder_log', [], false, false);
      dbi_execute('DROP TABLE webcal_reminder_log', [], false, false);
    }
  }
}

/**
 * Migrate category icons from the file system into the webcal_categories table as
 * part of the updates for v1.9.11.  Storing everything in the database will eventually
 * allow multiple instances of WebCalendar to run against the same database and make
 * database backups a complete site backup.
 */
function do_v1_9_11_updates()
{
  $icon_path =  __DIR__ . "/../wc-icons/";
  // Get all icon files from the wc-icons directory
  $iconFiles = glob($icon_path . 'cat-*.gif');
  $iconFilesPng = glob($icon_path . 'cat-*.png');
  $iconFiles = array_merge($iconFiles, $iconFilesPng);
  foreach ($iconFiles as $iconFile) {
    // Extract the category ID from the filename
    preg_match('/cat-(\d+)\.(gif|png)/', $iconFile, $matches);
    $catId = $matches[1];
    $fileType = $matches[2];
    $iconData = '';
    $fd = @fopen($iconFile, 'r');
    if (!$fd)
      die_miserable_death("Error reading temp file: $iconFile");
    while (!feof($fd)) {
      $iconData .= fgets($fd, 4096);
    }
    fclose($fd);
    // Get MIME type of the icon
    $iconMime = 'image/' . $fileType;
    // Update the database
    $res = dbi_execute(
      'UPDATE webcal_categories SET cat_icon_mime = ? WHERE cat_id = ?',
      [$iconMime, $catId]
    );
    if (!$res) {
      echo "Failed to update icon for category ID $catId: " . dbi_error();
      continue;
    } else {
      if (!dbi_update_blob(
        'webcal_categories',
        'cat_icon_blob',
        "cat_id = $catId",
        $iconData
      )) {
        echo "Failed to update icon for category ID $catId: " . dbi_error();
      } else {
        // Encode the binary data to Base64.
        $base64Data = base64_encode($iconData);
        // Create a data URL.
        $dataUrl = 'data:image/png;base64,' . $base64Data;
        //echo '<img src="' . $dataUrl . '" alt="Embedded Image"> <br>';
        //echo "cat $catId done <br>\n";
        // Delete the files so we don't repeat this later
        if (!unlink($iconFile)) {
          echo "Failed to delete icon file $iconFile";
        }
      }
    }
  }
}

/* Functions moved from index.php script */

/**
 * Retrieves the value of a specified PHP configuration directive (from php.ini).
 *
 * This function will return either 'ON' or 'OFF' for boolean settings,
 * or check if a specific string value exists within the directive's value.
 *
 * @param string $val     The configuration directive (php.ini setting) to retrieve.
 * @param string|bool $string Optional. If specified, checks if this value exists within the directive's value.
 *                            If not specified or set to false, the function will return 'ON' or 'OFF' for the directive.
 *
 * @return string|bool    Returns 'ON' or 'OFF' for boolean settings. If $string is specified, it returns the string
 *                        if found within the directive's value, otherwise false.
 */
function get_php_setting($val, $string = false)
{
  $setting = ini_get($val);
  return ($string == false
    ? ($setting == '1' || $setting == 'ON' ? 'ON' : 'OFF')
    : // Test for $string in ini value.
    (in_array($string, explode(',', $setting)) ? $string : false));
}
/**
 * get_php_modules (needs description)
 */
function get_php_modules($val)
{
  return (function_exists($val) ? 'ON' : 'OFF');
}

/**
 * We will generate many errors while trying to test database.
 * Disable them temporarily as needed.
 */
function show_errors($error_val = 0)
{
  global $show_all_errors;

  if (empty($_SESSION['error_reporting']))
    $_SESSION['error_reporting'] = get_php_setting('error_reporting');

  ini_set('error_reporting', ($show_all_errors == true
    ? 64 : ($error_val ? $_SESSION['error_reporting'] : 64)));
}

/**
 * We will convert from Server based storage to GMT time.
 * Optionally, a tzoffset can be added to the URL and will
 * adjust all existing events by that amount. If cutoffdate is supplied,
 * only dates prior to that date are affected.
 */
function convert_server_to_GMT($offset = 0, $cutoffdate = '')
{
  // Default value.
  $error = translate('Conversion Successful');
  // Don't allow $offsets over 24.
  if (abs($offset) > 24)
    $offset = 0;
  // Do webcal_entry update.
  $res = dbi_execute('SELECT cal_date, cal_time, cal_id, cal_duration
    FROM webcal_entry');
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      $cal_date = $row[0];
      $cal_time = sprintf("%06d", $row[1]);
      $cal_id = $row[2];
      $cal_duration = $row[3];
      // Skip Untimed or All Day events.
      if (($cal_time == -1) || ($cal_time == 0 && $cal_duration == 1440))
        continue;
      else {
        $sy = substr($cal_date, 0, 4);
        $sm = substr($cal_date, 4, 2);
        $sd = substr($cal_date, 6, 2);
        $sh = substr($cal_time, 0, 2);
        $si = substr($cal_time, 2, 2);
        $ss = substr($cal_time, 4, 2);

        $new_datetime = (empty($offset)
          ? mktime($sh, $si, $ss, $sm, $sd, $sy)
          : gmmktime($sh + $offset, $si, $ss, $sm, $sd, $sy));

        $new_cal_date = gmdate('Ymd', $new_datetime);
        $new_cal_time = gmdate('His', $new_datetime);
        $cutoff = (!empty($cutoffdate) ? ' AND cal_date <= ?' : '');
        // Now update row with new data.
        if (!dbi_execute(
          'UPDATE webcal_entry SET cal_date = ?, cal_time = ?
          WHERE cal_id = ?' . $cutoff,
          [$new_cal_date, $new_cal_time, $cal_id, $cutoffdate]
        ))
          return str_replace(
            ['XXX', 'YYY'],
            ['webcal_entry', dbi_error()],
            translate('Error updating table XXX')
          );
      }
    }
    dbi_free_result($res);
  }

  // Do webcal_entry_logs update.
  $res = dbi_execute('SELECT cal_date, cal_time, cal_log_id
    FROM webcal_entry_log');
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      $cal_date = $row[0];
      $cal_time = sprintf("%06d", $row[1]);
      $cal_log_id = $row[2];
      $sy = substr($cal_date, 0, 4);
      $sm = substr($cal_date, 4, 2);
      $sd = substr($cal_date, 6, 2);
      $sh = substr($cal_time, 0, 2);
      $si = substr($cal_time, 2, 2);
      $ss = substr($cal_time, 4, 2);
      $new_datetime = mktime($sh, $si, $ss, $sm, $sd, $sy);
      $new_cal_date = gmdate('Ymd', $new_datetime);
      $new_cal_time = gmdate('His', $new_datetime);
      // Now update row with new data
      if (!dbi_execute(
        'UPDATE webcal_entry_log
        SET cal_date = ?, cal_time = ? WHERE cal_log_id = ?',
        [$new_cal_date, $new_cal_time, $cal_log_id]
      ))
        return str_replace(
          ['XXX', 'YYY'],
          ['webcal_entry_log', dbi_error()],
          translate('Error updating table XXX')
        );
    }
    dbi_free_result($res);
  }
  // Update Conversion Flag in webcal_config.
  // Delete any existing entry.
  if (!dbi_execute('DELETE FROM webcal_config
    WHERE cal_setting = \'WEBCAL_TZ_CONVERSION\''))
    return str_replace(
      'XXX',
      dbi_error(),
      translate('Database error XXX.')
    );

  if (!dbi_execute('INSERT INTO webcal_config ( cal_setting, cal_value )
    VALUES ( \'WEBCAL_TZ_CONVERSION\', \'Y\' )'))
    return str_replace(
      'XXX',
      dbi_error(),
      translate('Database error XXX.')
    );

  return $error;
}

/**
 * Examine the database to determine what version of WebCalendar the database was last used with.
 * This involves trying various SQL to see what fails and what succeeds.
 */
function getDatabaseVersionFromSchema($silent = true)
{
  global $database_upgrade_matrix, $PROGRAM_VERSION, $settings, $show_all_errors;
  $dbVersion = null;
  $success = true;
  $silent = true;

  // Suppress errors based on $show_all_errors.
  if (!$show_all_errors)
    show_errors(false);
  // This data is read from file upgrade_matrix.php.
  for ($i = 0; $i < count($database_upgrade_matrix); $i++) {
    $sql = $database_upgrade_matrix[$i][0];
    if (!$silent) {
      echo "SQL: $sql<br>\n";
    }

    if (empty($sql)) {
      if ($success) {
        // We reached the end of database_upgrade_matrix[] with no errors, which
        // means the database is structurally up-to-date.
        $dbVersion = $PROGRAM_VERSION;
      }
    } else {
      try {
        $res = dbi_execute($sql, [], false, $show_all_errors);
      } catch (Exception $e) {
        // Suppress any exceptions; this is only used for testing what version
        // we are on, so when it fails we know it's before the version that SQL
        // could have worked on.
        $res = false;
        $success = false;
        if (!$silent) {
          echo "Failed at: $sql <br>";
        }
      }
      if ($res) {
        if (!$silent) {
          echo "Success on " . $database_upgrade_matrix[$i][2] . "<br>";
        }
        $dbVersion = $database_upgrade_matrix[$i][2];
        $res = null;
        // Clean up our test 
        $sql = $database_upgrade_matrix[$i][1];
        if ($sql != '')
          dbi_execute($sql, [], false, $show_all_errors);
      } else {
        if (!$silent) {
          echo "Failure on " . $database_upgrade_matrix[$i][2] . "<br>";
          echo "Error: " . dbi_error() . "<br>\n";
        }
        $success = false;
        //echo "Failure SQL: $sql<br>";
      }
    }
  }

  // We need to determine if this is a blank database.
  // This may be due to a manual table setup.
  $res = dbi_execute(
    'SELECT COUNT( cal_value ) FROM webcal_config',
    [],
    false,
    $show_all_errors
  );
  if ($res) {
    $row = dbi_fetch_row($res);
    if (isset($row[0]) && $row[0] == 0) {
      $_SESSION['blank_database'] = true;
    } else {
      // Make sure all existing values in config and pref tables are UPPERCASE.
      make_uppercase();

      // Clear db_cache. This will prevent looping when launching WebCalendar
      // if upgrading and WEBCAL_PROGRAM_VERSION is cached.
      if (!empty($settings['db_cachedir']))
        dbi_init_cache($settings['db_cachedir']);
      else
      if (!empty($settings['cachedir']))
        dbi_init_cache($settings['cachedir']);

      // Delete existing WEBCAL_PROGRAM_VERSION number.
      dbi_execute('DELETE FROM webcal_config
        WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'');
    }
    dbi_free_result($res);
    // Insert webcal_config values only if blank.
    db_load_config();
  }
  // Note: We don't do TZ conversion anymore since that changes was 15+ years ago.
  // Get existing application name.
  // Enable warnings.
  show_errors(true);
  if (!$silent) {
    echo "Db structure is version: " . $dbVersion . "<br>\n";
  }
  return $dbVersion;
}

/**
 * Extracts SQL statements from a specified file.
 *
 * This function reads the content of the provided SQL file, strips out
 * any comments (single line starting with # or --, and multiline enclosed
 * in /* and *\/), and then returns each SQL statement as an array.
 *
 * @param string $filename The path to the SQL file.
 *
 * @throws Exception If the specified file is not found.
 *
 * @return array An array of SQL statements.
 */
function extractSqlCommandsFromFile($filename)
{
  // Check if file exists
  if (!file_exists($filename)) {
    throw new Exception("File not found: $filename");
  }

  // Read the file contents
  $content = file_get_contents($filename);

  // Strip out all comments
  $contentWithoutComments = preg_replace('/(--[^\r\n]*)|(\#[^\r\n]*)|\/\*.*?\*\//s', '', $content);

  // Split the content into individual SQL statements
  return array_filter(array_map('trim', explode(";\n", $contentWithoutComments)));
}

/**
 * Executes SQL statements from a specified file.
 *
 * This function reads the content of the provided SQL file using 
 * extractSqlCommandsFromFile() and then executes each SQL statement.
 *
 * @param string $filename The path to the SQL file.
 *
 * @throws Exception If there are issues executing the SQL or if the file is not found.
 *
 * @return void
 */
function executeSqlFromFile($filename)
{
  $sqlStatements = extractSqlCommandsFromFile($filename);

  foreach ($sqlStatements as $statement) {
    if (!empty($statement)) {
      // Assuming dbi_execute() is a function that takes a SQL statement and executes it
      echo "Statement: $statement <br>";
      dbi_execute($statement);
    }
  }
}

function getSqlFile($dbType, $isUpgrade = false)
{
  $file_base = __DIR__ . '/sql/' . ($isUpgrade ? 'upgrade' : 'tables');
  $install_filename = $file_base . '-';
  switch ($dbType) {
    case 'ibase':
    case 'mssql':
    case 'oracle':
      $install_filename .= $dbType . '.sql';
      break;
    case 'ibm_db2':
      $install_filename .= 'db2.sql';
      break;
      // Need to add more form fields to capture odbc db type...
    case 'odbc':
      // Not yet supported in installer :-(
      $install_filename .= $_SESSION['odbc_db'] . '.sql';
      break;
    case 'postgresql':
      $install_filename .= 'postgres.sql';
      break;
    case 'sqlite3':
      require_once 'sql/tables-sqlite3.php';
      populate_sqlite_db($real_db, $c);
      $install_filename = '';
      break;
    default:
      $install_filename .= 'mysql.sql';
  }
  return $install_filename;
}

function removeWhitespaceOnlyLines($inputStr)
{
  // Split the string by newlines
  $lines = explode("\n", $inputStr);

  // Filter out lines that only contain whitespace
  $filteredLines = array_filter($lines, function ($line) {
    return trim($line) !== '';
  });

  // Join the lines back together
  return implode("\n", $filteredLines);
}
