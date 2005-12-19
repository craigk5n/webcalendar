<?php
/*
 * $Id$
 *
 * Page Description:
 * This page will present the user with forms for submitting
 * a data file to import.
 *
 * Input Parameters:
 * None
 *
 * Comments:
 * Might be nice to allow user to set the category for all imported
 * events.  So, a user could easily export events from the work
 * calendar and import them into WebCalendar with a category
 * "work".
 */
include_once 'includes/init.php';
$BodyX = 'onload="toggle_import()"';
$INC = array('js/export_import.php', 'js/visible.php');
print_header($INC, '', $BodyX);

// Generate the selection list for calendar user selection.
// Only ask for calendar user if user is an administrator.
// We may enhance this in the future to allow
// - selection of more than one user
// - non-admin users this functionality
function print_user_list () {
  global $single_user, $is_admin, $NONUSER_ENABLED, $login,
    $is_nonuser_admin, $is_assistant;

  if ( $single_user == "N" && $is_admin ) {
    $userlist = get_my_users ();
    if ($NONUSER_ENABLED == "Y" ) {
      $nonusers = get_nonuser_cals ();
      $userlist = ( ! empty ( $NONUSER_AT_TOP ) && $NONUSER_AT_TOP == "Y") ?
        array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
    }
    $num_users = 0;
    $size = 0;
    $users = "";
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      $l = $userlist[$i]['cal_login'];
      $size++;
      $users .= "<option value=\"" . $l . "\"";
      if ( ! empty ( $id ) && $id > 0 ) {
        if ( ! empty ( $participants[$l] ) )
          $users .= " selected=\"selected\"";
      } else {
        if ( $l == $login && ! $is_assistant  && ! $is_nonuser_admin )
          $users .= " selected=\"selected\"";
      }
      $users .= ">" . $userlist[$i]['cal_fullname'] . "</option>\n";
    }
  
    if ( $size > 50 )
      $size = 15;
    else if ( $size > 5 )
      $size = 5;
    print "<tr><td style=\"vertical-align:top;\">\n";
    print "<label for=\"caluser\">" . 
     translate("Calendar") . "</label></td><td>\n";
    print "<select name=\"calUser\" id=\"caluser\" size=\"$size\">$users\n";
    print "</select>\n";
    print "</td></tr>\n";
  }
}
?>

<h2><?php etranslate("Import")?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_import.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400');" /></h2>

<?php
$upload = ini_get ( "file_uploads" );
$upload_enabled = ! empty ( $upload ) &&
  preg_match ( "/(On|1|true|yes)/i", $upload );
if ( ! $upload_enabled ) {
  // The php.ini file does not have file_uploads enabled, so we will
  // not receive the uploaded import file.
  // Note: do not translate "php.ini file_uploads" since these
  // are the filename and config name.
  echo "<p>" . translate ( "Disabled" ) . " (php.ini file_uploads)</p>\n";
} else {
  // file uploads enabled
?>
<form action="import_handler.php" method="post" name="importform"  enctype="multipart/form-data" onsubmit="return checkExtension()">
<table style="border-width:0px;">
<tr><td>
 <label for="importtype"><?php etranslate("Import format")?>:</label></td><td>
  <select name="ImportType" id="importtype" onchange="toggle_import()">
   <option value="ICAL">iCal</option>
   <option value="PALMDESKTOP">Palm Desktop</option>
   <option value="VCAL">vCal</option>
      <option value="OUTLOOKCSV">Outlook (CSV)</option>
  </select>
</td></tr>
<!-- Valid only for Palm Desktop import -->
<tr id="palm"><td>
 <label><?php etranslate("Exclude private records")?>:</label></td><td>
 <label><input type="radio" name="exc_private" value="1" checked="checked" /><?php etranslate("Yes")?></label> 
 <label><input type="radio" name="exc_private" value="0" /><?php etranslate("No")?></label>
</td></tr>
<!-- /PALM -->
<!-- Not valid for Outlook CSV as it doesn't generate UID for import tracking -->
<tr id="ivcal"><td>
 <label><?php etranslate("Overwrite Prior Import")?>:</label></td><td>
 <label><input type="radio" name="overwrite" value="Y" checked="checked" />&nbsp;<?php etranslate("Yes");?></label> 
 <label><input type="radio" name="overwrite" value="N" />&nbsp;<?php etranslate("No");?></label>
</td></tr>
<!-- /IVCAL -->
<tr id="outlookcsv"><td colspan="2">
 <label><?php etranslate("Repeated items are imported separately. Prior imports are not overwritten.")?></label></td><td>
</td></tr>
<tr class="browse"><td>
 <label for="fileupload"><?php etranslate("Upload file");?>:</label></td><td>
 <input type="file" name="FileName" id="fileupload" size="45" maxlength="50" />
</td></tr>
<?php print_user_list(); ?>
</table>
<br /><input type="submit" value="<?php etranslate("Import")?>" />
</form>
<?php } print_trailer (); ?>
</body>
</html>
