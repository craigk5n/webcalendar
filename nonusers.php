<?php
include_once 'includes/init.php';
print_header();

if ( ! $is_admin ) {
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Error") .
    "</FONT></H2>" . translate("You are not authorized") . ".\n";
  print_trailer ();
  echo "</BODY></HTML>\n";
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo "<H2><FONT COLOR=\"$H2COLOR\">" . translate("Error") .
    "</FONT></H2>" . translate("NONUSER_PREFIX not set") . ".\n";
  print_trailer ();
  echo "</BODY></HTML>\n";
  exit;
}
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("NonUser")?></FONT></H2>

<?php
// Adding/Editing nonuser calendar
if ( ( ( $add == '1' ) || ( ! empty ( $nid ) ) ) && empty ( $error ) ) {
  $userlist = get_my_users ();
  $button = translate("Add");
  $nid = clean($nid);
  ?>
  <FORM ACTION="nonusers_handler.php" METHOD="POST">
  <?php
  if ( ! empty ( $nid ) ) {
    nonuser_load_variables ( $nid, 'nonusertemp_' );
    $id_display = "$nid <INPUT NAME=\"nid\" TYPE=\"hidden\" VALUE=\"$nid\">";
    $button = translate("Save");
    $nonusertemp_login = substr($nonusertemp_login, strlen($NONUSER_PREFIX));
  } else {
    $id_display = "<INPUT NAME=\"nid\" SIZE=\"20\" MAX=\"20\">";
  }
  ?>
  <table>
  <tr><td><?php etranslate("Calendar ID")?>:</td><td> <?php echo $id_display ?></td></tr>
  <tr><td><?php etranslate("First Name")?>:</td><td> <INPUT NAME="nfirstname" SIZE="20" MAX="25" VALUE="<?php echo htmlspecialchars ( $nonusertemp_firstname ); ?>"></td></tr>
  <tr><td><?php etranslate("Last Name")?>:</td><td> <INPUT NAME="nlastname" SIZE="20" MAX="25" VALUE="<?php echo htmlspecialchars ( $nonusertemp_lastname ); ?>"></td></tr>
  <tr><td><?php etranslate("Admin")?>:</td><td><SELECT NAME="nadmin">
  <?php
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<OPTION VALUE=\"".$userlist[$i]['cal_login']."\"";
    if ($nonusertemp_admin == $userlist[$i]['cal_login'] ) echo " SELECTED";
    echo ">".$userlist[$i]['cal_fullname']."\n";
  }
  ?>
  </SELECT></td></tr>
  </table>

  <BR><BR>
  <INPUT TYPE="submit" NAME="action" VALUE="<?php echo $button;?>">
  <?php if ( ! empty ( $nid ) ) {  ?>
    <INPUT TYPE="submit" NAME="action" VALUE="<?php etranslate("Delete");?>" ONCLICK="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')">
  <?php }  ?>
  </FORM>
  <?php
} else if ( empty ( $error ) ) {
  // Displaying NonUser Calendars
  $userlist = get_nonuser_cals ();
  if ( ! empty ( $userlist ) ) {
    echo "<UL>";
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      echo "<LI><A HREF=\"nonusers.php?nid=" . $userlist[$i]["cal_login"] . "\">"
          . $userlist[$i]['cal_fullname'] . "</A></LI>\n";
    }
    echo "</UL>";
  }
  echo "<P><A HREF=\"nonusers.php?add=1\">" . translate("Add New NonUser Calendar") . "</A></P><BR>\n";
}
?>

<?php print_trailer(); ?>
</BODY>
</HTML>
