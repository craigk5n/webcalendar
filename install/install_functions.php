<?php
/* $Id: install_functions.php,v 1.19.2.2 2007/08/06 02:28:33 cknudsen Exp $
 *
 * The file contains all the functions used in the installation script
 */
function do_debug ( $msg ) {
  // log to /tmp/webcal-debug.log
  // error_log ( date ( "Y-m-d H:i:s" ) . "> $msg\n",
  // 3, "d:\php\logs\debug.txt" );
}

function make_uppercase () {
  // Make sure all cal_settings are UPPERCASE.
  // translate ( 'Error updating webcal_config' )
  if ( ! dbi_execute ( 'UPDATE webcal_config
    SET cal_setting = UPPER( cal_setting )' ) )
    echo str_replace ( 'XXX', array ( 'webcal_config', dbi_error () ),
      translate ( 'Error updating table XXX' ) );

  if ( ! dbi_execute ( 'UPDATE webcal_user_pref
    SET cal_setting = UPPER( cal_setting )' ) )
    echo str_replace ( 'XXX', array ( 'webcal_user_pref', dbi_error () ),
      translate ( 'Error updating table XXX' ) );
}

function db_load_admin () {
  $res = dbi_execute ( 'SELECT cal_login FROM webcal_user
    WHERE cal_login = \'admin\'', array (), false, false );
  $sql = 'INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname,
    cal_firstname, cal_is_admin ) VALUES ( \'admin\',
    \'21232f297a57a5a743894a0e4a801fc3\', \'ADMINISTRATOR\', \'DEFAULT\', \'Y\' )';
  // Preload access_function premissions.
  $sql2 = 'INSERT INTO webcal_access_function ( cal_login, cal_permissions )
    VALUES ( \'admin\', \'YYYYYYYYYYYYYYYYYYYYYYYYYYY\' )';
  if ( ! $res ) {
    dbi_execute ( $sql );
    dbi_execute ( $sql2 );
  } else { // Sqlite returns $res always.
    $row = dbi_fetch_row ( $res );
    if ( ! isset ( $row[0] ) ) {
      dbi_execute ( $sql );
      dbi_execute ( $sql2 );
    }
    dbi_free_result ( $res );
  }
}

function db_check_admin () {
  $res = dbi_execute ( 'SELECT COUNT( cal_login ) FROM webcal_user
    WHERE cal_is_admin = \'Y\'', array (), false, false );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    dbi_free_result ( $res );
    return ( $row[0] > 0 );
  }
  return false;
}

function do_v11b_updates () {
  $res = dbi_execute ( 'SELECT weu.cal_id, cal_category, cat_owner
    FROM webcal_entry_user weu, webcal_categories wc
    WHERE weu.cal_category = wc.cat_id' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      dbi_execute ( 'INSERT INTO webcal_entry_categories ( cal_id, cat_id,'
         . ( empty ( $row[2] ) ? 'cat_order' : 'cat_owner' )
         . ' ) VALUES ( ?, ?, ? )', array ( $row[0], $row[1],
          ( empty ( $row[2] ) ? 99 : $row[2] ) ) );
    }
    dbi_free_result ( $res );
  }
  // Update LANGUAGE settings from Browser-Defined to none.
  dbi_execute ( 'UPDATE webcal_config SET cal_value = \'none\'
    WHERE cal_setting = \'LANGUAGE\' AND cal_value = \'Browser-defined\'' );

  dbi_execute ( 'UPDATE webcal_user_pref SET cal_value = \'none\'
    WHERE cal_setting = \'LANGUAGE\' AND cal_value = \'Browser-defined\'' );

  // Clear old category values.
  dbi_execute ( 'UPDATE webcal_entry_user SET cal_category = NULL' );
  // Mark existing exclusions as new exclusion type.
  dbi_execute ( 'UPDATE webcal_entry_repeats_not SET cal_exdate = 1' );
  // Change cal_days format to cal_cal_byday format.
  // Deprecate monthlyByDayR to simply monthlyByDay.
  dbi_execute ( 'UPDATE webcal_entry_repeats SET cal_type = \'monthlyByDay\'
    WHERE cal_type = \'monthlybByDayR\'' );
  $res = dbi_execute ( 'SELECT cal_id, cal_days FROM webcal_entry_repeats ' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $row[1] ) && $row[1] != 'yyyyyyy' && $row[1] != 'nnnnnnn' ) {
        $byday = array ();
        if ( substr ( $row[1], 0, 1 ) == 'y' )
          $byday[] = 'SU';

        if ( substr ( $row[1], 1, 1 ) == 'y' )
          $byday[] = 'MO';

        if ( substr ( $row[1], 2, 1 ) == 'y' )
          $byday[] = 'TU';

        if ( substr ( $row[1], 3, 1 ) == 'y' )
          $byday[] = 'WE';

        if ( substr ( $row[1], 4, 1 ) == 'y' )
          $byday[] = 'TH';

        if ( substr ( $row[1], 5, 1 ) == 'y' )
          $byday[] = 'FR';

        if ( substr ( $row[1], 6, 1 ) == 'y' )
          $byday[] = 'SA';

        $bydays = implode ( ',', $byday );
        dbi_execute ( 'UPDATE webcal_entry_repeats SET cal_byday = ?
          WHERE cal_id = ?', array ( $bydays, $row[0] ) );
      }
    }
    dbi_free_result ( $res );
  }
  // Repeat end dates are now exclusive so we need to add 1 day to each.
  $res = dbi_execute ( 'SELECT cal_end, cal_id FROM webcal_entry_repeats' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $row[0] ) ) {
        $dY = substr ( $row[0], 0, 4 );
        $dm = substr ( $row[0], 4, 2 );
        $dd = substr ( $row[0], 6, 2 );
        $new_date = date ( 'Ymd', gmmktime ( 0, 0, 0, $dm, $dd, $dY ) + 86400 );
        dbi_execute ( 'UPDATE webcal_entry_repeats SET cal_end = ?
          WHERE cal_id = ?', array ( $new_date, $row[1] ) );
      }
    }
    dbi_free_result ( $res );
  }
    // Update Priority to new values
    //Old High=3, Low = 1....New Highest =1 Lowest =9
    //We will leave 3 alone and change 1,2 to 7,5
  dbi_execute ( 'UPDATE webcal_entry SET cal_priority = 7
    WHERE cal_priority = 1' );
  dbi_execute ( 'UPDATE webcal_entry SET cal_priority = 5
    WHERE cal_priority = 2' );
}

/* Convert site_extra reminders to webcal_reminders. */
function do_v11e_updates () {
  $reminder_log_exists = false;
  $res = dbi_execute ( 'SELECT cal_id, cal_data
    FROM webcal_site_extras WHERE cal_type = \'7\'' );
  $done = array ();
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! empty ( $done[$row[0]] ) )
        // Already did this one;
        // must have had two site extras for reminder ignore the 2nd one.
        continue;

      $date = $last_sent = $offset = $times_sent = 0;
      if ( strlen ( $row[1] ) == 8 ) // cal_data is probably a date.
        $date = mktime ( 0, 0, 0, substr ( $row[1], 4, 2 ),
          substr ( $row[1], 6, 2 ), substr ( $row[1], 0, 4 ) );
      else
        $offset = $row[1];

      $res2 = dbi_execute ( 'SELECT cal_last_sent
        FROM webcal_reminder_log WHERE cal_id = ? AND cal_last_sent > 0',
        array ( $row[0] ) );
      if ( $res2 ) {
        $reminder_log_exists = true;
        $row2 = dbi_fetch_row ( $res2 );
        $times_sent = 1;
        $last_sent = ( ! empty ( $row2[0] ) ? $row2[0] : 0 );
        dbi_free_result ( $res2 );
      }
      dbi_execute ( 'INSERT INTO webcal_reminders ( cal_id, cal_date,
        cal_offset, cal_last_sent, cal_times_sent ) VALUES ( ?, ?, ?, ?, ? )',
        array ( $row[0], $date, $offset, $last_sent, $times_sent ) );
      $done[$row[0]] = true;
    }
    dbi_free_result ( $res );
    // Remove reminders from site_extras.
    dbi_execute ( 'DELETE FROM webcal_site_extras
      WHERE webcal_site_extras.cal_type = \'7\'' );
    // Remove entries from webcal_reminder_log.
    if ( $reminder_log_exists == true ) {
      dbi_execute ( 'DELETE FROM webcal_reminder_log', array (), false, false );
      dbi_execute ( 'DROP TABLE webcal_reminder_log', array (), false, false );
    }
  }
}

/* Functions moved from index.php script */

function get_php_setting ( $val, $string = false ) {
  $setting = ini_get ( $val );
  return ( $string == false
    ? ( $setting == '1' || $setting == 'ON' ? 'ON' : 'OFF' )
    : // Test for $string in ini value.
    ( in_array ( $string, explode ( ',', $setting ) ) ? $string : false ) );
}

function get_php_modules ( $val ) {
  return ( function_exists ( $val ) ? 'ON' : 'OFF' );
}

/* We will generate many errors while trying to test database.
 * Disable them temporarily as needed.
 */
function show_errors ( $error_val = 0 ) {
  global $show_all_errors;

  if ( empty ( $_SESSION['error_reporting'] ) )
    $_SESSION['error_reporting'] = get_php_setting ( 'error_reporting' );

  ini_set ( 'error_reporting', ( $show_all_errors == true
      ? 64 : ( $error_val ? $_SESSION['error_reporting'] : 64 ) ) );
}

/* We will convert from Server based storage to GMT time.
 * Optionally, a tzoffset can be added to the URL and will
 * adjust all existing events by that amount. If cutoffdate is supplied,
 * only dates prior to that date are affected.
 */
function convert_server_to_GMT ( $offset = 0, $cutoffdate = '' ) {
  // Default value.
  $error = translate ( 'Conversion Successful' );
  // Don't allow $offsets over 24.
  if ( abs ( $offset ) > 24 )
    $offset = 0;
  // Do webcal_entry update.
  $res = dbi_execute ( 'SELECT cal_date, cal_time, cal_id, cal_duration
    FROM webcal_entry' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $cal_date = $row[0];
      $cal_time = sprintf ( "%06d", $row[1] );
      $cal_id = $row[2];
      $cal_duration = $row[3];
      // Skip Untimed or All Day events.
      if ( ( $cal_time == -1 ) || ( $cal_time == 0 && $cal_duration == 1440 ) )
        continue;
      else {
        $sy = substr ( $cal_date, 0, 4 );
        $sm = substr ( $cal_date, 4, 2 );
        $sd = substr ( $cal_date, 6, 2 );
        $sh = substr ( $cal_time, 0, 2 );
        $si = substr ( $cal_time, 2, 2 );
        $ss = substr ( $cal_time, 4, 2 );

        $new_datetime = ( empty ( $offset )
          ? mktime ( $sh, $si, $ss, $sm, $sd, $sy )
          : gmmktime ( $sh + $offset, $si, $ss, $sm, $sd, $sy ) );

        $new_cal_date = gmdate ( 'Ymd', $new_datetime );
        $new_cal_time = gmdate ( 'His', $new_datetime );
        $cutoff = ( ! empty ( $cutoffdate ) ? ' AND cal_date <= ?' : '' );
        // Now update row with new data.
        if ( ! dbi_execute ( 'UPDATE webcal_entry SET cal_date = ?, cal_time = ?
          WHERE cal_id = ?' . $cutoff,
            array ( $new_cal_date, $new_cal_time, $cal_id, $cutoffdate ) ) )
          return str_replace ( 'XXX', array ( 'webcal_entry', dbi_error () ),
            translate ( 'Error updating table XXX' ) );
      }
    }
    dbi_free_result ( $res );
  }

  // Do webcal_entry_logs update.
  $res = dbi_execute ( 'SELECT cal_date, cal_time, cal_log_id
    FROM webcal_entry_log' );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $cal_date = $row[0];
      $cal_time = sprintf ( "%06d", $row[1] );
      $cal_log_id = $row[2];
      $sy = substr ( $cal_date, 0, 4 );
      $sm = substr ( $cal_date, 4, 2 );
      $sd = substr ( $cal_date, 6, 2 );
      $sh = substr ( $cal_time, 0, 2 );
      $si = substr ( $cal_time, 2, 2 );
      $ss = substr ( $cal_time, 4, 2 );
      $new_datetime = mktime ( $sh, $si, $ss, $sm, $sd, $sy );
      $new_cal_date = gmdate ( 'Ymd', $new_datetime );
      $new_cal_time = gmdate ( 'His', $new_datetime );
      // Now update row with new data
      if ( ! dbi_execute ( 'UPDATE webcal_entry_log
        SET cal_date = ?, cal_time = ? WHERE cal_log_id = ?',
          array ( $new_cal_date, $new_cal_time, $cal_log_id ) ) )
        return str_replace ( 'XXX', array ( 'webcal_entry_log', dbi_error () ),
          translate ( 'Error updating table XXX' ) );
    }
    dbi_free_result ( $res );
  }
  // Update Conversion Flag in webcal_config.
  // Delete any existing entry.
  if ( ! dbi_execute ( 'DELETE FROM webcal_config
    WHERE cal_setting = \'WEBCAL_TZ_CONVERSION\'' ) )
    return str_replace ( 'XXX', dbi_error (),
      translate ( 'Database error XXX.' ) );

  if ( ! dbi_execute ( 'INSERT INTO webcal_config ( cal_setting, cal_value )
    VALUES ( \'WEBCAL_TZ_CONVERSION\', \'Y\' )' ) )
    return str_replace ( 'XXX', dbi_error (),
      translate ( 'Database error XXX.' ) );

  return $error;
}

function get_installed_version ( $postinstall = false ) {
  global $database_upgrade_matrix, $PROGRAM_VERSION, $settings, $show_all_errors;

  // Set this as the default value.
  $_SESSION['application_name'] = 'Title';
  $_SESSION['blank_database'] = '';
  // We will append the db_type to come up te proper filename.
  $_SESSION['install_file'] = 'tables';
  $_SESSION['old_program_version'] = ( $postinstall
    ? $PROGRAM_VERSION : 'new_install' );

  // Suppress errors based on $show_all_errors.
  if ( ! $show_all_errors )
    show_errors ( false );
  // This data is read from file upgrade_matrix.php.
  for ( $i = 0, $dbCntStr = count ( $database_upgrade_matrix ); $i < $dbCntStr; $i++ ) {
    $sql = $database_upgrade_matrix[$i][0];

    if ( $sql != '' )
      $res = dbi_execute ( $sql, array (), false, $show_all_errors );
    if ( $res ) {
      $_SESSION['old_program_version'] = $database_upgrade_matrix[$i + 1][2];
      $_SESSION['install_file'] = $database_upgrade_matrix[$i + 1][3];
      $res = '';
      $sql = $database_upgrade_matrix[$i][1];
      if ( $sql != '' )
        dbi_execute ( $sql, array (), false, $show_all_errors );
    }
  }
  $response_msg = ( $_SESSION['old_program_version'] == 'pre-v0.9.07'
    ? translate ( 'Perl script required' )
    : translate ( 'previous version requires updating several tables' ) );
  // v1.1 and after will have an entry in webcal_config to make this easier
  // $res = dbi_execute ( 'SELECT cal_value FROM webcal_config
  //   WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'', array (), false, false );
  // if ( $res ) {
  // $row = dbi_fetch_row ( $res );
  // if ( ! empty ( $row[0] ) ) {
  // $_SESSION['old_program_version'] = $row[0];
  // $_SESSION['install_file'] = 'upgrade_' . $row[0];
  // }
  // dbi_free_result ( $res );
  // }

  // We need to determine if this is a blank database.
  // This may be due to a manual table setup.
  $res = dbi_execute ( 'SELECT COUNT( cal_value ) FROM webcal_config',
    array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( isset ( $row[0] ) && $row[0] == 0 )
      $_SESSION['blank_database'] = true;
    else {
      // Make sure all existing values in config and pref tables are UPPERCASE.
      make_uppercase ();

      // Clear db_cache. This will prevent looping when launching WebCalendar
      // if upgrading and WEBCAL_PROGRAM_VERSION is cached.
      if ( ! empty ( $settings['db_cachedir'] ) )
        dbi_init_cache ( $settings['db_cachedir'] );
      else
      if ( ! empty ( $settings['cachedir'] ) )
        dbi_init_cache ( $settings['cachedir'] );

      // Delete existing WEBCAL_PROGRAM_VERSION number.
      dbi_execute ( 'DELETE FROM webcal_config
        WHERE cal_setting = \'WEBCAL_PROGRAM_VERSION\'' );
    }
    dbi_free_result ( $res );
    // Insert webcal_config values only if blank.
    db_load_config ();
    // Check if an Admin account exists.
    $_SESSION['admin_exists'] = db_check_admin ();
  }
  // Determine if old data has been converted to GMT.
  // This seems lke a good place to put this.
  $res = dbi_execute ( 'SELECT cal_value FROM webcal_config
    WHERE cal_setting = \'WEBCAL_TZ_CONVERSION\'',
    array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    dbi_free_result ( $res );
    // If not 'Y', prompt user to do conversion from server time to GMT time.
    if ( ! empty ( $row[0] ) )
      $_SESSION['tz_conversion'] = $row[0];
    else { // We'll test if any events even exist.
      $res = dbi_execute ( 'SELECT COUNT( cal_id ) FROM webcal_entry ',
        array (), false, $show_all_errors );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        dbi_free_result ( $res );
      }
      $_SESSION['tz_conversion'] = ( $row[0] > 0 ? 'NEEDED' : 'Y' );
    }
    dbi_free_result ( $res );
  }
  // Don't show TZ conversion if blank database.
  if ( $_SESSION['blank_database'] == true )
    $_SESSION['tz_conversion'] = 'Y';
  // Get existing server URL.
  // We could use the self-discvery value, but this may be a custom value.
  $res = dbi_execute ( 'SELECT cal_value FROM webcal_config
    WHERE cal_setting = \'SERVER_URL\'', array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty ( $row[0] ) && strlen ( $row[0] ) )
      $_SESSION['server_url'] = $row[0];

    dbi_free_result ( $res );
  }
  // Get existing application name.
  $res = dbi_execute ( 'SELECT cal_value FROM webcal_config
    WHERE cal_setting = \'APPLICATION_NAME\'',
    array (), false, $show_all_errors );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty ( $row[0] ) )
      $_SESSION['application_name'] = $row[0];

    dbi_free_result ( $res );
  }
  // Enable warnings.
  show_errors ( true );
} // end get_installed_version

function parse_sql ( $sql ) {
  $sql = trim ( $sql );
  $sql = trim ( $sql, "\r\n " );
  $ret = array ();

  $buffer_str = '';
  for( $i = 0; $i < strlen ( $sql ); $i++ ) {
    $buffer_str .= substr ( $sql, $i, 1 );
    if ( substr ( $sql, $i, 1 ) == ';' ) {
      $ret[] = $buffer_str;
      $buffer_str = '';
    }
  }
  return ( $ret );
}

function db_populate ( $install_filename, $display_sql ) {
  global $show_all_errors, $str_parsed_sql;

  if ( $install_filename == '' )
    return;

  $current_pointer = false;
  $full_sql = '';
  $magic = @get_magic_quotes_runtime ();
  @set_magic_quotes_runtime ( 0 );
  $fd = @fopen ( 'sql/' . $install_filename, 'r', true );

  // Discard everything up to the required point in the upgrade file.
  while ( ! feof ( $fd ) && empty ( $current_pointer ) ) {
    $data = trim ( fgets ( $fd, 4096 ), "\r\n " );
    if ( strpos ( strtoupper ( $data ),
          strtoupper ( $_SESSION['install_file'] ) ) ||
        substr ( $_SESSION['install_file'], 0, 6 ) == 'tables' )
      $current_pointer = true;
  }
  // We already have a $data item from above.
  if ( substr ( $data, 0, 2 ) == "/*" &&
      substr ( $_SESSION['install_file'], 0, 6 ) != 'tables' ) {
    // Do nothing...We skip over comments in upgrade files.
  } else
    $full_sql .= $data;

  // We need to strip out the comments from upgrade files.
  while ( ! feof ( $fd ) ) {
    $data = trim ( fgets ( $fd, 4096 ), "\r\n " );
    if ( substr ( $data, 0, 2 ) == '/*' &&
        substr ( $_SESSION['install_file'], 0, 6 ) != 'tables' ) {
      // Do nothing...We skip over comments in upgrade files.
    } else
      $full_sql .= $data;
  }

  @set_magic_quotes_runtime ( $magic );
  fclose ( $fd );
  $parsed_sql = parse_sql ( $full_sql );

  // String version of parsed_sql that is used if displaying SQL only.
  $str_parsed_sql = '';
  for ( $i = 0, $sqlCntStr = count ( $parsed_sql ); $i < $sqlCntStr; $i++ ) {
    if ( empty ( $display_sql ) ) {
      if ( $show_all_errors == true )
        echo $parsed_sql[$i] . '<br />';

      dbi_execute ( $parsed_sql[$i], array (), false, $show_all_errors );
    } else
      $str_parsed_sql .= $parsed_sql[$i] . "\n\n";
  }

  // Enable warnings.
  show_errors ( true );
} // end db_populate

?>
