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
?>


<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Users")?></FONT></H2>

<ul>
<?php
$userlist = user_get_users ();
for ( $i = 0; $i < count ( $userlist ); $i++ ) {
  if ( $userlist[$i]['cal_login'] != '__public__' ) {
    echo "<li><a href=\"edit_user.php?user=" . $userlist[$i]["cal_login"] .
      "\">";
    echo $userlist[$i]['cal_fullname'];
    echo "</a>";
    if (  $userlist[$i]["cal_is_admin"] == 'Y' )
      echo "<sup>*</sup>";
    echo " </li>\n";
  }
}
?>
</ul>
<sup>*</sup> <?php etranslate("denotes administrative user")?>
<P>
<?php
  if ( $admin_can_add_user )
    echo "<A HREF=\"edit_user.php\">" . translate("Add New User") .
      "</A><BR>\n";
?>

<?php print_trailer(); ?>
</BODY>
</HTML>
