<?php
/*
 * This page produces search results.
 *
 * "Advanced Search" adds the ability to search other users' calendars.
 * We do a number of security checks to make sure this is allowed.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @version $Id$
 */
include_once 'includes/init.php';

$error = '';

$keywords = getValue ( 'keywords' );

if ( strlen ( $keywords ) == 0 )
  $error = translate ( 'You must enter one or more search keywords' ) . '.';

$matches = 0;
// Determine if this user is allowed to search the calendar of other users
$search_others = false; // show "Advanced Search"
if ( $single_user == 'Y' )
  $search_others = false;

if ( $is_admin )
  $search_others = true;
else
if ( access_is_enabled () )
  $search_others = access_can_access_function ( ACCESS_ADVANCED_SEARCH );
else
if ( $login != '__public__' && ! empty ( $ALLOW_VIEW_OTHER ) &&
    $ALLOW_VIEW_OTHER == 'Y' )
  $search_others = true;
else
if ( $login == '__public__' && ! empty ( $PUBLIC_ACCESS_OTHERS ) &&
    $PUBLIC_ACCESS_OTHERS == 'Y' )
  $search_others = true;

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
    $userlookup = array ();
    for ( $i = 0, $cnt = count ( $myusers ); $i < $cnt; $i++ ) {
      $userlookup[$myusers[$i]['cal_login']] = 1;
    }
    $newlist = array ();
    $cnt = count ( $users );
    for ( $i = 0; $i < $cnt; $i++ ) {
      if ( ! empty ( $userlookup[$users[$i]] ) )
        $newlist[] = $users[$i];
    }
    $users = $newlist;
  }
  // Now, use access control to remove more users :-)
  if ( access_is_enabled () && ! $is_admin ) {
    $newlist = array ();
    for ( $i = 0; $i < $cnt; $i++ ) {
      if ( access_user_calendar ( 'view', $users[$i] ) )
        $newlist[] = $users[$i];
    }
    $users = $newlist;
  }
}

if ( empty ( $users ) || empty ( $users[0] ) )
  $search_others = false;

print_header ();
echo '
    <h2>' . translate ( 'Search Results' ) . '</h2>';

if ( ! empty ( $error ) )
  echo print_error ( $error );
else {
  $ids = array ();
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
  $words = array ( $phrase );
} else
  // original (default) behavior
  $words = split ( ' ', $keywords );
// end Phrase modification

  $word_cnt = count ( $words );
  for ( $i = 0; $i < $word_cnt; $i++ ) {
    $sql_params = array ();
    // Note: we only search approved/waiting events (not deleted).
    $sql = 'SELECT we.cal_id, we.cal_name, we.cal_date
      FROM webcal_entry we, webcal_entry_user weu
      WHERE we.cal_id = weu.cal_id AND weu.cal_status in ( "A","W" )
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
        OR ( weu.cal_login != ? AND we.cal_access = "P" ) ) ';
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
     . ' ) LIKE UPPER( ? ) ) ORDER BY cal_date';
    $sql_params[] = '%' . $words[$i] . '%';
    $sql_params[] = '%' . $words[$i] . '%';
    // echo "SQL: $sql<br /><br />";
    // print_r ( $sql_params );
    $res = dbi_execute ( $sql, $sql_params );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $matches++;
        $idstr = strval ( $row[0] );
        if ( empty ( $ids[$idstr] ) )
          $ids[$idstr] = 1;
        else
          $ids[$idstr]++;

        $info[$idstr] = "$row[1] ( " . date_to_str ( $row[2] ) . ' )';
      }
    }
    dbi_free_result ( $res );
  }
}

ob_start ();
echo '
    <p><strong>';
if ( $matches > 0 ) {
  $matches = count ( $ids );
  // Let update_translation.pl pick up translations.
  // translate ( 'match found' ) translate ( 'matches found' )
  echo $matches . ' '
   . translate ( // line break to bypass update_translation.pl here.
    'match' . ( $matches == 1 ? '' : 'es' ) . ' found' );
} else
  echo translate ( 'No matches found' );

echo ": $keywords" . '</strong>.</p>';
// now sort by number of hits
if ( empty ( $error ) ) {
  arsort ( $ids );
  echo '
    <ul>';
  for ( reset ( $ids ); $key = key ( $ids ); next ( $ids ) ) {
    echo '
      <li><a class="nav" href="view_entry.php?id=' . $key . '">' . $info[$key]
     . '</a></li>';
  }
  echo '
    </ul>';
}

ob_end_flush ();
echo print_trailer ();

?>
