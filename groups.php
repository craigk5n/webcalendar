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

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Groups")?></FONT></H2>

<UL>
<?php
$res = dbi_query ( "SELECT cal_group_id, cal_name FROM webcal_group " .
  "ORDER BY cal_name" );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    echo "<LI><A HREF=\"group_edit.php?id=" . $row[0] .
      "\">" . $row[1] . "</A> ";
  }
  dbi_free_result ( $res );
}
?>
</UL>
<P>
<?php
  echo "<A HREF=\"group_edit.php\">" . translate("Add New Group") .
    "</A><BR>\n";
?>

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>
