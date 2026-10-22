<?php
/**
 * Description:
 *  Export calendar data as a SQL file for import into the WebCalendar
 *  WordPress plugin (https://wordpress.org/plugins/agenticdaisy-calendar/).
 *
 *  The download is a plain SQL file containing INSERT statements for the
 *  tables the WordPress plugin's migration wizard understands, plus a
 *  metadata comment header (WebCalendar version, server timezone, charset)
 *  the wizard uses to convert values correctly.
 *
 *  Deliberately NOT exported: password hashes (cal_passwd), API tokens
 *  (cal_api_token), binary category icons, groups, views, reports, access
 *  control rows and attachments. The WordPress plugin maps users to
 *  WordPress accounts and sends password-reset emails instead.
 *
 * Security:
 *  User must be an admin user.
 */
require_once 'includes/init.php';

if ( ! $is_admin )
  die_miserable_death ( print_not_auth() );

/* Tables and columns understood by the WordPress migration wizard.
 * Column lists are fixed to the 1.9.x schema on purpose: they double as a
 * whitelist so secrets and blobs can never leak into the export. */
$wpx_tables = [
  'webcal_user' => ['cal_login', 'cal_lastname', 'cal_firstname',
    'cal_is_admin', 'cal_email', 'cal_enabled'],
  'webcal_entry' => ['cal_id', 'cal_group_id', 'cal_ext_for_id',
    'cal_create_by', 'cal_date', 'cal_time', 'cal_mod_date', 'cal_mod_time',
    'cal_duration', 'cal_due_date', 'cal_due_time', 'cal_priority',
    'cal_type', 'cal_access', 'cal_name', 'cal_location', 'cal_url',
    'cal_completed', 'cal_description'],
  'webcal_entry_repeats' => ['cal_id', 'cal_type', 'cal_end', 'cal_endtime',
    'cal_frequency', 'cal_days', 'cal_bymonth', 'cal_bymonthday',
    'cal_byday', 'cal_bysetpos', 'cal_byweekno', 'cal_byyearday',
    'cal_wkst', 'cal_count'],
  'webcal_entry_repeats_not' => ['cal_id', 'cal_date', 'cal_exdate'],
  'webcal_entry_user' => ['cal_id', 'cal_login', 'cal_status',
    'cal_category', 'cal_percent'],
  'webcal_entry_categories' => ['cal_id', 'cat_id', 'cat_order',
    'cat_owner'],
  'webcal_categories' => ['cat_id', 'cat_owner', 'cat_name', 'cat_color',
    'cat_status'],
  'webcal_user_layers' => ['cal_layerid', 'cal_login', 'cal_layeruser',
    'cal_color', 'cal_dups'],
  'webcal_nonuser_cals' => ['cal_login', 'cal_lastname', 'cal_firstname',
    'cal_admin', 'cal_is_public', 'cal_url'],
  'webcal_site_extras' => ['cal_id', 'cal_name', 'cal_type', 'cal_date',
    'cal_remind', 'cal_data'],
  'webcal_user_pref' => ['cal_login', 'cal_setting', 'cal_value'],
  'webcal_reminders' => ['cal_id', 'cal_date', 'cal_offset', 'cal_related',
    'cal_before', 'cal_last_sent', 'cal_repeats', 'cal_duration',
    'cal_times_sent', 'cal_action'],
  'webcal_config' => ['cal_setting', 'cal_value'],
];

/* Standard MySQL string-literal escaping.  dbi_escape_string() is not used
 * here because it calls stripslashes() first, which would corrupt
 * legitimate backslashes in event text. */
function wpx_escape ( $value ) {
  return str_replace (
    ["\\", "\0", "'", "\"", "\n", "\r", "\x1a"],
    ["\\\\", "\\0", "\\'", "\\\"", "\\n", "\\r", "\\Z"], $value );
}

function wpx_row_count ( $table ) {
  $res = dbi_execute ( 'SELECT COUNT(*) FROM ' . $table );
  $row = $res ? dbi_fetch_row ( $res ) : false;
  return $row ? intval ( $row[0] ) : 0;
}

function wpx_connection_charset () {
  global $db_connection_info;
  if ( $GLOBALS['db_type'] == 'mysqli'
      && ! empty ( $db_connection_info['connection'] )
      && method_exists ( $db_connection_info['connection'],
        'character_set_name' ) )
    return $db_connection_info['connection']->character_set_name();

  return 'unknown';
}

function wpx_server_timezone () {
  $res = dbi_execute (
    'SELECT cal_value FROM webcal_config WHERE cal_setting = ?',
    ['TIMEZONE'] );
  $row = $res ? dbi_fetch_row ( $res ) : false;
  return ( $row && ! empty ( $row[0] ) )
    ? $row[0] : date_default_timezone_get();
}

if ( getPostValue ( 'wpexport' ) == '1' ) {
  global $PROGRAM_VERSION;

  $include_log = ( getPostValue ( 'include_log' ) == '1' );
  if ( $include_log )
    $wpx_tables['webcal_entry_log'] = ['cal_log_id', 'cal_entry_id',
      'cal_login', 'cal_user_cal', 'cal_type', 'cal_date', 'cal_time',
      'cal_text'];

  $host = preg_replace ( '/[^A-Za-z0-9.-]/', '',
    empty ( $_SERVER['SERVER_NAME'] ) ? 'webcalendar'
      : $_SERVER['SERVER_NAME'] );
  $filename = 'webcalendar-export-' . $host . '-' . gmdate ( 'Ymd' ) . '.sql';

  header ( 'Content-Type: application/sql; charset=UTF-8' );
  header ( 'Content-Disposition: attachment; filename="' . $filename . '"' );
  header ( 'Pragma: private' );
  header ( 'Cache-control: private, must-revalidate' );

  echo '-- webcalendar-export:'
    . ' version=' . $PROGRAM_VERSION
    . ' host=' . $host
    . ' server_tz=' . wpx_server_timezone()
    . ' charset=' . wpx_connection_charset()
    . ' generated=' . gmdate ( 'Y-m-d\TH:i:s\Z' ) . "\n"
    . '-- Exported for the WebCalendar WordPress plugin migration wizard.'
    . "\n"
    . '-- Do not edit. Upload this file on the plugin\'s Import page.'
    . "\n\n";

  /* Without SET NAMES a client restoring this file falls back to its own
   * default charset and 4-byte UTF-8 (emoji) inserts fail. */
  $charset = wpx_connection_charset();
  if ( $charset != 'unknown' )
    echo 'SET NAMES ' . $charset . ";\n\n";

  $batch_size = 100;

  foreach ( $wpx_tables as $table => $columns ) {
    $count = wpx_row_count ( $table );
    echo '-- table: ' . $table . ' (' . $count . " rows)\n";

    if ( $count == 0 ) {
      echo "\n";
      continue;
    }

    $res = dbi_execute ( 'SELECT ' . implode ( ', ', $columns )
      . ' FROM ' . $table );
    $insert_prefix = 'INSERT INTO ' . $table
      . ' ( ' . implode ( ', ', $columns ) . " ) VALUES\n";
    $batch = [];

    while ( $row = dbi_fetch_row ( $res ) ) {
      $values = [];
      foreach ( $row as $value )
        $values[] = ( $value === null
          ? 'NULL' : "'" . wpx_escape ( $value ) . "'" );

      $batch[] = '( ' . implode ( ', ', $values ) . ' )';

      if ( count ( $batch ) >= $batch_size ) {
        echo $insert_prefix . implode ( ",\n", $batch ) . ";\n";
        $batch = [];
        flush();
      }
    }
    if ( count ( $batch ) > 0 )
      echo $insert_prefix . implode ( ",\n", $batch ) . ";\n";

    echo "\n";
    flush();
  }

  echo '-- end of webcalendar-export' . "\n";
  exit;
}

$exportStr = translate ( 'Export for WordPress' );

print_header();
echo '
    <h2>' . $exportStr . '</h2>
    <p>' . translate ( 'Download your calendar data as a SQL file that can be imported into the WebCalendar plugin for WordPress. On your WordPress site, go to WebCalendar, then Import, and upload the downloaded file. The import wizard will guide you through mapping users and calendars.' ) . '</p>
    <p>' . translate ( 'Passwords are never exported. The WordPress plugin creates or maps WordPress accounts and sends password-reset emails instead.' ) . '</p>
    <table class="report" summary="export row counts">
      <tr><th>' . translate ( 'Table' ) . '</th><th>'
  . translate ( 'Rows' ) . '</th></tr>';

foreach ( $wpx_tables as $table => $columns )
  echo '
      <tr><td>' . $table . '</td><td style="text-align:right">'
    . wpx_row_count ( $table ) . '</td></tr>';

echo '
    </table>
    <form action="export_wordpress.php" method="post">
      ' . csrf_form_key() . '
      <input type="hidden" name="wpexport" value="1">
      <p><label><input type="checkbox" name="include_log" value="1"> '
  . translate ( 'Include activity log' ) . '</label></p>
      <input type="submit" value="' . translate ( 'Export' ) . '">
    </form>
    <p><a href="https://wordpress.org/plugins/agenticdaisy-calendar/">'
  . translate ( 'About the WebCalendar plugin for WordPress' ) . '</a></p>'
  . print_trailer();
