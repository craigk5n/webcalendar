<?php /* $Id$ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

if ( ! $NONUSER_PREFIX ) {
  echo print_error_header() . translate ( 'NONUSER_PREFIX not set' ) . '
  </body>
</html>';
  exit;
}
$add = getValue ( 'add' );
echo '
      <a name="tabnonusers"></a>
      <div id="tabscontent_remotes">';

if ( empty ( $error ) ) {
  echo '
        <a href="edit_remotes.php?add=1">'
   . translate( 'Add New Remote Calendar' ) . '</a><br>';
  // Displaying Remote Calendars
  $userlist = get_nonuser_cals ( $login, true );
  if ( ! empty ( $userlist ) ) {
    echo '
        <ul>';
    foreach ( $userlist as $i ) {
      echo '
          <li><a href="edit_remotes.php?nid=' . $i['cal_login']
        . '">' . $i['cal_fullname'] . '</a></li>';
    }
    echo '
        </ul>';
  }
}

echo '
        <iframe id="remotesiframe" name="remotesiframe"></iframe>
      </div>';
?>
