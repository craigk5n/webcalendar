<?php
include_once 'includes/init.php';
load_user_categories();

$error = "";

if ( empty ( $id ) )
  $error = translate("Invalid entry id") . ".";
else if ( $categories_enabled != "Y" )
  $error = translate("You are not authorized") . ".";
else if ( empty ( $categories ) )
  $error = translate("You have not added any categories") . ".";

// make sure user is a participant
$res = dbi_query ( "SELECT cal_category, cal_status FROM webcal_entry_user " .
  "WHERE cal_id = $id AND cal_login = '$login'" );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    if ( $row[1] == "D" ) // User deleted themself
      $error = translate("You are not authorized") . ".";
    $cur_cat = $row[0];
  } else {
    // not a participant for this event
    $error = translate("You are not authorized") . ".";
  }
  dbi_free_result ( $res );
} else {
  $error = translate("Database error") . ": " . dbi_error ();
}

// Get event name and make sure event exists
$event_name = "";
$res = dbi_query ( "SELECT cal_name FROM webcal_entry " .
  "WHERE cal_id = $id" );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $event_name = $row[0];
  } else {
    // No such event
    $error = translate("Invalid entry id") . ".";
  }
} else {
  $error = translate("Database error") . ": " . dbi_error ();
}

// If this is the form handler, then save now
if ( ! empty ( $cat_id ) && empty ( $error ) ) {
  $sql = "UPDATE webcal_entry_user SET cal_category = $cat_id " .
    "WHERE cal_id = $id and cal_login = '$login'";
  if ( ! dbi_query ( $sql ) ) {
    $error = translate ( "Database error" ) . ": " . dbi_error ();
  } else {
    $url = "view_entry.php?id=$id";
    if ( ! empty ( $date ) )
      $url .= "&amp;date=$date";
    do_redirect ( $url );
  }
}

print_header();
?>

<?php if ( ! empty ( $error ) ) { ?>
<h2><?php etranslate("Error")?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php } else { ?>
<h2><?php etranslate("Set Category")?></h2>

<form action="set_entry_cat.php" method="post" name="SelectCategory">

<input type="hidden" name="date" value="<?php echo $date?>" />
<input type="hidden" name="id" value="<?php echo $id?>" />

<table style="border-width:0px;" cellpadding="5">
<tr style="vertical-align:top;"><td style="font-weight:bold;">
	<?php etranslate("Brief Description")?>:</td><td>
	<?php echo $event_name; ?>
</td></tr>
<tr style="vertical-align:top;"><td style="font-weight:bold;">
	<?php etranslate("Category")?>:&nbsp;</td><td>
	<select name="cat_id">
		<option value="NULL"><?php etranslate("None")?></option>
  <?php
    foreach ( $categories as $K => $V ) {
      if ( $K == $cur_cat )
        echo "<option value=\"$K\" selected=\"selected\">$V</option>\n";
      else
        echo "<option value=\"$K\">$V</option>\n";
    }
  ?>
	</select>
</td></tr>
<tr style="vertical-align:top;"><td colspan="2">
	<input type="submit" value="<?php etranslate("Save");?>" />
</td></tr>
</table>
</form>
<?php } ?>

<?php print_trailer(); ?>
</body>
</html>