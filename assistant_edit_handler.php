<?php
include_once 'includes/init.php';

$error = "";
if ($user != $login)
  $user = ( ($is_admin || $is_nonuser_admin) && $user ) ? $user : $login;

# update user list
dbi_query ( "DELETE FROM webcal_asst WHERE cal_boss = '$user'" );
for ( $i = 0; $i < count ( $users ); $i++ ) {
  dbi_query ( "INSERT INTO webcal_asst ( cal_boss, cal_assistant ) " .
    "VALUES ( '$user', '$users[$i]' )" );
}

$url = "assistant_edit.php";
if (($is_admin || $is_nonuser_admin) && $login != $user )
   $url = $url . (strpos($url, "?") === false ? "?" : "&amp;") . "user=$user";
do_redirect ( $url );

print_header();
?>
<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<br /><br /><span style=\"font-weight:bold;\">SQL:</span> $sql";
//?>
</blockquote>

<?php print_trailer(); ?>
</body>
</html>
