<?php
/*
 * $Id$
 *
 * Page Description:
 *	Serves as the home page for administrative functions.
 *
 * Input Parameters:
 *	None
 *
 * Security:
 *	Admin users will see different options available on this page.
 *
 */
include_once 'includes/init.php';

if ( empty ( $TODAYCELLBG ) )
  $TODAYCELLBG = '#C0C0C0';

$COLUMNS = 3;


$style = "
<style>
table.admintable {
  padding: 5px;
  border: 1px solid #000000;
  background-color: #CCCCCC;
}
td.admincell {
  padding: 20px;
  text-align: center;
}
td.admincell a {
  padding: 10px;
  width: 200px;
  text-align: center;
  background-color: #CCCCCC;
  border-top: 1px solid #EEEEEE;
  border-left: 1px solid #EEEEEE;
  border-bottom: 1px solid #777777;
  border-right: 1px solid #777777;
}
td.admincell a:hover {
  padding: 10px;
  width: 200px;
  text-align: center;
  background-color: #AAAAAA;
  border-top: 1px solid #777777;
  border-left: 1px solid #777777;
  border-bottom: 1px solid #EEEEEE;
  border-right: 1px solid #EEEEEE;
}
</style>
";
print_header('', $style);

$names = array ();
$links = array ();

if ( $is_admin ) {
  $names[] = translate("System Settings");
  $links[] = "admin.php";
}

$names[] = translate("Preferences");
$links[] = "pref.php";

if ( $is_admin ) {
  $names[] = translate("Users");
  $links[] = "users.php";
} else {
  $names[] = translate("Account");
  $links[] = "edit_user.php";
}

if ( $is_admin && ! empty ( $groups_enabled ) && $groups_enabled == 'Y' ) {
  $names[] = translate("Groups");
  $links[] = "groups.php";
}

if ( $single_user != 'Y' ) {
  $names[] = translate("Assistants");
  $links[] = "assistant_edit.php";
}

if ( $is_admin && $nonuser_enabled == 'Y' ) {
  $names[] = translate("NonUser Calendars");
  $links[] = "nonusers.php";
}

if ( $categories_enabled == 'Y' ) {
  $names[] = translate("Categories");
  $links[] = "category.php";
}

$names[] = translate("Views");
$links[] = "views.php";

$names[] = translate("Layers");
$links[] = "layers.php";

if ( $reports_enabled == 'Y' ) {
  $names[] = translate("Reports");
  $links[] = "report.php";
}

if ( $is_admin ) {
  $names[] = translate("Delete Events");
  $links[] = "purge.php";
}

if ( $is_admin ) {
  $names[] = translate("Activity Log");
  $links[] = "activity_log.php";
}

if ( $is_admin && ! empty ( $public_access ) && $public_access == 'Y' ) {
  $names[] = translate("Public Preferences");
  $links[] = "pref.php?public=1";
}

if ( $is_admin && ! empty ( $public_access ) && $public_access == 'Y' &&
  $public_access_can_add == 'Y' && $public_access_add_needs_approval == 'Y' ) {
  $names[] = translate("Unapproved Public Events");
  $links[] = "list_unapproved.php?user=__public__";
}
?>

<h2 style="color:<?php echo $H2COLOR;?>;"><?php etranslate("Administrative Tools")?></h2>

<table class="admintable">

<?php
  for ( $i = 0; $i < count ( $names ); $i++ ) {
  if ( $i % $COLUMNS == 0 )
    echo "<tr>\n";
//  if ( $i % 0 == 1 )
//      echo "<tr>\n";
    echo "<td class=\"admincell\">";
    if ( ! empty ( $links[$i] ) )
      echo "<a href=\"$links[$i]\" class=\"admincell\">";
    echo $names[$i];
    if ( ! empty ( $links[$i] ) )
      echo "</a>";
    echo "</td>\n";
    if ( $i % $COLUMNS == $COLUMNS - 1 )
       echo "</tr>\n";
  }
  if ( $i % $COLUMNS != 0 )
    echo "</tr>\n";
?>
</table>

<?php print_trailer(); ?>
</body>
</html>
