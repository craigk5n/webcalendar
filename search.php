<?php
/* $Id$ */

include_once 'includes/init.php';

// Determine if this user is allowed to search the calendar of other users
$show_others = false; // show "Advanced Search"
if ( $single_user == 'Y' )
  $show_others = false;
if ( $is_admin )
  $show_others = true;
else if ( access_is_enabled () )
  $show_others = access_can_access_function ( ACCESS_ADVANCED_SEARCH );
else if ( $login != '__public__' && ! empty ( $allow_view_other ) &&
  $allow_view_other == 'Y' )
  $show_others = true;
else if ( $login == '__public__' && ! empty ( $public_access_others ) &&
  $public_access_others == 'Y' )
  $show_others = true;

if ( $show_others ) {
  $INC = array ('js/search.php');
} else {
  $INC = '';
}
print_header ( $INC );

?>
<h2><?php etranslate("Search"); ?></h2>

<form action="search_handler.php" method="post" name="searchformentry" style="margin-left:13px;">

<label for="keywordsadv"><?php etranslate("Keywords")?>:&nbsp;</label>
<input type="text" name="keywords" id="keywordsadv" size="30" />&nbsp;
<input type="submit" value="<?php etranslate("Search")?>" /><br />
<?php 
if ( ! $show_others ) {
  echo "</form>";
} else {
?>
<br/><br/>
<div id="advlink"><a title="<?php etranslate("Advanced Search");?>"
   href="javascript:show('adv'); hide('advlink');"><?php
    etranslate("Advanced Search");?></a></div>
<table id="adv" style="display:none;">
<tr><td style="vertical-align:top; text-align:right; font-weight:bold; width:60px;">
	<?php etranslate("Users"); ?>:&nbsp;</td><td>
<?php
  $users = get_my_users ();
  // Get non-user calendars (if enabled)
  if ( ! empty ( $nonuser_enabled ) && $nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    if ( ! empty ( $nonuser_at_top ) && $nonuser_at_top == "Y" )
      $users = array_merge ( $nonusers, $users );
    else
      $users = array_merge ( $users, $nonusers );
  }
  $size = 0;
  $out = "";
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $out .= "<option value=\"" . $users[$i]['cal_login'] . "\"";
    if ( $users[$i]['cal_login'] == $login )
      $out .= " selected=\"selected\"";
    $out .= ">" . $users[$i]['cal_fullname'] . "</option>\n";
  }
  if ( count ( $users ) > 50 )
    $size = 15;
  else if ( count ( $users ) > 10 )
    $size = 10;
  else
    $size = count ( $users );
?>
<select name="users[]" size="<?php echo $size;?>" multiple="multiple"><?php echo $out; ?></select>
<?php 
  if ( $groups_enabled == "Y" ) {
   echo "<input type=\"button\" onclick=\"selectUsers()\" value=\"" .
      translate("Select") . "...\" />\n";
  }
?>
</td></tr>
</table>
</form>
<?php } ?>

<?php print_trailer(); ?>
</body>
</html>
