<?php
/*
 * $Id$
 *
 * Page Description:
 *	This page displays the views that the user currently owns and
	* allows new ones to be created
 *
 * Input Parameters:
 *	id  - specify view id in webcal_view table
 * if blank, a new view is created
 *
 * Security:
 *	Must be owner of the viewto edit
 */
include_once 'includes/init.php';

$error = "";

if ( ! $is_admin )
  $user = $login;

$INC = array('js/views_edit.php');
print_header($INC);
?>

<form action="views_edit_handler.php" method="post" name="editviewform">
<?php
$newview = true;
$viewname = "";
$viewtype = "";

if ( empty ( $id ) ) {
  $viewname = translate("Unnamed View");
} else {
  // search for view by id
  for ( $i = 0; $i < count ( $views ); $i++ ) {
    if ( $views[$i]['cal_view_id'] == $id ) {
      $newview = false;
      $viewname = $views[$i]["cal_name"];
      if ( empty ( $viewname ) )
        $viewname = translate("Unnamed View");
      $viewtype = $views[$i]["cal_view_type"];
    }
  }
}

// If view_name not found, then the specified view id does not
// belong to current user. 
if ( empty( $viewname ) ) {
  $error = translate ( "You are not authorized" );
}

// get list of users for this view
if ( ! $newview ) {
  $sql = "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $viewuser[$row[0]] = 1;
      }
      dbi_free_result ( $res );
				} else {
      $error = translate ( "Database error" ) . ": " . dbi_error ();
    }
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}
	
if ( $newview ) {
  $v = array ();
  echo "<h2>" . translate("Add View") . "</h2>\n";
  echo "<input type=\"hidden\" name=\"add\" value=\"1\" />\n";
} else {
  echo "<h2>" . translate("Edit View") . "</h2>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
}
?>

<table style="border-width:0px;">
<tr><td>
	<label for="viewname"><?php etranslate("View Name")?>:</label></td><td>
	<input name="viewname" id="viewname" size="20" value="<?php echo htmlspecialchars ( $viewname );?>" />
</td></tr>
<tr><td>
	<label for="viewtype"><?php etranslate("View Type")?>:</label></td><td>
	<select name="viewtype" id="viewtype">
		<option value="D" <?php if ( $viewtype == "D" ) echo " selected=\"selected\"";?>><?php etranslate("Day"); ?></option>
		<option value="W" <?php if ( $viewtype == "W" ) echo " selected=\"selected\"";?>><?php etranslate("Week (Users horizontal)"); ?></option>
		<option value="V" <?php if ( $viewtype == "V" ) echo " selected=\"selected\"";?>><?php etranslate("Week (Users vertical)"); ?></option>
		<option value="S" <?php if ( $viewtype == "S" ) echo " selected=\"selected\"";?>><?php etranslate("Week (Timebar)"); ?></option>
		<option value="T" <?php if ( $viewtype == "T" ) echo " selected=\"selected\"";?>><?php etranslate("Month (Timebar)"); ?></option>
		<option value="M" <?php if ( $viewtype == "M" ) echo " selected=\"selected\"";?>><?php etranslate("Month (side by side)"); ?></option>
		<option value="L" <?php if ( $viewtype == "L" ) echo " selected=\"selected\"";?>><?php etranslate("Month (on same calendar)"); ?></option>
      </select>&nbsp;
<!--
  cek - commented out since preview-views.html is hard-coded to English
  and causes the download .tar.gz file to be 300k bigger.
  I will add this back when we can resolve these issues.
      <a class="nav" href="docs/preview-views.html" target="_blank">(<?php etranslate("preview"); ?>)</a>
-->
      </td></tr>
<tr><td valign="top">
	<label for="viewusers"><?php etranslate("Users"); ?>:</label></td><td>
	<select name="users[]" id="viewusers" size="10" multiple="multiple">
<?php
  // get list of all users
  $users = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $users = ($nonuser_at_top == "Y") ? array_merge($nonusers, $users) : array_merge($users, $nonusers);
  }
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $u = $users[$i]['cal_login'];
    echo "<option value=\"$u\"";
    if ( ! empty ( $viewuser[$u] ) ) {
      echo " selected=\"selected\"";
    }
    echo ">" . $users[$i]['cal_fullname'] . "</option>\n";
  }
?>
</select>
<?php if ( $groups_enabled == "Y" ) { ?>
	<input type="button" onclick="selectUsers()" value="<?php etranslate("Select");?>..." />
<?php } ?>
</td></tr>
<tr><td colspan="2" style="text-align:center;">
<br />
<input type="submit" name="action" value="<?php if ( $newview ) etranslate("Add"); else etranslate("Save"); ?>" />
<?php if ( ! $newview ) { ?>
	<input type="submit" name="action" value="<?php etranslate("Delete")?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')" />
<?php } ?>
</td></tr>
</table>

</form>

<?php print_trailer(); ?>
</body>
</html>
