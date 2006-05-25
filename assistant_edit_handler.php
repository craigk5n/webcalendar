<?php
/* $Id$ */
include_once 'includes/init.php';

$error = '';
if ($user != $login)
  $user = ( ($is_admin || $is_nonuser_admin) && $user ) ? $user : $login;

# update user list
dbi_execute ( 'DELETE FROM webcal_asst WHERE cal_boss = ?', array( $user ) );
if ( ! empty ( $users ) ){
  for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
    dbi_execute ( 'INSERT INTO webcal_asst ( cal_boss, cal_assistant ) ' .
      'VALUES ( ?, ? )', array( $user, $users[$i] ) );
  }
}

do_redirect ( 'assistant_edit.php' . ( ( $is_admin || $is_nonuser_admin ) && 
  $login != $user ? '?user=' . $user : '' ) );

print_header();
?>
<h2><?php etranslate( 'Error' )?></h2>

<blockquote>
<?php echo $error; ?>
</blockquote>

<?php echo print_trailer(); ?>
</body>
</html>
