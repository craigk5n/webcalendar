<?php
/* $Id$ */
defined( '_ISVALID' ) or die( "You can't access this file directly!" );

$newRemoteStr = translate( 'Add New Remote Calendar' );
$targetStr = 'target="remotesiframe" onclick="javascript:show(\'remotesiframe\');">';

if ( ! $NONUSER_PREFIX ) {
  echo print_error_header () . 
      translate( 'NONUSER_PREFIX not set' ) . ".\n";
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( 'add' );
?>
  <a name="tabnonusers"></a>
  <div id="tabscontent_remotes">
<?php
if ( empty ($error) ) {
  echo '<a title="' . 
  $newRemoteStr . '" href="edit_remotes.php?add=1"' . $targetStr . 
  $newRemoteStr . "</a><br />\n";
  // Displaying Remote Calendars
  $userlist = get_nonuser_cals ( $login, true);
  if ( ! empty ( $userlist ) ) {
    echo '<ul>';
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      echo '<li><a title="' . 
        $userlist[$i]['cal_fullname'] . '" href="edit_remotes.php?nid=' . 
  $userlist[$i]['cal_login'] . '"' . $targetStr . 
  $userlist[$i]['cal_fullname'] . "</a></li>\n";
    }
    echo "</ul>";
  }
}

echo '<iframe name="remotesiframe" id="remotesiframe" style="width:90%;border-width:0px; height:250px;"></iframe>';
?>
</div>
