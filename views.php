<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

print_header();
?>

<h2><?php etranslate("Views")?></h2>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />
<ul>
<?php
$global_found = false;
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' || $is_admin ) {
    echo "<li><a href=\"views_edit.php?id=" . $views[$i]["cal_view_id"] .
      "\">" . $views[$i]["cal_name"] . "</a>";
    if ( $views[$i]['cal_is_global'] == 'Y' ) {
      echo "<sup>*</sup>";
      $global_found = true;
    }
    echo "</li>";
  }
}
?>
</ul>
<?php
  if ( $global_found )
    echo "<br />\n<sup>*</sup> " . translate ( "Global" );
?>
<br /><br />
<?php
  echo "<a href=\"views_edit.php\">" . translate("Add New View") .
    "</a><br />\n";
?>

<?php print_trailer(); ?>
</body>
</html>
