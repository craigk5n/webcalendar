<?php
include_once 'includes/init.php';

if ( ! $is_admin )
  $user = $login;

print_header();
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Views")?></FONT></H2>

<UL>
<?php
for ( $i = 0; $i < count ( $views ); $i++ ) {
  echo "<LI><A HREF=\"views_edit.php?id=" . $views[$i]["cal_view_id"] .
    "\">" . $views[$i]["cal_name"] . "</A> ";
}
?>
</UL>
<P>
<?php
  echo "<A HREF=\"views_edit.php\">" . translate("Add New View") .
    "</A><BR>\n";
?>

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>