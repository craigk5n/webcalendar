<?php
include_once 'includes/init.php';
print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR; ?>"><?php etranslate("View Another User's Calendar"); ?></H2></FONT>

<?php
if (( $allow_view_other != "Y" && ! $is_admin ) ||
   ( $public_access == "Y" && $login == "__public__" && $public_access_others != "Y")) {
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $error ) ) {
  echo "<BLOCKQUOTE>$error</BLOCKQUOTE>\n";
} else {
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
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

<?php print_trailer(); ?>
</BODY>
</HTML>
