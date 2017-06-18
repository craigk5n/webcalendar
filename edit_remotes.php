<?php
/* Allows a user to specify a remote calendar by URL that can be imported
 * manually into the NUC calendar specified. The user will also be allowed to
 * create a layer to display this calendar on top of their own calendar.
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: edit_remotes.php,v 1.17.2.4 2007/11/12 20:47:48 umcesrjones Exp $
 * @package WebCalendar
 * @subpackage Edit Remotes
 *
 * Security
 * $REMOTES_ENABLED must be enabled under System Settings and if
 * if UAC is enabled, then the user must be allowed to ACCESS_IMPORT.
*/
include_once 'includes/init.php';
print_header ( array ( 'js/edit_remotes.php/false', 'js/visible.php' ),
  '', '', true );

$error = '';

if ( ! $NONUSER_PREFIX )
  $error = translate ( 'NONUSER_PREFIX not set' );

if ( $REMOTES_ENABLED != 'Y' || ( access_is_enabled () && !
      access_can_access_function ( ACCESS_IMPORT ) ) )
  $error = print_not_auth (11);

if ( $error ) {
  echo print_error ( $error ) . '
  </body>
</html>';
  exit;
}
$add = getValue ( 'add' );
$nid = getValue ( 'nid' );

// Adding/Editing remote calendar.
if ( ( $add == '1' || ! empty ( $nid ) ) && empty ( $error ) ) {
  $userlist = get_nonuser_cals ( $login, true );

  if ( empty ( $nid ) ) {
    $id_display = '<input type="text" name="nid" id="nid" size="20" '
     . 'maxlength="20" onchange="check_name();" /> '
     . translate ( 'word characters only' );
    $lableStr = translate ( 'Add Remote Calendar' );
  } else {
    $nid = clean_html ( $nid );
    nonuser_load_variables ( $nid, 'remotestemp_' );

    $button = translate ( 'Save' );
		$buttonAction = 'Save';
    $id_display = $nid . ' <input type="hidden" name="nid" id="nid" value="'
     . $nid . '" />';
    $lableStr = translate ( 'Edit Remote Calendar' );
    $remotestemp_login = substr ( $remotestemp_login, strlen ( $NONUSER_PREFIX ) );
  }

  $button = translate ( 'Add' );
	$buttonAction = 'Add';
  $calIdStr = translate ( 'Calendar ID' );
  $colorStr = translate ( 'Color' );
  $confirmStr = str_replace ( 'XXX', translate ( 'entry' ),
    translate ( 'Are you sure you want to delete this XXX?' ) );
  $createLayerStr = translate ( 'Create Layer' );
  $deleteStr = translate ( 'Delete' );
  $firstNameStr = translate ( 'First Name' );
  $lastNameStr = translate ( 'Last Name' );
  $reloadStr = translate ( 'Reload' );
  $requiredStr = translate ( 'Required to View Remote Calendar' );
  $selectStr = translate ( 'Select' );
  $urlStr = translate ( 'URL' );

  $firstNameValue = ( empty ( $remotestemp_firstname )
    ? '' : htmlspecialchars ( $remotestemp_firstname ) );
  $lastNameValue = ( empty ( $remotestemp_lastname )
    ? '' : htmlspecialchars ( $remotestemp_lastname ) );
  $urlValue = ( empty ( $remotestemp_url )
    ? '' : htmlspecialchars ( $remotestemp_url ) );

  echo <<<EOT
    <h2>{$lableStr}</h2>
    <form action="edit_remotes_handler.php" method="post" name="prefform"
      onsubmit="return valid_form( this );">
      <table cellspacing="0" cellpadding="2">
        <tr>
          <td><label for="calid">{$calIdStr}:</label></td>
          <td colspan="3">{$id_display}</td>
        </tr>
        <tr>
          <td><label for="nfirstname">{$firstNameStr}:</label></td>
          <td colspan="3"><input type="text" name="nfirstname" id="nfirstname"
            size="20" maxlength="25" value="{$firstNameValue}" /></td>
        </tr>
        <tr>
          <td><label for="nlastname">{$lastNameStr}:</label></td>
          <td colspan="3"><input type="text" name="nlastname" id="nlastname"
            size="20" maxlength="25" value="{$lastNameValue}" /></td>
        </tr>
        <tr>
          <td><label for="nurl">{$urlStr}:</label></td>
          <td colspan="3"><input type="text" name="nurl" id="nurl" size="75"
            maxlength="255" value="{$urlValue}" /></td>
        </tr>
EOT;
  if ( empty ( $nid ) ) {
    echo <<<EOT
        <tr>
          <td><label for="nlayer">{$createLayerStr}:</label></td>
          <td colspan="3">
            <input type="hidden" name="reload" id="reload" value="true" />
            <input type="checkbox" name="nlayer" id="nlayer" value="Y"
              onchange="toggle_layercolor();" />{$requiredStr}
          </td>
        </tr>
        <tr id="nlayercolor" style="visibility:hidden" >
          <td>
EOT;
    echo print_color_input_html ( 'layercolor', $colorStr, '#000000' ) . '
          </td>
        </tr>';
  }
  echo <<<EOT
      </table>
      <input type="hidden" name="nadmin" id="nadmin" value="{$login}" />
      <input type="submit" name="{$buttonAction}" value="{$button}" />
EOT;

  if ( ! empty ( $nid ) )
    echo <<<EOT
      <input type="submit" name="delete" value="{$deleteStr}"
        onclick="return confirm( '{$confirmStr}' )" />
      <input type="submit" name="reload" value="{$reloadStr}" />
EOT;

  echo '
    </form>';
}
echo print_trailer ( false, true, true );

?>
