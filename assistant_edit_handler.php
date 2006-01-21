<?php
include_once 'includes/init.php';

$error = "";
if ($user != $login)
  $user = ( ($is_admin || $is_nonuser_admin) && $user ) ? $user : $login;

# update user list
dbi_execute ( "DELETE FROM webcal_asst WHERE cal_boss = ?", array( $user ) );
if ( ! empty ( $users ) ){
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    dbi_execute ( "INSERT INTO webcal_asst ( cal_boss, cal_assistant ) " .
      "VALUES ( ?, ? )", array( $user, $users[$i] ) );
  }
}

$url = "assistant_edit.php";
if (($is_admin || $is_nonuser_admin) && $login != $user )
   $url = $url . (strpos($url, "?") === false ? "?" : "&amp;") . "user=$user";
do_redirect ( $url );

print_header();
?>
<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>
</body>
</html>
