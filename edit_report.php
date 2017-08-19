<?php
/**
 * Presents a HTML form to add or edit a report.
 *
 * Input Parameters:
 * - <var>report_id</var> (optional) - the report id of the report to edit. If
 *   blank, user is adding a new report.
 * - <var>public</var> (optional) - If set to '1' and user is an admin user,
 *   then we are creating a report for the public user.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 * @subpackage Reports
 */

/* Security:
 * If system setting $REPORTS_ENABLED is set to anything other than 'Y',
 * then don't allow access to this page.
 * If $ALLOW_VIEW_OTHER is 'N', then do not allow selection of participants.
 * Only report creator (cal_login in webcal_report), or an admin user,
 * can edit/delete report.
 */

include_once 'includes/init.php';
load_user_categories();

$adding_report = false;
$charset = ( empty ( $LANGUAGE ) ? 'iso-8859-1' : translate ( 'charset' ) );
$checked = ' checked="checked"';
$error =
 ( empty ( $REPORTS_ENABLED ) || $REPORTS_ENABLED != 'Y' || $login == '__public__'
   ? print_not_auth() : '' );
$report_id = getValue ( 'report_id', '-?[0-9]+', true );
$selected = ' selected="selected"';
$show_participants = ( $single_user == 'Y' || $DISABLE_PARTICIPANTS_FIELD == 'Y'
  ? false : true );
$updating_public = ( $is_admin && ! empty( $public ) && $PUBLIC_ACCESS == 'Y' );

$report_user = ( $updating_public ? '__public__' : '');

if ( empty ( $report_id ) ) {
  $adding_report = true;
  $include_header = $report_allow_nav = 'Y';
  $report_id = -1;
  $report_is_global = 'N';
}

// Set date range options.
$ranges = [
  '0' => translate ( 'Tomorrow' ),
  '1' => translate ( 'Today' ),
  '2' => translate ( 'Yesterday' ),
  '3' => translate ( 'Day before yesterday' ),
  '10' => translate ( 'Next week' ),
  '11' => translate ( 'This week' ),
  '12' => translate ( 'Last week' ),
  '13' => translate ( 'Week before last' ),
  '20' => translate ( 'Next week and week after' ),
  '21' => translate ( 'This week and next week' ),
  '22' => translate ( 'Last week and this week' ),
  '23' => translate ( 'Last two weeks' ),
  '30' => translate ( 'Next month' ),
  '31' => translate ( 'This month' ),
  '32' => translate ( 'Last month' ),
  '33' => translate ( 'Month before last' ),
  '40' => translate ( 'Next year' ),
  '41' => translate ( 'This year' ),
  '42' => translate ( 'Last year' ),
  '43' => translate ( 'Year before last' ),
  '50' => translate ( 'Next 14 days' ),
  '51' => translate ( 'Next 30 days' ),
  '52' => translate ( 'Next 60 days' ),
  '53' => translate ( 'Next 90 days' ),
  '54' => translate ( 'Next 180 days' ),
  '55' => translate ( 'Next 365 days' )];

// Get list of users visible to the current user.
if ( empty ( $error ) && $show_participants ) {
  $userlist = get_my_users ( '', 'view' );
  if ( $NONUSER_ENABLED == 'Y' ) {
    // Restrict NUC list if groups are enabled.
    $nonusers = get_my_nonusers ( $login, true, 'view' );
    $userlist = ( $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $nonusers, $userlist )
      : array_merge ( $userlist, $nonusers ) );
  }
  $userlistcnt = count ( $userlist );
}

// Default values.
$day_template = '<dt><b>${date}</b></dt>
<dd><dl>${events}</dl></dd>';

$event_template = '<dt>${name}</dt>
<dd><b>' . translate ( 'Date' ) . ':</b> ${date}<br />
<b>' . translate ( 'Time' ) . ':</b> ${time}<br />
${description}</dd>
';

$page_template = '<dl>${days}</dl>';

// Setup option arrays.
$day_options = ['events', 'date', 'fulldate', 'report_id'];

$event_options = ['name', 'description', 'date', 'fulldate', 'time',
  'starttime', 'endtime', 'duration', 'location', 'url', 'priority', 'href',
  'user', 'fullname', 'report_id'];

$page_options = ['days', 'report_id'];

/**
 * Generate clickable option lists.
 */
function print_options ( $textarea, $option ) {
  // Use ASCII values for ${}.
  echo '
            <a onclick="addMe( \'' . $textarea . '\', \'${' . $option
   . '}\' )">${' . $option . '}</a><br />';
}

if ( empty ( $error ) && $report_id >= 0 ) {
  $res = dbi_execute ( 'SELECT cal_login, cal_report_id, cal_is_global,
    cal_report_type, cal_include_header, cal_report_name, cal_time_range,
    cal_user, cal_allow_nav, cal_cat_id, cal_include_empty, cal_show_in_trailer,
    cal_update_date FROM webcal_report WHERE cal_report_id = ?',
    [$report_id] );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $i = 0;
      $report_login = $row[$i++];
      $report_id = $row[$i++];
      $report_is_global = $row[$i++];
      $report_type = $row[$i++];
      $report_include_header = $row[$i++];
      $report_name = $row[$i++];
      $report_time_range = $row[$i++];
      $report_user = $row[$i++];
      $report_allow_nav = $row[$i++];
      $report_cat_id = $row[$i++];
      $report_include_empty = $row[$i++];
      $report_show_in_menu = $row[$i++];
      $report_update_date = $row[$i++];

      // Check permissions.
      if ( $show_participants && ! empty ( $report_user ) ) {
        $user_is_in_list = false;
        for ( $i = 0; $i < $userlistcnt; $i++ ) {
          if ( $report_user == $userlist[$i]['cal_login'] )
            $user_is_in_list = true;
        }
        if ( ! $user_is_in_list && $report_login != $login && ! $is_admin )
          $error = print_not_auth();
      }
      if ( ! $is_admin && $login != $report_login )
        // Only creator or an admin can edit/delete the event.
        $error = print_not_auth();

      // If we are editing a public user report we need to set $updating_public.
      if ( $is_admin && $report_login == '__public__' )
        $updating_public = true;
    } else
      $error = str_replace ( 'XXX', $report_id,
        translate ( 'Invalid report id XXX.' ) );

    dbi_free_result ( $res );
  } else
    $error = db_error();

  $res = dbi_execute ( 'SELECT cal_template_type, cal_template_text
  FROM webcal_report_template
  WHERE cal_report_id = ?', [$report_id] );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] == 'D' )
        $day_template = $row[1];
      elseif ( $row[0] == 'E' )
        $event_template = $row[1];
      elseif ( $row[0] == 'P' )
        $page_template = $row[1];
    }
    dbi_free_result ( $res );
  }
} else {
  // Default values for new report.
  $report_allow_nav = $report_include_header = 'Y';
  $report_cat_id = $report_update_date = '';
  $report_id = -1;
  $report_include_empty = $report_is_global = $report_show_in_menu = 'N';
  $report_login = $login;
  $report_name = translate ( 'Unnamed Report' );
  $report_time_range = 11; // Current week.
  $report_type = 'html';
  // $report_user already set.
}

print_header();

if ( ! empty ( $error ) ) {
  echo $error . print_trailer ( false );
  exit;
}
echo '
    <h2>'
 . ( $updating_public ? translate ( $PUBLIC_ACCESS_FULLNAME ) . ' ' : '' )
 . ( $adding_report ? translate ( 'Add Report' ) : translate ( 'Edit Report' ) )
 . '</h2>
    <form action="edit_report_handler.php" method="post" name="reportform">'
 . ( $updating_public ? '
      <input type="hidden" name="public" value="1" />' : '' )
 . ( ! $adding_report ? '
      <input type="hidden" name="report_id" value="'
   . $report_id . '" />' : '' ) . '
      <table>
        <tr>
          <td><label for="rpt_name">' . translate ( 'Report name' )
 . ':</label></td>
          <td><input type="text" name="report_name" id="rpt_name" size="40" '
 . 'maxlength="50" value="' . $report_name . '" /></td>
        </tr>';

if ( $show_participants ) {
  echo '
        <tr>
          <td><label for="rpt_user">' . translate ( 'User' ) . ':</label></td>
          <td>
            <select name="report_user" id="rpt_user" size="1">
              <option value=""' . ( empty ( $report_user ) ? $selected : '' )
   . '>' . translate ( 'Current User' ) . '</option>';

  for ( $i = 0; $i < $userlistcnt; $i++ ) {
    echo '
              <option value="' . $userlist[$i]['cal_login'] . '"'
     . ( ! empty ( $report_user ) && $report_user == $userlist[$i]['cal_login']
      ? $selected : '' ) . '>' . $userlist[$i]['cal_fullname'] . '</option>';
  }

  echo '
            </select>
          </td>
        </tr>';
}

echo ( $is_admin ? '
        <tr>
          <td><label>' . translate( 'Global' ) . ':</label></td>
          <td>' . print_radio( 'is_global', '', '',
    ( ! empty( $report_is_global ) && $report_is_global == 'Y'
      ? 'Y' : 'N' ) ) . '</td>
        </tr>'

  // The report will always be shown in the menu for the creator of the report.
  // For admin users who create a global report,
  // allow option of adding to all users menu.
 . '
        <tr>
          <td><label>' . translate( 'Include link in menu' ) . ':</label></td>
          <td>' . print_radio( 'show_in_trailer', '', '',
    ( ! empty( $report_show_in_menu ) && $report_show_in_menu == 'Y'
      ? 'Y' : 'N' ) ) . '</td>
        </tr>' : '' ) . '
        <tr>
          <td><label>' . translate( 'Include standard header/trailer' )
 . ':</label></td>
          <td>' . print_radio( 'include_header', '', '',
    ( ! empty( $report_include_header ) && $report_include_header == 'Y'
      ? 'Y' : 'N' ) ) . '</td>
        </tr>
        <tr>
          <td><label>' . translate( 'Include previous/next links' )
 . ':</label></td>
          <td>' . print_radio( 'allow_nav', '', '',
    ( ! empty( $report_allow_nav ) && $report_allow_nav == 'Y'
      ? 'Y' : 'N' ) ) . '</td>
        </tr>
        <tr>
          <td><label>' . translate( 'Include empty dates' ) . ':</label></td>
          <td>' . print_radio( 'include_empty', '', '',
    ( ! empty( $report_include_empty ) && $report_include_empty == 'Y'
      ? 'Y' : 'N' ) ) . '</td>
        </tr>
        <tr>
          <td><label for="rpt_time_range">' . translate ( 'Date range' )
 . ':</label></td>
          <td>
            <select name="time_range" id="rpt_time_range">';

while ( list ( $num, $descr ) = each ( $ranges ) ) {
  echo '
              <option value="' . $num . '"'
   . ( $report_time_range == $num ? $selected : '' )
   . '>' . $descr . '</option>';
}

echo '
            </select>
          </td>
        </tr>';

if ( $CATEGORIES_ENABLED == 'Y' ) {
  echo '
        <tr>
          <td><label for="rpt_cat_id">' . translate ( 'Category' )
   . ':</label></td>
          <td>
            <select name="cat_id" id="rpt_cat_id">
              <option value="">' . translate ( 'None' ) . '</option>';

  while ( list ( $K, $V ) = each ( $categories ) ) {
    echo '
              <option value="' . $K . '"' . ( $report_cat_id == $K ? $selected : '' )
     . '>' . htmlentities ( $V['cat_name'] ) . '</option>';
  }

  echo '
            </select>
          </td>
        </tr>';
} //end $CATEGORIES_ENABLED test
echo '
      </table>
      <table>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td colspan="2"><label>' . translate ( 'Template variables' )
 . '</label></td>
        </tr>
        <tr>
          <td class="aligntop"><label>' . translate ( 'Page template' )
 . ':</label></td>
          <td><textarea rows="12" cols="60" name="page_template">'
 . htmlentities ( $page_template, ENT_COMPAT, $charset ) . '</textarea></td>
          <td class="aligntop cursoradd" colspan="2">';

foreach ( $page_options as $option ) {
  print_options ( 'page_template', $option );
}

echo '
          </td>
        </tr>
        <tr>
          <td class="aligntop"><label>' . translate ( 'Day template' )
 . ':</label></td>
          <td><textarea rows="12" cols="60" name="day_template">'
 . htmlentities ( $day_template, ENT_COMPAT, $charset ) . '</textarea></td>
          <td class="aligntop cursoradd" colspan="2">';

foreach ( $day_options as $option ) {
  print_options ( 'day_template', $option );
}

echo '
          </td>
        </tr>
        <tr>
          <td class="aligntop"><label>' . translate ( 'Event template' )
 . ':</label></td>
          <td><textarea rows="12" cols="60" name="event_template" id="event_template">'
 . htmlentities ( $event_template, ENT_COMPAT, $charset ) . '</textarea></td>
          <td class="aligntop cursoradd" width="150px">';

foreach ( $event_options as $option ) {
  print_options ( 'event_template', $option );
}

echo '
          </td>
          <td class="aligntop cursoradd">';

$extra_names = get_site_extras_names( EXTRA_DISPLAY_REPORT );
if ( count ( $extra_names ) > 0 )
  echo '
            <label>' . translate ( 'Site Extras' ) . '</label><br />';

foreach ( $extra_names as $name ) {
  print_options ( 'event_template', 'extra:' . $name );
}

echo '
          </td>
        </tr>
        <tr>
          <td colspan="4">
            <input type="submit" value="' . translate ( 'Save' ) . '" />'
 . ( $adding_report ? '' : '&nbsp;&nbsp;
            <input type="submit" name="delete" value="'
   . translate ( 'Delete' ) . '" onclick="return confirm( \''
   . translate( 'Are you sure you want to delete this report?' )
   . '\');" />' );

?>
          </td>
        </tr>
      </table>
    </form>
    <script>
<!-- <![CDATA[
    // This script borrowed from phpMyAdmin with some mofification.
      function addMe ( areaname, myValue ) {
        var textarea = document.reportform.elements[areaname];
        // IE support.
        if ( document.selection ) {
          textarea.focus();
          sel = document.selection.createRange();
          sel.text = myValue;
        }
        // MOZILLA/NETSCAPE support.
        else if ( textarea.selectionStart || textarea.selectionStart == '0' ) {
          var
            startPos = textarea.selectionStart,
            endPos = textarea.selectionEnd;

          textarea.value = textarea.value.substring ( 0, startPos ) + myValue
            + textarea.value.substring ( endPos, textarea.value.length );
        }
        else {
          textarea.value += myValue;
        }
      }
//]]> -->
    </script>
<?php echo print_trailer();

?>
