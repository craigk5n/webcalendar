<?php
include_once 'includes/init.php';
print_header();
?>

<h2><?php etranslate("View Another User's Calendar"); ?></h2>

<?php
if (( $ALLOW_VIEW_OTHER != "Y" && ! $is_admin ) ||
   ( $PUBLIC_ACCESS == "Y" && $login == "__public__" && $PUBLIC_ACCESS_OTHERS != "Y")) {
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $error ) ) {
  echo "<blockquote>$error</blockquote>\n";
} else {
  $userlist = get_my_users ();
  if ($NONUSER_ENABLED == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($NONUSER_AT_TOP == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  if ( strstr ( $STARTVIEW, "view" ) )
    $url = "month.php";
  else {
    $url = $STARTVIEW;
    if ( $url == "month" || $url == "day" || $url == "week" || $url == "year" )
      $url .= ".php";
  }
  ?>
  <form action="<?php echo $url;?>" method="get" name="SelectUser">
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
