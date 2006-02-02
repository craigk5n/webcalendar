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
 * @version $Id$
 */
include_once 'includes/init.php';

$error = "";

$keywords = getValue ( "keywords" );

if ( strlen ( $keywords ) == 0 )
  $error = translate("You must enter one or more search keywords") . ".";

$matches = 0;

// Determine if this user is allowed to search the calendar of other users
$search_others = false; // show "Advanced Search"
if ( $single_user == 'Y' )
  $search_others = false;
if ( $is_admin )
  $search_others = true;
else if ( access_is_enabled () )
  $search_others = access_can_access_function ( ACCESS_ADVANCED_SEARCH );
else if ( $login != '__public__' && ! empty ( $ALLOW_VIEW_OTHER ) &&
  $ALLOW_VIEW_OTHER == 'Y' )
  $search_others = true;
else if ( $login == '__public__' && ! empty ( $PUBLIC_ACCESS_OTHERS ) &&
  $PUBLIC_ACCESS_OTHERS == 'Y' )
  $search_others = true;

if ( empty ( $users ) || empty ( $users[0] ) )
  $search_others = false;

// Security precaution -- make sure users listed in participants list
// was not hacked up to include users that they don't really have access to.
if ( $search_others ) {
  // If user can only see users in his group, then remove users not
  // in his group.
  if ( ! empty ( $USER_SEES_ONLY_HIS_GROUPS ) && 
    $USER_SEES_ONLY_HIS_GROUPS == 'Y'
    && ! empty ( $GROUPS_ENABLED ) && $GROUPS_ENABLED == 'Y' ) {
    $myusers = get_my_users ();
    $userlookup = array ();
    for ( $i = 0; $i < count ( $myusers ); $i++ ) {
      $userlookup[$myusers[$i]['cal_login']] = 1;
    }
    $newlist = array ();
    for ( $i = 0; $i < count ( $users ); $i++ ) {
      if ( ! empty ( $userlookup[$users[$i]] ) )
        $newlist[] = $users[$i];
    }
    $users = $newlist;
  }
  // Now, use access control to remove more users :-)
  if ( access_is_enabled () && ! $is_admin ) {
    $newlist = array ( );
    for ( $i = 0; $i < count ( $users ); $i++ ) {
      if ( access_can_view_user_calendar ( $users[$i] ) )
        $newlist[] = $users[$i];
    }
    $users = $newlist;
  }
}

if ( empty ( $users ) || empty ( $users[0] ) )
  $search_others = false;

print_header();
?>

<h2><?php etranslate("Search Results")?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo "<span style=\"font-weight:bold;\">" . translate("Error") . ":</span> $error";
} else {
  $ids = array ();
  $words = split ( " ", $keywords );
  for ( $i = 0; $i < count ( $words ); $i++ ) {
    $sql_params = array();
    // Note: we only search approved/waiting events (not deleted)
    $sql = "SELECT webcal_entry.cal_id, webcal_entry.cal_name, " .
      "webcal_entry.cal_date " .
      "FROM webcal_entry, webcal_entry_user " .
      "WHERE webcal_entry.cal_id = webcal_entry_user.cal_id " .
      "AND webcal_entry_user.cal_status in ('A','W') " .
      "AND webcal_entry_user.cal_login IN ( ";
    if ( $search_others ) {
      if ( empty ( $users[0] ) )
        $users[0] = $login;
      for ( $j = 0; $j < count ( $users ); $j++ ) {
        if ( $j > 0 )
          $sql .= ", ";
        $sql .= " ?";
        $sql_params[] = $users[$j];
      }
    } else {
      $sql .= " ? ";
      $sql_params[] = $login;
    }
    $sql .= ") ";
    if ( $search_others ) {
      // Don't search confidential entries of other users.
      $sql .= "AND ( webcal_entry_user.cal_login = ? OR " .
        "( webcal_entry_user.cal_login != ? AND " .
        "webcal_entry.cal_access = 'P' ) ) ";
        $sql_params[] = $login;
        $sql_params[] = $login;
    }
    //we get an error using mssql trying to read text column as varchar
    //this workaround seems to fix it up ROJ
    //this only will search the first ikb of the description
    $sql .= "AND ( UPPER(webcal_entry.cal_name) " .
      "LIKE UPPER(?) " .
      ( strcmp ( $GLOBALS["db_type"], "mssql" ) == 0? 
        "OR UPPER( CAST ( webcal_entry.cal_description AS varchar(1024) ) ) " :
        "OR UPPER(webcal_entry.cal_description ) " ).
      "LIKE UPPER(?) ) " .
      "ORDER BY cal_date";
    $sql_params[] = '%' . $words[$i] . '%';
    $sql_params[] = '%' . $words[$i] . '%';
    //echo "SQL: $sql<br /><br />";
    //print_r ( $sql_params );
    $res = dbi_execute ( $sql , $sql_params );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $matches++;
        $idstr = strval ( $row[0] );
        if ( empty ( $ids[$idstr] ) )
          $ids[$idstr] = 1;
        else
          $ids[$idstr]++;
        $info[$idstr] = "$row[1] (" . date_to_str ($row[2]) .
          ")";
      }
    }
    dbi_free_result ( $res );
  }
}

if ( $matches > 0 )
  $matches = count ( $ids );

if ( $matches == 1 )
  echo "<span style=\"font-weight:bold;\">$matches " . translate("match found") . ".</span><br /><br />";
else if ( $matches > 0 )
  echo "<span style=\"font-weight:bold;\">$matches " . translate("matches found") . ".</span><br /><br />";
else
  echo translate("No matches found") . ".";

// now sort by number of hits
if ( empty ( $error ) ) {
  arsort ( $ids );
  echo "<ul>\n";
  for ( reset ( $ids ); $key = key ( $ids ); next ( $ids ) ) {
    echo "<li><a class=\"nav\" href=\"view_entry.php?id=$key\">" . $info[$key] . "</a></li>\n";
  }
  echo "</ul>\n";
}

?>
<br /><br />

<?php print_trailer(); ?>

</body>
</html>
