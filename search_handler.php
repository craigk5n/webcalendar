<?php
/**
 * This page produces search results.
 *
 * "Advanced Search" adds the ability to search other users' calendars.
 * We do a number of security checks to make sure this is allowed.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */
include_once 'includes/init.php';
require_valid_referring_url ();

$error = '';

// Disable if public access and OVERRIDE_PUBLIC in use
if ( $login == '__public__' && ! empty ( $OVERRIDE_PUBLIC ) &&
  $OVERRIDE_PUBLIC == 'Y' ) {
  print_header();
  echo print_not_auth();
  print_trailer();
  exit;
}

$keywords = getValue ( 'keywords' );
$advanced = getValue ( 'advanced' );

if ( strlen ( $keywords ) == 0 )
  $error = translate( 'You must enter one or more search keywords.' );

$matches = 0;
// Determine if this user is allowed to search the calendar of other users
$search_others = false; // show "Advanced Search"
if ( $single_user == 'Y' )
  $search_others = false;

if ( $is_admin )
  $search_others = true;
else
if ( access_is_enabled() )
  $search_others = access_can_access_function ( ACCESS_ADVANCED_SEARCH );
else
if ( $login != '__public__' && ! empty ( $ALLOW_VIEW_OTHER ) &&
    $ALLOW_VIEW_OTHER == 'Y' )
  $search_others = true;
else
if ( $login == '__public__' && ! empty ( $PUBLIC_ACCESS_OTHERS ) &&
    $PUBLIC_ACCESS_OTHERS == 'Y' )
  $search_others = true;

$users = getValue ( 'users' );
if ( empty ( $users ) || empty ( $users[0] ) )
  $search_others = false;
// Security precaution -- make sure users listed in participants list
// was not hacked up to include users that they don't really have access to.
if ( $search_others ) {
  // If user can only see users in his group, then remove users not in his group.
  if ( ! empty ( $USER_SEES_ONLY_HIS_GROUPS ) &&
      $USER_SEES_ONLY_HIS_GROUPS == 'Y' && ! empty ( $GROUPS_ENABLED ) &&
      $GROUPS_ENABLED == 'Y' ) {
    $myusers = get_my_users ( '', 'view' );
    $userlookup = [];
    for ( $i = 0, $cnt = count ( $myusers ); $i < $cnt; $i++ ) {
      $userlookup[$myusers[$i]['cal_login']] = 1;
    }
    $newlist = [];
    $cnt = count ( $users );
    for ( $i = 0; $i < $cnt; $i++ ) {
      if ( ! empty ( $userlookup[$users[$i]] ) )
        $newlist[] = $users[$i];
    }
    $users = $newlist;
  }
  // Now, use access control to remove more users :-)
  if ( access_is_enabled() && ! $is_admin ) {
    $newlist = [];
    for ( $i = 0; $i < count ( $users ); $i++ ) {
      if ( access_user_calendar ( 'view', $users[$i] ) ) {
        $newlist[] = $users[$i];
        //echo "can access $users[$i] <br />";
      } else {
        //echo "cannot access $users[$i] <br />";
      }
    }
    $users = $newlist;
  }
}

if ( empty ( $users ) || empty ( $users[0] ) )
  $search_others = false;

//Get advanced filters
$cat_filter = getPostValue ( 'cat_filter' );
$extra_filter = getPostValue ( 'extra_filter' );
$date_filter = getPostValue ( 'date_filter' );

$from_YMD = getPostValue ( 'from__YMD' );
if ( empty ( $from_YMD ) ) {
  $start_day = $start_month = $start_year = '';
} else {
  $start_year = intval ( substr ( $from_YMD, 0, 4 ) );
  $start_month = intval ( substr ( $from_YMD, 4, 2 ) );
  $start_day = intval ( substr ( $from_YMD, 6, 2 ) );
  if ( $start_year < 1970 )
    $start_year = 1970;
}

$end_YMD = getPostValue ( 'until__YMD' );
if ( empty ( $end_YMD ) ) {
  $end_day = $end_month = $end_year = '';
} else {
  $end_year = intval ( substr ( $end_YMD, 0, 4 ) );
  $end_month = intval ( substr ( $end_YMD, 4, 2 ) );
  $end_day = intval ( substr ( $end_YMD, 6, 2 ) );
  if ( $end_year < 1970 )
    $end_year = 1970;
}

if ( $date_filter == 3 ) {//Use Date Range
  $startDate = gmdate( 'Ymd', gmmktime( 0, 0, 0,
    $start_month, $start_day, $start_year ) );
  $endDate = gmdate( 'Ymd', gmmktime( 23, 59, 59,
    $end_month, $end_day, $end_year ) );
}

print_header();
echo '
    <h2>' . translate ( 'Search Results' ) . '</h2>';

if ( ! empty ( $error ) )
  echo print_error ( $error );
else {
// *** "Phrase" feature by Steve Weyer saweyer@comcast.net 4-May-2005
// check if keywords is surrounded by quotes
// an alternative might be to add a checkbox/list on search.php
// to indicate Phrase or other mode via an arg
// if a phrase, use (after removing quotes) rather than split into words
// also add query (keywords) to "match results" heading near end
// e.g., search_handler.php?keywords=%22Location:%20Arts%20and%20Crafts%22

// begin Phrase modification
$klen = strlen ( $keywords );
$phrasedelim = "\\\"";
$plen = strlen ( $phrasedelim );

if ( substr ( $keywords, 0, $plen ) == $phrasedelim &&
    substr ( $keywords, $klen - $plen ) == $phrasedelim ) {
  $phrase = substr ( $keywords, $plen, $klen - ( $plen * 2 ) );
  $words = [$phrase];
} else
  // original (default) behavior
  $words = explode ( ' ', $keywords );
// end Phrase modification
  $order = 'DESC';
  $word_cnt = count ( $words );
  for ( $i = 0; $i < $word_cnt; $i++ ) {
    $sql_params = [];
    // Note: we only search approved/waiting events (not deleted).
    $sql = 'SELECT we.cal_id, we.cal_name, we.cal_date, weu.cal_login '
      . ( empty( $extra_filter ) ? '' : ', wse.cal_data ' )
      . 'FROM webcal_entry_user weu LEFT JOIN  webcal_entry we '
      . ( empty( $cat_filter ) ? '' : ', webcal_entry_categories wec ' )
      . ( empty( $extra_filter ) ? '' : ', webcal_site_extras wse ' )
      . 'ON weu.cal_id = we.cal_id WHERE weu.cal_status in ( \'A\',\'W\' )
       AND weu.cal_login IN ( ?';
    if ( $search_others ) {
      if ( empty ( $users[0] ) )
        $sql_params[0] = $users[0] = $login;

      $user_cnt = count ( $users );
      for ( $j = 0; $j < $user_cnt; $j++ ) {
        if ( $j > 0 ) $sql .= ', ?';
        $sql_params[] = $users[$j];
      }
    } else
      $sql_params[] = $login;

    $sql .= ' ) ';
    if ( $search_others ) {
      // Don't search confidential entries of other users.
      $sql .= 'AND ( weu.cal_login = ?
        OR ( weu.cal_login != ? AND we.cal_access = \'P\' ) ) ';
      $sql_params[] = $login;
      $sql_params[] = $login;
    }
    // We get an error using mssql trying to read text column as varchar.
    // This workaround seems to fix it up ROJ
    // but, will only search the first 1kb of the description.
    $sql .= 'AND ( UPPER( we.cal_name ) LIKE UPPER( ? ) OR UPPER( '
     . ( strcmp ( $GLOBALS['db_type'], 'mssql' ) == 0
      ? 'CAST ( we.cal_description AS varchar (1024) )'
      : 'we.cal_description' )
     . ' ) LIKE UPPER( ? ) ';
    $sql_params[] = '%' . $words[$i] . '%';
    $sql_params[] = '%' . $words[$i] . '%';

    //process advanced filters
    if ( ! empty ( $extra_filter ) ) {
      $sql .= ' OR wse.cal_data LIKE UPPER( ? )';
      $sql_params[] = '%' . $words[$i] . '%';
    }
    //close AND statement from above
    $sql .= ')';
    if ( ! empty ( $cat_filter ) ) {
      $sql .= ' AND wec.cat_id = ? AND we.cal_id = wec.cal_id ';
      $sql_params[] = $cat_filter;
    }
    if ( ! empty ( $extra_filter ) )
      $sql .= ' AND we.cal_id = wse.cal_id ';
    if ( ! empty ( $date_filter ) ) {
      if ( $date_filter == 1 ) { //Past entries
        $sql .= 'AND we.cal_date < ? ';
        $sql_params[] = date ( 'Ymd' );
      }
      if ( $date_filter == 2 ) {//Upcoming entries
        $sql .= 'AND we.cal_date >= ? ';
        $sql_params[] = date ( 'Ymd' );
        $order = 'ASC';
      }
      if ( $date_filter == 3 ) {//Use Date Range
        $sql .= 'AND ( we.cal_date >= ? AND we.cal_date <= ? )';
        $sql_params[] = $startDate;
        $sql_params[] = $endDate;
      }
    }

    $res = dbi_execute ( $sql . ' ORDER BY we.cal_date ' . $order
     . ', we.cal_name', $sql_params );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $info[$matches]['id'] = $row[0];
        $info[$matches]['text'] = $row[1] . ' ( ' . date_to_str( $row[2] ) . ' )';
        $info[$matches]['user'] = $row[3];

        $matches++;
      }
    }
    dbi_free_result ( $res );
  }
}
echo '
    <p><strong>';
if ( $matches > 0 ) {
  // Let update_translation.pl pick up translations.
  // translate ( 'match found' ) translate ( 'matches found' )
  echo $matches . ' '
   . translate ( // line break to bypass update_translation.pl here.
    'match' . ( $matches == 1 ? '' : 'es' ) . ' found' );
} else
  echo translate ( 'No matches found' );

echo ": " . htmlentities ( $keywords ) . '</strong>.</p>';


// now sort by number of hits
if ( empty ( $error ) && empty ( $info ) ) {
  // no mtaches
} else if ( empty ( $error ) ) {
  echo '
    <ul>';
  foreach ( $info as $result ) {
    echo '
      <li><a class="nav" href="view_entry.php?id=' . $result['id']
     . '&amp;user=' . $result['user'] . '">' . $result['text'] . '</a></li>';
  }
  echo '
    </ul>';
}
echo '
      <form action="search.php' . ( ! empty ( $advanced ) ? '?adv=1' : '' )
        . '"  style="margin-left: 13px;" method="post">
       <input type="submit" value="'
        . translate ( 'New Search' ) . '" /></form>
    ' . print_trailer ();

?>
