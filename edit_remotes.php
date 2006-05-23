<?php
/* $Id$ 
 * 
 * Allows a user to specify a remote calendar by URL that can
 * be imported manually into the NUC calendar specified. The user
 * will also be allowed to create a layer to display this calendar 
 * on top of their own calendar.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 * @subpackage Reports
 *
 * Security
 * $REMOTES_ENABLED must be enabled under System Settings and if
 * if UAC is enabled, then the user must be allowed to ACCESS_IMPORT 
*/
include_once 'includes/init.php';
$INC = array('js/edit_remotes.php/false', 'js/visible.php/true');
print_header( $INC, '', '', true );

$error = '';

if ( ! $NONUSER_PREFIX ) {
  $error = translate( 'NONUSER_PREFIX not set' );
}

 if ($REMOTES_ENABLED != 'Y'  || ( access_is_enabled () && 
  ! access_can_access_function ( ACCESS_IMPORT ) ) ) {
  $error = translate ( 'You are not authorized' );
}

if ( $error ) {
  echo '<h2>' . translate( 'Error' ) . "</h2>\n" . $error . ".\n";
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( 'add' );
$nid = getValue ( 'nid' );

// Adding/Editing remote calendar
if (( ($add == '1') || (! empty ($nid)) ) && empty ($error)) {
  $userlist = get_nonuser_cals ( $login, true);
  $button = translate( 'Add' );
  $nid = clean_html($nid);

if ( ! empty ( $nid ) ) {
  nonuser_load_variables ( $nid, 'remotestemp_' );
  $lableStr = translate( 'Edit Remote Calendar' );
  $id_display = "$nid <input type=\"hidden\" name=\"nid\" id=\"nid\" value=\"$nid\" />";
  $button = translate( 'Save' );
  $remotestemp_login = substr($remotestemp_login, strlen($NONUSER_PREFIX));
} else {
  $lableStr = translate( 'Add Remote Calendar' );
  $id_display = '<input type="text" name="nid" id="nid" size="20" maxlength="20" onchange="check_name();" /> ' . translate ( 'word characters only' );
}
$calIdStr = translate( 'Calendar ID' );
$firstNameStr = translate( 'First Name' );
$lastNameStr = translate( 'Last Name' );
$firstNameValue = ( empty ( $remotestemp_firstname ) ? '' : htmlspecialchars ( $remotestemp_firstname ) );
$lastNameValue = ( empty ( $remotestemp_lastname ) ? '' : htmlspecialchars ( $remotestemp_lastname ) );
$urlStr = translate( 'URL' );
$urlValue = ( empty ( $remotestemp_url ) ? '' : htmlspecialchars ( $remotestemp_url ) );
$createLayerStr = translate( 'Create Layer' );
$requiredStr = translate( 'Required to View Remote Calendar' );
$colorStr = translate( 'Color' );
$selectStr = translate( 'Select' );
$deleteStr = translate( 'Delete' );
$confirmStr = translate( 'Are you sure you want to delete this entry?', true);
$reloadStr = translate( 'Reload' );

echo <<<EOT
  <h2>{$lableStr}</h2>
  <form action="edit_remotes_handler.php" method="post"  name="prefform" onsubmit="return valid_form(this);">
  <table>
    <tr><td>
      <label for="calid">{$calIdStr}:</label></td>
      <td>{$id_display}</td></tr>
    <tr><td>
      <label for="nfirstname">[$firstNameStr}:</label></td><td>
      <input type="text" name="nfirstname" id="nfirstname" size="20" maxlength="25" value="{$firstNameValue}" /></td></tr>
    <tr><td>
      <label for="nlastname">{$lastNameStr}:</label></td><td>
      <input type="text" name="nlastname" id="nlastname" size="20" maxlength="25" value="{$lastNameValue}" /></td></tr>
    <tr><td>
      <label for="url">{$urlStr}:</label></td><td>
      <input type="text" name="nurl" id="nurl" size="75" maxlength="255" value="{$urlValue}" /></td></tr>
EOT;
if ( empty ( $nid ) ) {
echo <<<EOT
   <tr><td>
     <label for="url">{$createLayerStr}:</label></td><td>
     <input type="checkbox" name="nlayer"  value="Y"  onchange="toggle_layercolor();"/>{$requiredStr}</td></tr>
   <tr id="nlayercolor" style="visibility:hidden" ><td>
     <label for="layercolor">{id$colorStr}:</label></td><td>
     <input type="text" name="layercolor" id="layercolor" size="7" maxlength="7" value="" />
     <input type="button" onclick="selectColor('layercolor')" value="{$selectStr}..." /></td></tr> 
EOT;
}
echo <<<EOT
  </table>
  <input type="hidden" name="nadmin" id="nadmin" value="{$login}" />
  <input type="submit" name="action" value="{$button}" />
EOT;
if ( ! empty ( $nid ) ) {
echo <<<EOT
  <input type="submit" name="delete" value="{$deleteStr}" onclick="return confirm('{$confirmStr}')" />
  <input type="submit" name="reload" value="{$reloadStr}" />
EOT;
}  ?>
</form>
<?php } 
print_trailer ( false, true, true ); ?>
</body>
</html>
