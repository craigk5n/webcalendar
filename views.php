<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

print_header();
?>

<h2 style="color:<?php echo $H2COLOR;?>;"><?php etranslate("Views")?></h2>

<ul>
<?php
for ( $i = 0; $i < count ( $views ); $i++ ) {
  echo "<li><a href=\"views_edit.php?id=" . $views[$i]["cal_view_id"] .
    "\">" . $views[$i]["cal_name"] . "</a></li>";
}
?>
</ul>
<br /><br />
<?php
  echo "<a href=\"views_edit.php\">" . translate("Add New View") .
    "</a><br />\n";
?>

<?php print_trailer(); ?>
</body>
</html>
