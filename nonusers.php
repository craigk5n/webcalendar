<?php
/* $Id: nonusers.php,v 1.32.2.3 2007/11/13 21:24:01 umcesrjones Exp $ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

if ( ! $is_admin ) {
  echo print_not_auth ( 3, true ) . '
  </body>
</html>';
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo print_error_header () . translate ( 'NONUSER_PREFIX not set' ) . '
  </body>
</html>';
  exit;
}

$add = getValue ( 'add' );
$newNonUserStr = translate ( 'Add New NonUser Calendar' );
$targetStr =
'target="nonusersiframe" onclick="showFrame( \'nonusersiframe\' );">';

ob_start ();

echo '
  <a name="tabnonusers"></a>
  <div id="tabscontent_nonusers">';

if ( empty ( $error ) ) {
  echo '
    <a title="' . $newNonUserStr . '" href="edit_nonusers.php?add=1"'
   . $targetStr . $newNonUserStr . '</a><br />';
  // Displaying NonUser Calendars
  $userlist = get_nonuser_cals ();
  if ( ! empty ( $userlist ) ) {
    echo '
    <ul>';
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      echo '
      <li><a title="' . $userlist[$i]['cal_fullname']
       . '" href="edit_nonusers.php?nid=' . $userlist[$i]['cal_login'] . '"'
       . $targetStr . $userlist[$i]['cal_fullname'] . '</a></li>';
    }
    echo '
    </ul>';
  }
}

echo '
    <iframe name="nonusersiframe" id="nonusersiframe" style="width: 90%; '
 . 'border: 0; height: 250px;"></iframe>
  </div>';

ob_end_flush ();

?>
