<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

if ( $groups_enabled == "N" ) {
  do_redirect ( "$STARTVIEW.php" );
  exit;
}
print_header();
?>

<h2 style="color:<?php echo $H2COLOR;?>;"><?php etranslate("Groups")?></h2>

<ul>
<?php
$res = dbi_query ( "SELECT cal_group_id, cal_name FROM webcal_group " .
  "ORDER BY cal_name" );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    echo "<li><a href=\"group_edit.php?id=" . $row[0] .
      "\">" . $row[1] . "</a></li>";
  }
  dbi_free_result ( $res );
}
?>
</ul>
<br /><br />
<?php
  echo "<a href=\"group_edit.php\">" . translate("Add New Group") .
    "</a><br />\n";
?>

<?php print_trailer(); ?>
</body>
</html>
