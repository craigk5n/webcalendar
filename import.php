<?php
/* $Id: import.php,v 1.55.2.2 2007/08/06 02:28:30 cknudsen Exp $
 *
 * Page Description:
 * This page will present the user with forms for submitting a data file to import.
 *
 * Input Parameters:
 * None
 *
 * Comments:
 * Might be nice to allow user to set the category for all imported events.
 * So, a user could easily export events from the work calendar and import them
 * into WebCalendar with a category "work".
 */
include_once 'includes/init.php';

/* Generate the selection list for calendar user selection.
 * Only ask for calendar user if user is an administrator.
 *
 * We may enhance this in the future to allow:
 *  - selection of more than one user
 *  - non-admin users this functionality
 */
function print_user_list () {
  global $is_admin, $is_assistant, $is_nonuser_admin, $login,
  $NONUSER_AT_TOP, $NONUSER_ENABLED, $single_user;

  if ( $single_user == 'N' && $is_admin ) {
    $userlist = user_get_users ();
    if ( $NONUSER_ENABLED == 'Y' ) {
      $nonusers = get_nonuser_cals ();
      $userlist = ( ! empty ( $NONUSER_AT_TOP ) && $NONUSER_AT_TOP == 'Y' )
      ? array_merge ( $nonusers, $userlist ) : array_merge ( $userlist, $nonusers );
    }
    $num_users = $size = 0;
    $users = '';
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      $l = $userlist[$i]['cal_login'];
      $size++;
      $users .= '
              <option value="' . $l . '"'
       . ( $l == $login && ! $is_assistant && ! $is_nonuser_admin
        ? ' selected="selected"' : '' )
       . '>' . $userlist[$i]['cal_fullname'] . '</option>';
    }

    if ( $size > 50 )
      $size = 15;
    elseif ( $size > 5 )
      $size = 5;

    echo '
        <tr>
          <td class="aligntop"><label for="caluser">' . translate ( 'Calendar' )
     . ':</label></td>
          <td>
            <select name="calUser" id="caluser" size="' . $size . '">' . $users . '
            </select>
          </td>
        </tr>';
  }
}

$upload = ini_get ( 'file_uploads' );
$upload_enabled = ( ! empty ( $upload ) &&
  preg_match ( '/(On|1|true|yes)/i', $upload ) );

print_header ( array ( 'js/export_import.php', 'js/visible.php' ),
  '', 'onload="toggle_import();"' );
ob_start ();
echo '
    <h2>' . translate ( 'Import' ) . '&nbsp;<img src="images/help.gif" alt="'
 . translate ( 'Help' ) . '" class="help" onclick="window.open( '
 . "'help_import.php', 'cal_help', '"
 . 'dependent,menubar,scrollbars,height=400,width=400\' );" /></h2>';

if ( ! $upload_enabled )
  // The php.ini file does not have file_uploads enabled,
  // so we will not receive the uploaded import file.
  // Note: do not translate "php.ini file_uploads"
  // since these are the filename and config name.
  echo '
    <p>' . translate ( 'Disabled' ) . ' (php.ini file_uploads)</p>';
else {
  // File uploads enabled.
  $noStr = translate ( 'No' );
  $yesStr = translate ( 'Yes' );
  echo '
    <form action="import_handler.php" method="post" name="importform" '
   . 'enctype="multipart/form-data" onsubmit="return checkExtension()">
      <table>
        <tr>
          <td><label for="importtype">' . translate ( 'Import format' ) . ':</label></td>
          <td>
            <select name="ImportType" id="importtype" onchange="toggle_import()">
              <option value="ICAL">iCal</option>
              <option value="PALMDESKTOP">Palm Desktop</option>
              <option value="VCAL">vCal</option>
              <option value="OUTLOOKCSV">Outlook (CSV)</option>
            </select>
          </td>
        </tr>
<!-- Valid only for Palm Desktop import. -->
        <tr id="palm">
          <td><label>' . translate ( 'Exclude private records' ) . ':</label></td>
          <td>
            <label><input type="radio" name="exc_private" value="1" checked="checked" />'
   . $yesStr . '</label>
            <label><input type="radio" name="exc_private" value="0" />'
   . $noStr . '</label>
          </td>
        </tr>
<!-- /PALM -->
<!-- Not valid for Outlook CSV as it doesn\'t generate UID for import tracking. -->
        <tr id="ivcal">
          <td><label>' . translate ( 'Overwrite Prior Import' ) . ':</label></td>
          <td>
            <label><input type="radio" name="overwrite" value="Y" checked="checked" />&nbsp;'
   . $yesStr . '</label>
            <label><input type="radio" name="overwrite" value="N" />&nbsp;'
   . $noStr . '</label>
          </td>
        </tr>
<!-- /IVCAL -->
        <tr id="outlookcsv">
          <td colspan="2"><label>'
   . translate ( 'Repeated items are imported separately. Prior imports are not overwritten.' )
   . '</label></td>
        </tr>
        <tr class="browse">
          <td><label for="fileupload">' . translate ( 'Upload file' ) . ':</label></td>
          <td><input type="file" name="FileName" id="fileupload" size="45" '
   . 'maxlength="50" /></td>
        </tr>';
  print_user_list ();
  echo '
      </table><br />
      <input type="submit" value="' . translate ( 'Import' ) . '" />
    </form>';
}
ob_end_flush ();
echo print_trailer ();

?>
