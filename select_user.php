<?php
include_once 'includes/init.php';
print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR; ?>"><?php etranslate("View Another User's Calendar"); ?></H2></FONT>

<?php
if ( $allow_view_other != "Y" && ! $is_admin ) {
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $error ) ) {
  echo "<BLOCKQUOTE>$error</BLOCKQUOTE>\n";
} else {
  $userlist = get_my_users ();
  $nonusers = get_nonuser_cals ();
  $userlist = $nonusers + $userlist;
  ?>
  <FORM ACTION="<?php echo $STARTVIEW;?>.php" METHOD="GET" NAME="SelectUser">
  <SELECT NAME="user" ONCHANGE="document.SelectUser.submit()">
  <?php
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<OPTION VALUE=\"".$userlist[$i]['cal_login']."\">".$userlist[$i]['cal_fullname']."\n";
  }
  ?>
  </SELECT>
  <INPUT TYPE="submit" VALUE="<?php etranslate("Go")?>"></FORM>
  <?php
}

?>
<P>

<?php include_once "./includes/trailer.php"; ?>
</BODY>
</HTML>