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
   $url = $url . (strpos($url, "?") === false ? "?" : "&") . "user=$user";
do_redirect ( $url );

print_header();
?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></FONT></H2>

<BLOCKQUOTE>
<?php

echo $error;
//if ( $sql != "" )
//  echo "<P><B>SQL:</B> $sql";
//?>
</BLOCKQUOTE>

<?php print_trailer(); ?>
</BODY>
</HTML>
