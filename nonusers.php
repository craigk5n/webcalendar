<?php
if ( ! $is_admin ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
  		translate("You are not authorized") . ".\n";
  echo "</body>\n</html>";
  exit;
}
if ( ! $NONUSER_PREFIX ) {
  echo "<h2>" . translate("Error") . "</h2>\n" . 
  		translate("NONUSER_PREFIX not set") . ".\n";
  echo "</body>\n</html>";
  exit;
}
$add = getValue ( "add" );
?>
	<a name="tabnonusers"></a>
	<div id="tabscontent_nonusers">
<?php
if ( empty ($error) ) {
  echo "<a title=\"" . 
	translate("Add New NonUser Calendar") . "\" href=\"edit_nonusers.php?add=1\" target=\"nonusersiframe\" onclick=\"javascript:show('nonusersiframe');\">" . 
	translate("Add New NonUser Calendar") . "</a><br />\n";
  // Displaying NonUser Calendars
  $userlist = get_nonuser_cals ();
  if ( ! empty ( $userlist ) ) {
    echo "<ul>";
    for ( $i = 0; $i < count ( $userlist ); $i++ ) {
      echo "<li><a title=\"" . 
      	$userlist[$i]['cal_fullname'] . "\" href=\"edit_nonusers.php?nid=" . 
	$userlist[$i]["cal_login"] . "\" target=\"nonusersiframe\" onclick=\"javascript:show('nonusersiframe');\">" . 
	$userlist[$i]['cal_fullname'] . "</a></li>\n";
    }
    echo "</ul>";
  }
}

echo "<iframe name=\"nonusersiframe\" id=\"nonusersiframe\" style=\"width:90%;border-width:0px; height:250px;\"></iframe>";
?>
</div>
