<?php
include_once 'includes/init.php';
print_header();

if ( ! $is_admin ) {
  echo "<h2>" . translate("Error") .
    "</h2>" . translate("You are not authorized") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}
?>

<h2><?php etranslate("Users")?></h2>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />

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
    echo "</li>\n";
  }
}
?>
</ul>
<sup>*</sup> <?php etranslate("denotes administrative user")?>
<br /><br />
<?php
  if ( $admin_can_add_user )
    echo "<a href=\"edit_user.php\">" . translate("Add New User") .
      "</a><br />\n";
?>

<?php print_trailer(); ?>
</body>
</html>
