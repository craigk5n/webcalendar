<?php
/**
 * This file lists unapproved entries for one or more users.
 *
 * Optional parameters in URL:
 * url=user specifies that we should only display unapproved
 *   events for that one user
 *
 * The user will be allowed to approve/reject the event if:
 * it is on their own calendar
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @version $Id$
 */
include_once 'includes/init.php';
send_no_cache_header ();

if ( empty ( $user ) )
  $user = $login;

if ( ! empty ( $_POST ) ) {
  $process_action = getPostValue ( 'process_action' );
  $process_user = getPostValue ( 'process_user' );
  if ( ! empty ( $process_action ) ) {
    foreach ( $_POST as $tid => $app_user ) {
      if ( substr ( $tid, 0, 5  ) == 'entry' )
        $type = substr ( $tid, 5, 1 );
        $id = substr( $tid, 6 );
        if ( empty ( $error ) && $id > 0 ) {
          update_status ( $process_action, $app_user, $id, $type );
        }
      }
  }
}

// Only admin user or assistant can specify a username other than his own.
if ( ! $is_admin && $user != $login  && ! $is_assistant &&
  ! access_is_enabled () )
  $user = $login;

$HeadX = generate_refresh_meta ();
//make sure we return after editing an event via this page
remember_this_view();

$INC = array('js/popups.php/true');
print_header($INC,$HeadX);

$key =  0;
$eventinfo = $noret = '';

// List all unapproved events for the specified user.
// Exclude "extension" events (used when an event goes past midnight)
// TODO: only include delete link if they have permission to delete
// when user access control is enabled.

function list_unapproved ( $user ) {
  global $eventinfo, $temp_fullname, $key, $login, $NONUSER_ENABLED, $noret;

  $count = 0;
  $ret = '';
  user_load_variables ( $user, 'temp_' );
  //echo "Listing events for $user<br />";

  $sql = 'SELECT we.cal_id, we.cal_name, we.cal_description, weu.cal_login,
    we.cal_priority, we.cal_date, we.cal_time, we.cal_duration,
    weu.cal_status, we.cal_type
    FROM webcal_entry we, webcal_entry_user weu
    WHERE we.cal_id = weu.cal_id AND weu.cal_login = ? AND weu.cal_status = \'W\'
    ORDER BY weu.cal_login, we.cal_date';
  $rows = dbi_get_cached_rows ( $sql, array ( $user ) );
  if ( $rows ) {
    $viewStr = translate( 'View this entry' );
    $allDayStr = translate('All day event');
    $appConStr = translate( 'Approve/Confirm' );
    $rejectStr = translate( 'Reject' );
    $deleteStr = translate( 'Delete' );
    $checkAllStr = translate ( 'Check All' );
    $uncheckAllStr = translate ( 'Uncheck All' );
    $appSelStr = translate ( 'Approve Selected' );
    $rejectSelStr = translate ( 'Reject Selected' );
    $emailStr = translate ( 'Emails Will Not Be Sent' );
    for ( $i = 0, $cnt = count ( $rows ); $i < $cnt; $i++ ) {
      $row = $rows[$i];
      $key++;
      $id = $row[0];
      $name = $row[1];
      $description = $row[2];
      $cal_user = $row[3];
      $pri = $row[4];
      $date = $row[5];
      $time = sprintf ( "%06d", $row[6] );
      $duration = $row[7];
      $status = $row[8];
      $type = $row[9];
      $view_link = 'view_entry';
      $entryID = 'entry' . $type . $id;

      if ($count == 0 ) {
        $ret .= '<tr><td colspan="5"><h3>' . $temp_fullname . "</h3></td></tr>\n";
      }
      $tribbon =  ( $count %2 == 0 ? '' :'class="odd"' );
      $ret .= "<tr $tribbon><td width=\"5%\" align=\"right\">";
      $ret .= "<input type=\"checkbox\" name=\"$entryID\"  value=\"$user\"/></td>\n";
      $divname = "eventinfo-pop$id-$key";
      $linkid  = "pop$id-$key";
      $ret .= '<td><a  title="' . $viewStr .
        "\" class=\"entry\" id=\"$linkid\" href=\"$view_link.php?id=$id&amp;user=$cal_user\">";
      $timestr = '';
      if ( $time > 0 || ($time == 0 && $duration != 1440 ) ) {
        $eventstart = date_to_epoch ( $date . $time );
        $eventstop = $eventstart + $duration;
        $timestr = display_time ('', 0, $eventstart);
        if ( $duration > 0 ) {
          $timestr .= ' - ' . display_time ( '', 0, $eventstop );
        }
        $eventdate = date_to_str ( date ('Ymd', $eventstart) );
      } else {
        //don't shift date if All Day or Untimed
        $eventdate = date_to_str ( $date );
        // if All Day display in popup
        if ($time == 0 && $duration == 1440 ) {
          $timestr = $allDayStr;
        }
      }
      $ret .= htmlspecialchars ( $name );
      $ret .= '</a>';
      $ret .= ' (' . $eventdate . ")\n";
      //approve
      $ret .= ':</td><td align="center">' . "\n"
        . '<input type="image" src="images/check.gif" title="' . $appConStr
        . "\" onclick=\"return do_confirm('approve','$cal_user', '$entryID');\" /></td>\n";
      //reject
      $ret .= '<td align="center">' . "\n"
        . '<input type="image" src="images/rejected.gif" title="' . $rejectStr
        . "\" onclick=\"return do_confirm('reject','$cal_user', '$entryID');\" /></td>\n";
      //delete
      if ( ! access_is_enabled () ||
        access_user_calendar ( 'edit', $user ) ) {
        $ret .= '<td align="center">' . "\n"
        . '<input type="image" src="images/delete.png" title="' . $deleteStr
        . "\" onclick=\"return do_confirm('delete','$cal_user', '$entryID');\" /></td>\n";
      }
      $eventinfo .= build_entry_popup ( $divname, $cal_user, $description,
        $timestr, site_extras_for_popup ( $id ));
      $count++;
      $ret .= "</tr>\n";
    }
    if ( $count > 1 ) {
      $ret .= '<tr><td colspan="5" nowrap="nowrap">&nbsp;' . "\n";
      $ret .= '<img src="images/select.gif" border="0" alt="" />' . "\n";
      $ret .= "<label><a title=\"$checkAllStr\" onclick=\"check_all('$user');\">" .
        $checkAllStr . "</a>  /  ";
      $ret .=  "<a  title=\"$uncheckAllStr\" onclick=\"uncheck_all('$user');\">" .
        $uncheckAllStr. "</a></label>";
      $ret .= '&nbsp;&nbsp;&nbsp;' . "\n";
      $ret .= '<input  type="image" src="images/check.gif" title="' . $appSelStr
        . "\" onclick=\"return do_confirm('approveSelected','$cal_user');\" />";
      $ret .= '&nbsp;&nbsp;&nbsp;'  . "\n";
      $ret .= '<input  type="image" src="images/rejected.gif" title="' . $rejectSelStr
        . "\" onclick=\"return do_confirm('rejectSelected','$cal_user');\" />";
      $ret .= "&nbsp;&nbsp;&nbsp;( " . $emailStr . ' )';
      $ret .= "</td></tr>\n";
    }
  }
  if ( $count == 0  ) {
    $noret .= '<tr><td colspan="5" class="nounapproved">' .
      translate( 'No unapproved entries for' ) . '&nbsp;' .
      $temp_fullname . ".</td></tr>\n";
  }
  return $ret;
} //end list_unapproved ()
?>

<h2><?php
 etranslate( 'Unapproved Entries' );
 //if ( $user == '__public__' ) echo " - " . $PUBLIC_ACCESS_FULLNAME;
?></h2>
<?php
$my_non_users = $app_users = array ();
$app_user_hash = array ();
$non_users = get_nonuser_cals ();
foreach ( $non_users as $nonuser ) {
  if ( user_is_nonuser_admin ( $login, $nonuser['cal_login'] ) ) {
    $my_non_users[]['cal_login'] = $nonuser['cal_login'];
    //echo $nonuser['cal_login'] . "<br />";
  }
}

// If a user is specified, we list just that user.
if ( ( $is_assistant || $is_nonuser_admin || $is_admin ||
  access_is_enabled () ) &&
  ! empty ( $user ) && $user != $login ) {
  if ( ! access_is_enabled () ||
    access_user_calendar ( 'approve', $user ) ) {
    $app_users[] = $user;
    $app_user_hash[$user] = 1;
  } else {
    // not authorized to approve for specified user
    echo translate ( 'Not authorized' );
  }
} else {
  // First, we list ourself
  $app_users[] = $login;
  $app_user_hash[$login] = 1;
  if ( access_is_enabled () ) {
    if ( $NONUSER_ENABLED == 'Y' ) {
      $all = array_merge ( get_my_users (), $my_non_users );
    } else {
      $all = get_my_users ();
    }
    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( access_user_calendar ( 'approve', $x ) ) {
        if ( empty ( $app_user_hash[$x] ) ) {
          $app_users[] = $x;
          $app_user_hash[$x] = 1;
        }
      }
    }
  } else {
    if ( $is_admin && $PUBLIC_ACCESS == 'Y' &&
      ( empty ( $user ) || $user != '__public__' ) ) {
      $app_users[] = '__public__';
      $app_users_hash['__public__'] = 1;
    }
    $all = $my_non_users;
    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
        if ( empty ( $app_user_hash[$x] ) ) {
          $app_users[] = $x;
          $app_user_hash[$x] = 1;
        }
    }
  }
}
?>
<form action="list_unapproved.php" name="listunapproved" method="post">
<table border="0">
<?php
  for ( $i = 0, $cnt = count ( $app_users ); $i < $cnt; $i++ ) {
    // List unapproved entries for this user.
    echo list_unapproved ( $app_users[$i] );
  }
  echo '<tr><td colspan="5">&nbsp;</td></tr>' .$noret; //list users with no events
?>
</table>
<input type="hidden" name="process_action" value="" />
<input type="hidden" name="process_user" value="" />
</form>
<?php if ( ! empty ( $eventinfo ) ) echo $eventinfo; ?>
<script language="javascript" type="text/javascript">
<!-- <![CDATA[
function check_all( user) {
  var theForm = document.forms['listunapproved'];
  var z = 0;
  for(z=0; z < theForm.length;z++){
    if(theForm[z].type == 'checkbox' && theForm[z].value == user){
    theForm[z].checked = true;
    }
  }
}
function uncheck_all(user) {
  var theForm = document.forms['listunapproved'];
  var z = 0;
  for(z=0; z < theForm.length;z++){
    if(theForm[z].type == 'checkbox' && theForm[z].value == user){
    theForm[z].checked = false;
    }
  }
}
function do_confirm ( phrase, user, id ) {

  form = document.listunapproved;
  switch ( phrase ) {
    case "approve":
      str = "<?php etranslate( 'Approve this entry?', true) ?>";
      action = 'A';
      break;
    case "reject":
      str = "<?php etranslate( 'Reject this entry?', true) ?>";
      action = 'R';
      break;
    case "delete":
      str = "<?php str_replace ( 'XXX', translate ( 'entry' ),
        translate ( 'Are you sure you want to delete this XXX?' ) ) ?>";
      action = 'D';
      break;
    case "approveSelected":
      str = "<?php etranslate( 'Approve Selected entries?', true) ?>";
      action = 'A';
      break;
    case "rejectSelected":
      str = "<?php etranslate( 'Reject Selected entries?', true) ?>";
      action = 'R';
      break;
    default:
      str = action = '';
  }
  form.process_action.value = action;
  form.process_user.value = user;
  conf = confirm(str);
  //We need this if only single operation
  if ( id  && conf )
    form.elements[id].checked = true;
  return conf;
}
//]]> -->
</script>
<?php echo print_trailer(); ?>

