<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';

$descStr = translate ( 'fine control of UAC and permissions' );

print_header ( '', '', '', true, false, true );
echo $helpListStr . '
    <div class="helpbody">
      <h2>' . translate( 'Help UAC' ) . '</h2>
      <p>' . $descStr . '</p>';
$tmp_arr = array (
  translate ( 'Can Email_' ) => translate ( 'If disabled no email from user' ),
  translate ( 'Can Invite_' ) => translate ( 'If disabled user cant see you' ),
  translate ( 'Can See Time Only_' ) =>
  translate ( 'If enabled user cant see details' ),
  );
list_help ( $tmp_arr );
echo '
    </div>' . print_trailer ( false, true, true );

?>
