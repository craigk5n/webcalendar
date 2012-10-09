<?php /* $Id$ */
/**
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

/**
 * Generate the selection list for calendar user selection.
 * Only ask for calendar user if user is an administrator.
 *
 * We may enhance this in the future to allow:
 *  - selection of more than one user
 *  - non-admin users this functionality
 */
function print_user_list() {
  global $is_admin, $is_assistant, $is_nonuser_admin, $login,
  $NONUSER_AT_TOP, $NONUSER_ENABLED, $option, $single_user;

  if ( $single_user == 'N' && $is_admin ) {
    $userlist = user_get_users();
    if ( $NONUSER_ENABLED == 'Y' ) {
      $nonusers = get_nonuser_cals();
      $userlist = ( ! empty ( $NONUSER_AT_TOP ) && $NONUSER_AT_TOP == 'Y' )
      ? array_merge ( $nonusers, $userlist ) : array_merge ( $userlist, $nonusers );
    }
    $num_users = $size = 0;
    $users = '';
    foreach ( $userlist as $i ) {
      $l = $i['cal_login'];
      $size++;
      $users .= $option . $l . ( $l == $login && ! $is_assistant && ! $is_nonuser_admin
        ? '" selected>' : '">' ) . $i['cal_fullname'] . '</option>';
    }

    if ( $size > 50 )
      $size = 15;
    elseif ( $size > 5 )
      $size = 5;

    echo '
        <tr>
          <td class="aligntop"><label for="caluser">' . translate( 'Calendar_' )
     . '</label></td>
          <td>
            <select name="calUser" id="caluser" size="' . $size . '">' . $users . '
            </select>
          </td>
        </tr>';
  }
}

function print_categories() {
  global $categories, $CATEGORIES_ENABLED, $option;

  if ( $CATEGORIES_ENABLED = 'Y' ) {
    $cat_options = $option . '__import" selected>'
     . translate('import from file') . '</option>';

    load_user_categories();

    $size = 0;
    foreach ( $categories as $i => $value ) {
      if ( $i != 0 ) {
        $l = $categories[$i]['cat_name'];
        $size++;
        $cat_options .= $option . $l . '">' . $l . '</option>';
      }
    }

    if ( $size > 50 )
      $size = 15;
    elseif ( $size > 5 )
      $size = 5;

    echo '
        <tr>
          <td class="aligntop"><label for="importcat">'
     . $cat_Str . '</label></td>
          <td>
            <select id="importcat" name="importcat" size="' . $size . '">'
     . $cat_options . '
            </select>
          </td>
        </tr>';
  }
}

$upload = ini_get ( 'file_uploads' );
$upload_enabled = ( ! empty( $upload )
   && preg_match( '/(On|1|true|yes)/i', $upload ) );

ob_start();
print_header();
echo '
    <h2>' . translate ( 'Import' ) . '&nbsp;<img src="images/help.gif" alt="'
 . translate( 'Help' ) . '"></h2>';

if ( ! $upload_enabled )
  // The php.ini file does not have file_uploads enabled,
  // so we will not receive the uploaded import file.
  // Note: do not translate "php.ini file_uploads"
  // since these are the filename and config name.
  echo '
    <p>' . str_replace( 'XXX', 'php.ini file_uploads',
      translate ( 'Disabled XXX' ) ) . '</p>';
else {
  // File uploads enabled.
  echo '
    <form action="import_handler.php" method="post" id="importform" '
   . ' name="importform" enctype="multipart/form-data">
      <table summary="">
        <tr>
          <td><label for="importtype">' . translate( 'Import format' )
   . '</label></td>
          <td>
            <select id="importtype" name="ImportType">'
   . $option . 'ICAL">iCal</option>'
   . $option . 'PALMDESKTOP">Palm Desktop</option>'
   . $option . 'VCAL">vCal</option>'
   . $option . 'OUTLOOKCSV">Outlook (CSV)</option>
            </select>
          </td>
        </tr>
<!-- Valid only for Palm Desktop import. -->
        <tr id="palm">
          <td><label>' . translate( 'Exclude private records' ) . '</label></td>
          <td>
            <label><input type="radio" name="exc_private" value="1" checked>'
   . $yesStr . '</label>
            <label><input type="radio" name="exc_private" value="0">'
   . $noStr . '</label>
          </td>
        </tr>
<!-- /PALM -->
<!-- Not valid for Outlook CSV as it doesn\'t generate UID for import tracking. -->
        <tr id="ivcal">
          <td><label>' . translate ( 'Overwrite Prior Import_' ) . '</label></td>
          <td>
            <label><input type="radio" name="overwrite" value="Y" checked>&nbsp;'
   . $yesStr . '</label>
            <label><input type="radio" name="overwrite" value="N">&nbsp;'
   . $noStr . '</label>
          </td>
        </tr>
<!-- /IVCAL -->
        <tr id="outlookcsv">
          <td colspan="2"><label>'
   . translate ( 'Repeat items imported separately' ) . '</label></td>
        </tr>
        <tr class="browse">
          <td><label for="fileupload">' . translate( 'Upload file_' )
   . '</label></td>
          <td><input type="file" name="FileName" id="fileupload" size="45" '
   . 'maxlength="50"></td>
        </tr>';
  print_user_list();
  print_categories();
  echo '
      </table><br>
      <input type="submit" value="' . translate( 'Import' ) . '">
    </form>';
}
echo print_trailer();
ob_end_flush();

?>
