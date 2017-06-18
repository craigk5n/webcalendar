<?php
/* $Id: remotes.php,v 1.12 2007/08/02 12:57:51 umcesrjones Exp $ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$newRemoteStr = translate ( 'Add New Remote Calendar' );
$targetStr =
'target="remotesiframe" onclick="showFrame( \'remotesiframe\' );">';

if ( ! $NONUSER_PREFIX ) {
  echo print_error_header () . translate ( 'NONUSER_PREFIX not set' ) . '
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
      <a title="' . $newRemoteStr . '" href="edit_remotes.php?add=1"'
   . $targetStr . $newRemoteStr . '</a><br />';
  // Displaying Remote Calendars
  $userlist = get_nonuser_cals ( $login, true );
  if ( ! empty ( $userlist ) ) {
    echo '
      <ul>';
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      echo '
        <li><a title="' . $userlist[$i]['cal_fullname']
       . '" href="edit_remotes.php?nid=' . $userlist[$i]['cal_login'] . '"'
       . $targetStr . $userlist[$i]['cal_fullname'] . '</a></li>';
    }
    echo '
      </ul>';
  }
}

echo '
      <iframe name="remotesiframe" id="remotesiframe" style="width: 90%; '
 . 'border: 0; height: 250px;"></iframe>
    </div>';
?>
