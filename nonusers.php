<?php
/* $Id$ */
defined( '_ISVALID' ) or die( "You can't access this file directly!" );

$newNonUserStr = translate( 'Add New NonUser Calendar' );
$targetStr = 'target="nonusersiframe" onclick="javascript:show(\'nonusersiframe\');">';

if ( ! $is_admin ) {
  echo print_not_auth ( true );
  echo "</body>\n</html>";
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo print_error_header () . 
      translate( 'NONUSER_PREFIX not set' ) . ".\n";
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( 'add' );
?>
  <a name="tabnonusers"></a>
  <div id="tabscontent_nonusers">
<?php
if ( empty ($error) ) {
  echo '<a title="' . 
  $newNonUserStr . '" href="edit_nonusers.php?add=1"' . $targetStr . 
  $newNonUserStr . "</a><br />\n";
  // Displaying NonUser Calendars
  $userlist = get_nonuser_cals ();
  if ( ! empty ( $userlist ) ) {
    echo '<ul>';
    for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
      echo '<li><a title="' . 
        $userlist[$i]['cal_fullname'] . '" href="edit_nonusers.php?nid=' . 
  $userlist[$i]['cal_login'] . '"' . $targetStr . 
  $userlist[$i]['cal_fullname'] . "</a></li>\n";
    }
    echo '</ul>';
  }
}

echo '<iframe name="nonusersiframe" id="nonusersiframe" style="width:90%;border-width:0px; height:250px;"></iframe>';
?>
</div>
