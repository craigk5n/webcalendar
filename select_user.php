<?php
include_once 'includes/init.php';
print_header();
?>

<h2><?php etranslate("View Another User's Calendar"); ?></h2>

<?php
if (( $allow_view_other != "Y" && ! $is_admin ) ||
   ( $public_access == "Y" && $login == "__public__" && $public_access_others != "Y")) {
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $error ) ) {
  echo "<blockquote>$error</blockquote>\n";
} else {
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  ?>
  <form action="<?php echo $STARTVIEW;?>.php" method="get" name="SelectUser">
  <select name="user" onchange="document.SelectUser.submit()">
  <?php
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    echo "<option value=\"".$userlist[$i]['cal_login']."\">".$userlist[$i]['cal_fullname']."</option>\n";
  }
  ?>
  </select>
  <input type="submit" value="<?php etranslate("Go")?>" /></form>
  <?php
}

?>
<br /><br />

<?php print_trailer(); ?>
</body>
</html>
