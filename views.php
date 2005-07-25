<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

$INC = array('js/users.php','js/visible.php');
print_header($INC);
?>

<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />
<!-- TABS -->
<div id="tabs">
	<span class="tabfor" id="tab_views"><a href="#tabviews" onclick="return showTab('views')"><?php 
		echo translate("Views");
	?></a></span>
</div>

<!-- TABS BODY -->
<div id="tabscontent">
	<!-- VIEWS -->
	<a name="tabviews"></a>
	<div id="tabscontent_views">
<?php
  echo "<a title=\"" . 
	translate("Add New View") . "\" href=\"views_edit.php\" target=\"viewiframe\" onclick=\"javascript:show('viewiframe');\">" . 
	translate("Add New View") . "</a>\n";
?>
	<ul>
<?php
$global_found = false;
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' || $is_admin ) {
    echo "<li><a title=\"" . 
    	$views[$i]["cal_name"] . "\" href=\"views_edit.php?id=" . 
	$views[$i]["cal_view_id"] . "\" target=\"viewiframe\" onclick=\"javascript:show('viewiframe');\">" . 
	$views[$i]["cal_name"] . "</a>";
    if ( $views[$i]['cal_is_global'] == 'Y' ) {
      echo "&nbsp;<abbr title=\"" . translate("Global") . "\">*</abbr>";
      $global_found = true;
    }
    echo "</li>\n";
  }
}
?>
</ul>
<?php
  if ( $global_found )
    echo "<br />\n*&nbsp;" . translate ( "Global" );
?>
<br />
<iframe name="viewiframe" id="viewiframe" style="width:90%;border-width:0px; height:343px;"></iframe>
</div>
</div>
<?php print_trailer(); ?>
</body>
</html>
