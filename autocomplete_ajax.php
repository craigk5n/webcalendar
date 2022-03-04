<?php
/**
 * Description
 *	Handler for AJAX requests for search suggestion (aka autocomplete)
 *	Note that we only search the current user's events for all time
 *	(no date range, category, etc.).  This this is a simple search
 *	(not advanced).
 *
 * Autocomplete URL: https://github.com/xcash/bootstrap-autocomplete
 * Autocomplete Doc URL: https://bootstrap-autocomplete.readthedocs.io/en/latest/
 *
 * Must return data in the following format:
 *	{
 *    "error":0,
 *    "status":"OK",
 *    "message":"",
 *    "matches":["Unnamed Event","TEst","test event \"2\" !","test recur","test recur OVERRIDE","Test Event"]
 *  }
 */
include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.php';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';
include 'includes/ajax.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();
load_user_preferences();
$WebCalendar->setLanguage();

load_user_layers();

$action = getValue('action');
if (empty($action))
  $action = 'search';
$query = getValue('q');
if (empty($query))
  $query = getValue('query');

$sendPlainText = false;
$format = getValue('format');
if (
  !empty($format) &&
  ($format == 'text' || $format == 'plain')
);
$sendPlainText = true;
if ($sendPlainText) {
  Header('Content-Type: text/plain');
} else {
  Header('Content-Type: text/json');
}

$error = '';
$matches = 0;

if ($action == 'search') {
  // remove double quotes
  $query = str_replace('"', '', $query);
  $words = explode(' ', $query);
  
  $eventTitles = $ret = [];
  $word_cnt = count ( $words );
  for ($i = 0; $i < $word_cnt; $i++) {
    $sql_params = [];
    // Note: we only search approved/waiting events (not deleted).
    $sql = 'SELECT we.cal_id, we.cal_name, we.cal_date, weu.cal_login '
    . (empty($extra_filter) ? '' : ', wse.cal_data ')
    . 'FROM webcal_entry_user weu LEFT JOIN  webcal_entry we '
    . (empty($cat_filter) ? '' : ', webcal_entry_categories wec ')
    . (empty($extra_filter) ? '' : ', webcal_site_extras wse ')
    . 'ON weu.cal_id = we.cal_id WHERE weu.cal_status in ( \'A\',\'W\' )
       AND weu.cal_login IN ( ?';
    $sql_params[] = $login;
    $sql .= ' ) ';

    // We get an error using mssql trying to read text column as varchar.
    // This workaround seems to fix it up ROJ
    // but, will only search the first 1kb of the description.
    $sql .= 'AND ( UPPER( we.cal_name ) LIKE UPPER( ? ) OR UPPER( '
    . (strcmp($GLOBALS['db_type'], 'mssql') == 0
      ? 'CAST ( we.cal_description AS varchar (1024) )'
      : 'we.cal_description')
    . ' ) LIKE UPPER( ? ) ) ';
    $sql_params[] = '%' . $words[$i] . '%';
    $sql_params[] = '%' . $words[$i] . '%';
    //echo "SQL:\n$sql\n\n";
    $res = dbi_execute ( $sql . ' ORDER BY we.cal_date ' .
       ', we.cal_name', $sql_params );
    if ($res) {
      while ($row = dbi_fetch_row($res)) {
        $utitle = str_replace(' ', '', strtoupper($row[1]));
        if (empty($eventTitles[$utitle])) {
          $ret[$matches]['id'] = $row[0];
          $ret[$matches]['name'] = $row[1];
          $ret[$matches]['text'] = $row[1] . ' (' . date_to_str($row[2]) . ')';
          $eventTitles[$utitle] = 1;
          $matches++;
          //echo "utitle = \"$utitle\" \n";
        }
      }
    }
    dbi_free_result ( $res );
  }

  $data = $sug = [];
  for ($i = 0; $i < count ($ret); $i++) {
    $sug[$i] = $ret[$i]['name'];
  }
  for ($i = 0; $i < count ($ret); $i++) {
    $data[$i] = $ret[$i]['text'];
  }
  ajax_send_object('matches', $sug, $sendPlainText);
} else {
  ajax_send_error(translate("Error"));
}

exit;
