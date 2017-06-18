<?php
/* This file lists unapproved entries for one or more users.
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
 * @version $Id: list_unapproved.php,v 1.74.2.6 2012/02/28 02:07:45 cknudsen Exp $
 */

include_once 'includes/init.php';
require_valide_referring_url ();
send_no_cache_header ();

if ( empty ( $user ) )
  $user = $login;

if ( ! empty ( $_POST ) ) {
  $process_action = getPostValue ( 'process_action' );
  $process_user = getPostValue ( 'process_user' );
  if ( ! empty ( $process_action ) ) {
    foreach ( $_POST as $tid => $app_user ) {
      if ( substr ( $tid, 0, 5 ) == 'entry' ) {
        $type = substr ( $tid, 5, 1 );
        $id = substr ( $tid, 6 );
        if ( empty ( $error ) && $id > 0 )
          update_status ( $process_action, $app_user, $id, $type );
      }
    }
  }
}

// Only admin user or assistant can specify a username other than his own.
if ( ! $is_admin && $user != $login && ! $is_assistant && ! access_is_enabled () )
  $user = $login;
// Make sure we return after editing an event via this page.
remember_this_view ();

$key = 0;
$eventinfo = $noret = '';

/* List all unapproved events for the specified user.
 * Exclude "extension" events (used when an event goes past midnight).
 * TODO: Only include delete link if they have permission to delete
 *       when user access control is enabled.
 * NOTE: this function is almost identical to the one in rss_unapproved.php.
 * Just the format (RSS vs HTML) is different.
*/
function list_unapproved ( $user ) {
  global $eventinfo, $key, $login, $NONUSER_ENABLED, $noret, $temp_fullname;

  user_load_variables ( $user, 'temp_' );

  $rssLink = '<a href="rss_unapproved.php?user=' .
    htmlspecialchars ( $user ) . '"><img src="images/rss.png" width="14" height="14" alt="RSS 2.0 - ' .
    htmlspecialchars ( $temp_fullname ) . '" border="0"/></a>';

  $count = 0;
  $ret = '';

  $sql = 'SELECT we.cal_id, we.cal_name, we.cal_description, weu.cal_login,
    we.cal_priority, we.cal_date, we.cal_time, we.cal_duration,
    weu.cal_status, we.cal_type
    FROM webcal_entry we, webcal_entry_user weu
    WHERE we.cal_id = weu.cal_id AND weu.cal_login = ? AND weu.cal_status = \'W\'
    ORDER BY weu.cal_login, we.cal_date';
  $rows = dbi_get_cached_rows ( $sql, array ( $user ) );
  if ( $rows ) {
    $allDayStr = translate ( 'All day event' );
    $appConStr = translate ( 'Approve/Confirm' );
    $appSelStr = translate ( 'Approve Selected' );
    $checkAllStr = translate ( 'Check All' );
    $deleteStr = translate ( 'Delete' );
    $emailStr = translate ( 'Emails Will Not Be Sent' );
    $rejectSelStr = translate ( 'Reject Selected' );
    $rejectStr = translate ( 'Reject' );
    $uncheckAllStr = translate ( 'Uncheck All' );
    $viewStr = translate ( 'View this entry' );
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

      $linkid = "pop$id-$key";
      $timestr = '';
      if ( $time > 0 || ( $time == 0 && $duration != 1440 ) ) {
        $eventstart = date_to_epoch ( $date . $time );
        $eventstop = $eventstart + $duration;
        $eventdate = date_to_str ( date ( 'Ymd', $eventstart ) );
        $timestr = display_time ( '', 0, $eventstart )
         . ( $duration > 0 ? ' - ' . display_time ( '', 0, $eventstop ) : '' );
      } else {
        // Don't shift date if All Day or Untimed.
        $eventdate = date_to_str ( $date );
        // If All Day display in popup.
        if ( $time == 0 && $duration == 1440 )
          $timestr = $allDayStr;
      }

      $ret .= ( $count == 0 ? '
      <tr>
        <td colspan="5"><h3>' . $temp_fullname . '&nbsp;' . $rssLink . '</h3></td>
      </tr>' : '' ) . '
      <tr ' . ( $count % 2 == 0 ? '' : 'class="odd"' ) . '>
        <td width="5%" align="right"><input type="checkbox" name="'
       . $entryID . '" value="' . $user . '"/></td>
        <td><a title="' . $viewStr . '" class="entry" id="' . $linkid
       . '" href="' . $view_link . '.php?id=' . $id . '&amp;user=' . $cal_user
       . '">' . htmlspecialchars ( $name ) . '</a> (' . $eventdate . '):</td>'
      /* approve */ . '
        <td align="center"><input type="image" src="images/check.gif" title="'
       . $appConStr . '" onclick="return do_confirm( \'approve\', \''
       . $cal_user . '\', \'' . $entryID . '\' );" /></td>'
      /* reject */ . '
        <td align="center"><input type="image" src="images/rejected.gif" title="'
       . $rejectStr . '" onclick="return do_confirm( \'reject\', \''
       . $cal_user . '\', \'' . $entryID . '\' );" /></td>'
      /* delete */
       . ( ! access_is_enabled () || access_user_calendar ( 'edit', $user ) ? '
        <td align="center"><input type="image" src="images/delete.png" title="'
         . $deleteStr . '" onclick="return do_confirm( \'delete\', \''
         . $cal_user . '\', \'' . $entryID . '\' );\" /></td>' : '' ) . '
      </tr>';

      $eventinfo .= build_entry_popup ( 'eventinfo-' . $linkid, $cal_user,
        $description, $timestr, site_extras_for_popup ( $id ) );
      $count++;
    }
    if ( $count > 1 )
      $ret .= '
      <tr>
        <td colspan="5" nowrap="nowrap">&nbsp;
          <img src="images/select.gif" border="0" alt="" />
          <label><a title="' . $checkAllStr . '" onclick="check_all( \''
       . $user . '\' );">' . $checkAllStr . '</a> / <a title="' . $uncheckAllStr
       . '" onclick="uncheck_all( \'' . $user . '\' );">' . $uncheckAllStr
       . '</a></label>&nbsp;&nbsp;&nbsp;
          <input type="image" src="images/check.gif" title="' . $appSelStr
       . '" onclick="return do_confirm( \'approveSelected\', \'' . $cal_user
       . '\' );" />&nbsp;&nbsp;&nbsp;
          <input type="image" src="images/rejected.gif" title="' . $rejectSelStr
       . '" onclick="return do_confirm( \'rejectSelected\', \'' . $cal_user
       . '\' );" />&nbsp;&nbsp;&nbsp;( ' . $emailStr . ' )
        </td>
      </tr>';
  }
  if ( $count == 0 )
    $noret .= '
      <tr>
        <td colspan="5" class="nounapproved">'
    // translate ( 'No unapproved entries for' )
    . str_replace ( 'XXX', $temp_fullname,
      translate ( 'No unapproved entries for XXX.' ) ) .
      '&nbsp;' . $rssLink . '</td>
      </tr>';

  return $ret;
} //end list_unapproved ()
print_header ( array ( 'js/popups.php/true' ), generate_refresh_meta () );

ob_start ();

echo '
    <h2>' . translate ( 'Unapproved Entries' ) . '</h2>';

$app_user_hash = $app_users = $my_non_users = array ();
$non_users = get_nonuser_cals ();
foreach ( $non_users as $nonuser ) {
  if ( user_is_nonuser_admin ( $login, $nonuser['cal_login'] ) )
    $my_non_users[]['cal_login'] = $nonuser['cal_login'];
}

// If a user is specified, we list just that user.
if ( ( $is_assistant || $is_nonuser_admin || $is_admin ||
    access_is_enabled () ) && ! empty ( $user ) && $user != $login ) {
  if ( ! access_is_enabled () ||
      access_user_calendar ( 'approve', $user ) ) {
    $app_user_hash[$user] = 1;
    $app_users[] = $user;
  } else
    // Not authorized to approve for specified user.
    echo translate ( 'Not authorized' );
} else {
  // First, we list ourself.
  $app_user_hash[$login] = 1;
  $app_users[] = $login;
  if ( access_is_enabled () ) {
    $all = ( $NONUSER_ENABLED == 'Y'
      ? array_merge ( get_my_users (), $my_non_users )
      : get_my_users () );

    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( access_user_calendar ( 'approve', $x ) &&
          empty ( $app_user_hash[$x] ) ) {
        $app_user_hash[$x] = 1;
        $app_users[] = $x;
      }
    }
  } else {
    if ( $is_admin && $PUBLIC_ACCESS == 'Y' &&
      ( empty ( $user ) || $user != '__public__' ) ) {
      $app_users_hash['__public__'] = 1;
      $app_users[] = '__public__';
    }
    $all = $my_non_users;
    for ( $j = 0, $cnt = count ( $all ); $j < $cnt; $j++ ) {
      $x = $all[$j]['cal_login'];
      if ( empty ( $app_user_hash[$x] ) ) {
        $app_user_hash[$x] = 1;
        $app_users[] = $x;
      }
    }
  }
}

echo '
    <form action="list_unapproved.php" name="listunapproved" method="post">
      <table border="0" summary="">';

for ( $i = 0, $cnt = count ( $app_users ); $i < $cnt; $i++ ) {
  // List unapproved entries for this user.
  echo list_unapproved ( $app_users[$i] );
}

echo '
        <tr>
          <td colspan="5">&nbsp;</td>
        </tr>' // List users with no events.
. $noret . '
      </table>
      <input type="hidden" name="process_action" value="" />
      <input type="hidden" name="process_user" value="" />
    </form>' . ( ! empty ( $eventinfo ) ? $eventinfo : '' ) . '
    <script language="javascript" type="text/javascript">
<!-- <![CDATA[
      function check_all ( user ) {
        var
          theForm = document.forms [ \'listunapproved\' ],
          z;

        for ( z = 0; z < theForm.length; z++ ) {
          if ( theForm[z].type == \'checkbox\' && theForm[z].value == user )
            theForm[z].checked = true;
        }
      }
      function uncheck_all ( user ) {
        var
          theForm = document.forms[\'listunapproved\'],
          z;

        for ( z = 0; z < theForm.length; z++ ) {
          if ( theForm[z].type == \'checkbox\' && theForm[z].value == user )
            theForm[z].checked = false;
        }
      }
      function do_confirm ( phrase, user, id ) {

        form = document.listunapproved;
        switch ( phrase ) {
          case "approve":
            str = "' . translate ( 'Approve this entry?', true ) . '";
            action = \'A\';
            break;
          case "reject":
            str = "' . translate ( 'Reject this entry?', true ) . '";
            action = \'R\';
            break;
          case "delete":
            str = "' . str_replace ( 'XXX', translate ( 'entry' ),
  translate ( 'Are you sure you want to delete this XXX?' ) ) . '";
            action = \'D\';
            break;
          case "approveSelected":
            str = "' . translate ( 'Approve Selected entries?', true ) . '";
            action = \'A\';
            break;
          case "rejectSelected":
            str = "' . translate ( 'Reject Selected entries?', true ) . '";
            action = \'R\';
            break;
          default:
            str = action = \'\';
        }
        form.process_action.value = action;
        form.process_user.value = user;
        conf = confirm ( str );
        // We need this if only single operation.
        if ( id  && conf )
          form.elements[id].checked = true;

        return conf;
      }
//]]> -->
    </script>
    ';
ob_end_flush ();
echo print_trailer ();

?>
