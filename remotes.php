<?php
if ( ! $NONUSER_PREFIX ) {
  echo '<h2>' . translate( 'Error' ) . "</h2>\n" . 
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
  translate( 'Add New Remote Calendar' ) . "\" href=\"edit_remotes.php?add=1\" target=\"remotesiframe\" onclick=\"javascript:show('remotesiframe');\">" . 
  translate( 'Add New Remote Calendar' ) . "</a><br />\n";
  // Displaying Remote Calendars
  $userlist = get_nonuser_cals ( $login, true);
  if ( ! empty ( $userlist ) ) {
    echo '<ul>';
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      echo '<li><a title="' . 
        $userlist[$i]['cal_fullname'] . '" href="edit_remotes.php?nid=' . 
  $userlist[$i]["cal_login"] . "\" target=\"remotesiframe\" onclick=\"javascript:show('remotesiframe');\">" . 
  $userlist[$i]['cal_fullname'] . "</a></li>\n";
    }
    echo "</ul>";
  }
}

echo '<iframe name="remotesiframe" id="remotesiframe" style="width:90%;border-width:0px; height:250px;"></iframe>';
?>
</div>
