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
      $url .= "?&date=$date";
    do_redirect ( $url );
  }
}

print_header();
?>

<?php if ( ! empty ( $error ) ) { ?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php } else { ?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Set Category")?></H2></FONT>

<FORM ACTION="set_entry_cat.php" METHOD="POST" NAME="SelectCategory">

<INPUT TYPE="hidden" NAME="date" VALUE="<?php echo $date?>">
<INPUT TYPE="hidden" NAME="id" VALUE="<?php echo $id?>">

<TABLE BORDER="0" CELLPADDING="5">

<TR>
<TD VALIGN="top"><B><?php etranslate("Brief Description")?>:</B></TD>
<TD VALIGN="top"><?php echo $event_name; ?></TD></TR>

<TR><TD VALIGN="top"><B><?php etranslate("Category")?>:</B>&nbsp;&nbsp;</TD>
<TD VALIGN="top"><SELECT NAME="cat_id">
  <OPTION VALUE="NULL"><?php etranslate("None")?></OPTION>
  <?php
    foreach ( $categories as $K => $V ) {
      if ( $K == $cur_cat )
        echo "<OPTION VALUE=\"$K\" SELECTED>$V</OPTION>\n";
      else
        echo "<OPTION VALUE=\"$K\">$V</OPTION>\n";
    }
  ?>
  </SELECT></TD>
</TR>

<TR><TD VALIGN="top" COLSPAN="2">
<INPUT TYPE="submit" VALUE="<?php etranslate("Save");?>">
</TD></TR>
</TABLE>

</FORM>

<?php } ?>

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>