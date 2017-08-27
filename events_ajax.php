<?php
/**
 * Description
 *   Handler for AJAX requests for viewing events in combo.php,
 *   which provides views for day, week, month, year and agenda and
 *   a view of the task list.
 *
 *   We use JSON for some of the data we send back to the AJAX request.
 *   Because JSON support was not built-in to PHP until 5.2, we have our
 *   own implmentation in includes/JSON.php.
 *
 *   Most of the event handling is identical to the non-AJAX PHP pages except
 *   that we store the local user's version of each event's date and time
 *   in the Event and RptEvent classes.
 *
 * TODO: hide private events of other users.
 */
include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';
require_once 'includes/classes/Event.class';
require_once 'includes/classes/RptEvent.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';
require_valid_referring_url ();

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';
include 'includes/JSON.php';
include 'includes/ajax.php';

// Load Doc classes for attachments and comments
include 'includes/classes/Doc.class';
include 'includes/classes/DocList.class';
include 'includes/classes/AttachmentList.class';
include 'includes/classes/CommentList.class';

$WebCalendar->initializeSecondPhase();

load_global_settings();
load_user_preferences();
$WebCalendar->setLanguage();

load_user_layers();

$debug = getValue ( 'debug' );
$debug = ! empty ( $debug );
$action = getValue ( 'action' );
if ( empty ( $action ) )
  $action = 'get';
$user    = getValue ( 'user', '[A-Za-z0-9_\.=@,\-]*', true );
if ( ! empty ( $user ) ) {
  // Make sure this user has permission to view the other user's calendar
  if ( ! access_user_calendar( 'view', $user ) ) {
     // Not allowed.
     $user = $login;
     ajax_send_error ( translate('Not authorized') );
     exit;
  }
}
if ( empty ( $user ) )
  $user = $login;
$get_unapproved = true;

$sendPlainText = false;
$format = getValue ( 'format' );
if ( ! empty ( $format ) &&
 ( $format == 'text' || $format == 'plain' ) );
$sendPlainText = true;

$startdate = getIntValue ( 'startdate' );
if ( empty ( $startdate ) )
  $startdate = date ( "Ym" ) . '01';
$startyear = substr ( $startdate, 0, 4 );
$startmonth = substr ( $startdate, 4, 2 );
$startday = substr ( $startdate, 6, 2 );
$startTime = mktime ( 3, 0, 0, $startmonth, $startday, $startyear );
$enddate = getIntValue ( 'enddate' );
if ( empty ( $enddate ) )
  $enddate = date ( "Ymd", mktime ( 3, 0, 0, $startmonth + 1,
    $startday, $startyear ) );
$endyear = substr ( $enddate, 0, 4 );
$endmonth = substr ( $enddate, 4, 2 );
$endday = substr ( $enddate, 6, 2 );
$endTime = mktime ( 3, 0, 0, $endmonth, $endday, $endyear );

$error = '';

$can_edit = false;
if ( $readonly == 'Y' || $is_nonuser ) {
  $can_edit = false;
} else if ( $is_admin ) {
  $can_edit = true;
} else if ( $login == '__public__' ) {
  // Is public allowed to add events?
  if ( $PUBLIC_ACCESS_CAN_ADD == 'Y' )
    $can_edit = true;
}
// Allow user access control to override permissions
if ( $can_edit && access_is_enabled () ) {
  if ( ! access_user_calendar ( 'edit', $user, $login ) )
    $can_edit = false;
}

if ( $action == 'get' ) {
  $dates = $eventCats = $ids = $tasks = [];
  /* Pre-Load the repeated events for quicker access */
  $wkstart = get_weekday_before ( $startyear, $startmonth );
  $startTime = $wkstart;
  if ( $debug )
    echo "startdate: $startdate <br />enddate: $enddate<br />startTime: $startTime<br />";
  $repeated_events = read_repeated_events ( $user, $startTime, $endTime );
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $user, $startTime, $endTime );
  if ( $DISPLAY_TASKS_IN_GRID == 'Y' )
    $tasks = read_tasks ( $user, $enddate );
  // Gather the category IDs for each
  for ( $i = 0; $i < count ( $events ); $i++ ) {
    $id = $events[$i]->getID();
    $ids[$id] = $id;
  }
  for ( $i = 0; $i < count ( $repeated_events ); $i++ ) {
    $id = $repeated_events[$i]->getID();
    $ids[$id] = $id;
  }
  for ( $i = 0; $i < count ( $tasks ); $i++ ) {
    $id = $tasks[$i]->getID();
    $ids[$id] = $id;
  }
  // Load all category IDs for the specified event IDs
  //echo "<pre>"; print_r ( $ids ); echo "</pre>";
  if ( ! empty ( $id ) )
    load_category_ids ( $ids );

  // TODO:  We need to be able to start a week on ANY day.
  $monthend = date ( 'Ymd',
    mktime ( 0, 0, 0, $startmonth + 1, 0, $startyear ) );
  for ( $i = $wkstart; date ( 'Ymd', $i ) <= $monthend; $i += 604800 ) {
    $tmp = $i + 172800; // 48 hours.
    for ( $j = 0; $j < 7; $j++ ) {
      // Add 12 hours just so we don't have DST problems.
      $date = $i + ( $j * 86400 + 43200 );
      $dateYmd = date ( 'Ymd', $date );
      $myEvents = get_entries ( $dateYmd, $get_unapproved );
      $myRepEvents = get_repeating_entries( $user, $dateYmd );
      $ev = combine_and_sort_events ( $myEvents, $myRepEvents );
      setLocalTimes ( $ev );
      setCategories ( $ev );
      //echo "<pre>"; print_r ( $ev ); echo "</pre>\n";
      $dates[$dateYmd] = $ev;
    }
  }
  $objects = ['dates' => $dates];
  if ( $debug ) {
    echo "<pre>"; print_r ( $objects ); echo "</pre>\n";
  }
  ajax_send_objects ( $objects, $sendPlainText );
} else if ( $action == 'gett' ) { // Get Tasks
  $eventCats = $ids = $tasks = [];
  $thisyear = date ( 'Y' );
  $thismonth = date ( 'm' );
  $task_list = query_events ( $user, false, '', '', true );

  foreach ( $task_list as $E ) {
    // Check UAC.
    $task_owner = $E->getLogin();
    if ( access_is_enabled() ) {
      $can_access = access_user_calendar ( 'view', $task_owner, '',
        $E->getCalType(), $E->getAccess() );
      if ( $can_access == 0 )
        continue;
    }
    $tasks[] = $E;
    $id = $E->getID();
    $ids[$id] = $id;
  }
  // TODO: include repeated tasks????
  // Load all category IDs for the specified event IDs
  //echo "<pre>"; print_r ( $ids ); echo "</pre>";
  if ( ! empty ( $id ) )
    load_category_ids ( $ids );
  setLocalTimes ( $tasks );
  setCategories ( $tasks );
  $objects = ['tasks' => $tasks];
  if ( $debug ) {
    echo "<h2>Return</h2><pre>"; print_r ( $objects ); echo "</pre>\n";
  }
  ajax_send_objects ( $objects, $sendPlainText );
} else if ( $action == 'eventinfo' ) {
  // TODO: enforce user access control here...
  $id = getIntValue ( 'id' );
  $res = dbi_execute ( 'SELECT cal_login, cal_status
  FROM webcal_entry_user
  WHERE cal_id = ?', [$id] );
  $attachments = $comments = $parts = [];
  if ( ! $res ) {
    $error = translate("Database error") . ': ' . dbi_error();
  } else {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $parts[] = ['login' => $row[0], 'status' => $row[1]];
    }
    dbi_free_result ( $res );
  }
  // Get list of attachments.
  if ( Doc::attachmentsEnabled() ) {
    $attList = new AttachmentList ( $id );
    for ( $i = 0; $i < $attList->getSize(); $i++ ) {
      $a = $attList->getDoc ( $i );
      // Set link target to '_blank' so that we don't lose our place.
      // If we go to another page, the back button will re-init the page
      // so the user loses his place.
      $attachments[] = [
        'summary' => $a->getSummary ( '_blank' ),
        'id' => $a->getId(),
        'owner' => $a->getLogin()];
    }
  }
  if ( Doc::commentsEnabled() ) {
    $comList = new CommentList ( $id );
    $comment_text = '';
    for ( $i = 0; $i < $comList->getSize(); $i++ ) {
      $cmt = $comList->getDoc ( $i );
      $comments[] = [
        'description' => htmlspecialchars ( $cmt->getDescription() ),
        'owner' => $cmt->getLogin(),
        'datetime' => date_to_str ( $cmt->getModDate(), '', false, true ) . ' '
          . display_time ( $cmt->getModTime(), 2 ),
        'text' => nl2br ( activate_urls (
           htmlspecialchars ( $cmt->getData() ) ) )];
    }
  }
  $objects = [
    'participants' => $parts,
    'comments' => $comments,
    'attachments' => $attachments];
  if ( empty ( $error ) ) {
    ajax_send_objects ( $objects, $sendPlainText );
  } else {
    ajax_send_error ( translate('Unknown error.') );
  }
} else if ( $action == 'addevent' ) {
  // This is a simple add event function. It will be added as
  // an untimed event, so we don't need to check for conflicts.
  if ( ! $can_edit ) {
    ajax_send_error ( translate('Not authorized') );
    exit;
  }
  $date = getPostValue ( 'date' );
  $cat_id = getPostValue ( 'category' );
  $name = getPostValue ( 'name' );
  $description = getPostValue ( 'description' );
  if ( $description == '' )
    $description = $name;
  $participants = getPostValue ( 'participants' );
  if ( empty ( $participants ) )
    $participants = $login;
  //$user = $login;
  // Get new ID
  $id = 1;
  $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
  if ( $row = dbi_fetch_row ( $res ) ) {
    $id = $row[0] + 1;
  }
  dbi_free_result ( $res );
  $mod_date = gmdate ( 'Ymd' );
  $mod_time = gmdate ( 'His' );
  $sql = 'INSERT INTO webcal_entry ( cal_id, cal_create_by, cal_date, ' .
    'cal_time, cal_mod_date, cal_mod_time, ' .
    'cal_duration, cal_priority, cal_access, cal_type, cal_name, ' .
    'cal_description ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
  $values = [$id, $login, $date, -1, $mod_date, $mod_time,
    0, 5, 'P', 'E', $name, $description];
  if ( ! dbi_execute ( $sql, $values ) ) {
    ajax_send_error ( translate('Database error') . ": " . dbi_error() );
    exit;
  }
  if ( $cat_id > 0 ) {
    $sql =
      'INSERT INTO webcal_entry_categories ( cal_id, cat_id, cat_owner ) ' .
      'VALUES ( ?, ?, ? )';
    $values = [$id, $cat_id, $user];
    if ( ! dbi_execute ( $sql, $values ) ) {
      ajax_send_error ( translate('Database error') . ": " . dbi_error() );
      exit;
    }
  }
  // Add to each participant
  $userList = explode ( ',', $participants );
  for ( $i = 0; $i < count ( $userList ); $i++ ) {
    $user = $userList[$i];
    $status = ( $user != $login &&
      boss_must_approve_event ( $login, $user ) &&
      $REQUIRE_APPROVALS == 'Y' &&
      ! $is_nonuser_admin ) ? 'W' : 'A';
    if ( ! dbi_execute ( 'INSERT INTO webcal_entry_user ( cal_id, cal_login,
        cal_status ) VALUES ( ?, ?, ? )',
        [$id, $user, $status] ) ) {
      ajax_send_error ( translate('Database error') . ": " . dbi_error() );
    }
    activity_log ( $id, $login, $user, LOG_CREATE, '' );
    // TODO: send email notification!
  }
  ajax_send_success();
} else if ( $action == 'addtask' ) {
  // This is a simple add task function. It will be added as
  // an untimed task, so we don't need to check for conflicts.
  if ( ! $can_edit ) {
    ajax_send_error ( translate('Not authorized') );
    exit;
  }
  $startdate = getPostValue ( 'startdate' );
  $duedate = getPostValue ( 'duedate' );
  $cat_id = getPostValue ( 'category' );
  $name = getPostValue ( 'name' );
  $description = getPostValue ( 'description' );
  if ( $description == '' )
    $description = $name;
  $user = $login;
  // Get new ID
  $id = 1;
  $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
  if ( $row = dbi_fetch_row ( $res ) ) {
    $id = $row[0] + 1;
  }
  dbi_free_result ( $res );
  $mod_date = gmdate ( 'Ymd' );
  $mod_time = gmdate ( 'His' );
  $sql = 'INSERT INTO webcal_entry ( cal_id, cal_create_by, cal_date, ' .
    'cal_time, cal_due_date, cal_due_time, cal_mod_date, cal_mod_time, ' .
    'cal_duration, cal_priority, cal_access, cal_type, cal_name, ' .
    'cal_description ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
  $values = [$id, $login, $startdate, -1, $duedate, -1,
    $mod_date, $mod_time, 0, 5, 'P', 'T', $name, $description];
  if ( ! dbi_execute ( $sql, $values ) ) {
    ajax_send_error ( translate('Database error') . ": " . dbi_error() );
    exit;
  }
  if ( $cat_id > 0 ) {
    $sql =
      'INSERT INTO webcal_entry_categories ( cal_id, cat_id, cat_owner ) ' .
      'VALUES ( ?, ?, ? )';
    $values = [$id, $cat_id, $user];
    if ( ! dbi_execute ( $sql, $values ) ) {
      ajax_send_error ( translate('Database error') . ": " . dbi_error() );
      exit;
    }
  }
  if ( ! dbi_execute ( 'INSERT INTO webcal_entry_user ( cal_id, cal_login,
      cal_status ) VALUES ( ?, ?, ? )',
      [$id, $user, 'A'] ) ) {
    ajax_send_error ( translate('Database error') . ": " . dbi_error() );
  }
  ajax_send_success();
  activity_log ( $id, $login, $user, LOG_CREATE_T, '' );
} else {
  ajax_send_error ( translate('Unknown error.') );
}

// For each event in our list, also set the local time for the current
// user. This way, the client-side javascript will not need to worry
// about converting times between timezones.
function setLocalTimes ( $eventList )
{
  for ( $i = 0; $i < count ( $eventList ); $i++ ) {
    $event = $eventList[$i];
    $d = date_to_str ( $event->getDate(), '__yyyy__,__n__,__dd__',
      false );
    $args = explode ( ',', $d );
    $localDate = sprintf ( "%04d%02d%02d", $args[0], $args[1], $args[2] );
    $event->setLocalDate ( $localDate );
    if ( $event->getTime() <= 0 ) {
      $event->setLocalTime ( $event->getTime() );
    } else {
      // Get time in local user time in HHMMSS format.
      $localTime = display_time ( $event->getDatetime(), 0, '', '24' );
      $localTime = str_replace ( ':', '', $localTime );
      $event->setLocalTime ( $localTime );
    }
  }
}

function setCategories ( $eventList )
{
  global $eventCats;

  for ( $i = 0; $i < count ( $eventList ); $i++ ) {
    $event = $eventList[$i];
    $id = $event->getID();
    if ( ! empty ( $eventCats[$id] ) ) {
      $event->setCategories ( $eventCats[$id] );
    }
  }
}

// Get all categories for each event.
function load_category_ids ( $ids )
{
  global $eventCats, $user, $debug;
  //$ids = array_unique ( sort ( $ids, SORT_NUMERIC ) );
  $idList = implode ( ",", $ids );
  if ( $debug )
    echo "load_category_ids: $idList <br />\n\n";
  $sql = 'SELECT cal_id, cat_id
  FROM webcal_entry_categories
  WHERE cal_id IN ( ' . $idList . ' )
    AND ( cat_owner = "' . $user . '"
      OR cat_owner IS NULL )
  ORDER BY cat_order';
  if ( $debug )
    echo "SQL: $sql <br />";
  $res = dbi_execute ( $sql, [] );
  $eventCats = [];
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $eventId = $row[0];
      $catId = $row[1];
      if ( ! empty ( $eventCats[$eventId] ) && is_array ( $eventCats[$eventId] ) ) {
        $eventCats[$eventId][] = $catId;
      } else {
        $eventCats[$eventId] = [$catId];
      }
    }
    dbi_free_result ( $res );
  } else {
    ajax_send_error ( translate('Database error') . ": " . dbi_error() );
    exit;
  }
  if ( $debug ) {
    echo "<pre>"; print_r ( $eventCats ); echo "</pre>";
  }
}

exit;
?>
