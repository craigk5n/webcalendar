<?php /* $Id$ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

if ( ! $is_admin ) {
  echo print_not_auth ( true ) . '
  </body>
</html>';
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo print_error_header() . translate ( 'NONUSER_PREFIX not set' ) . '
  </body>
</html>';
  exit;
}

$add = getValue ( 'add' );

echo '
      <a name="tabnonusers"></a>
      <div id="tabscontent_nonusers">';

if ( empty ( $error ) ) {
  echo '
        <a href="edit_nonusers.php?add=1">'
   . translate( 'Add New NonUser Calendar' ) . '</a><br>';
  // Displaying NonUser Calendars
  $userlist = get_nonuser_cals();
  if ( ! empty ( $userlist ) ) {
    echo '
        <ul>';
    foreach ( $userlist as $i ) {
      echo '
          <li><a href="edit_nonusers.php?nid=' . $i['cal_login']
        . '">' . $i['cal_fullname'] . '</a></li>';
    }
    echo '
        </ul>';
  }
}
echo '
        <iframe id="nonusersiframe" name="nonusersiframe"></iframe>
      </div>';

?>
