<?php
include_once 'includes/init.php';

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $layer_user = "__public__";
} else {
  $layer_user = $login;
}

load_user_layers ( $layer_user, 1 );

$INC = array('js/edit_layer.php');
print_header($INC);
?>

<H2><FONT COLOR="<?php echo $H2COLOR;?>">
<?php
if ( $updating_public )
  echo translate($PUBLIC_ACCESS_FULLNAME) . " ";
if ( ! empty ( $layers[$id]['cal_layeruser'] ) )
  etranslate("Edit Layer");
else
  etranslate("Add Layer");

?></FONT></H2>



<FORM ACTION="edit_layer_handler.php" METHOD="POST" ONSUBMIT="return valid_form(this);" NAME="prefform">

<?php if ( $updating_public ) { ?>
  <INPUT TYPE="hidden" NAME="public" VALUE="1">
<?php } ?>

<TABLE BORDER=0>


<?php
if ( $single_user == "N" ) {
  $userlist = get_my_users ();
  $nonusers = get_nonuser_cals ();
  $userlist = array_merge($userlist, $nonusers);
  $num_users = 0;
  $size = 0;
  $users = "";
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    if ( $userlist[$i]['cal_login'] != $layer_user ) {
      $size++;
      $users .= "<OPTION VALUE=\"" . $userlist[$i]['cal_login'] . "\"";
      if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) {
        if ( $layers[$id]['cal_layeruser'] == $userlist[$i]['cal_login'] )
          $users .= " SELECTED";
      } 
      $users .= "> " . $userlist[$i]['cal_fullname'];
    }
  }
  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  if ( $size >= 1 ) {
    print "<TR><TD VALIGN=\"top\"><B>" .
      translate("Source") . ":</B></TD>";
    print "<TD><SELECT NAME=\"layeruser\" SIZE=1>$users\n";
    print "</SELECT>\n";
    print "</TD></TR>\n";
  }
}
?>

<TR><TD><B><?php etranslate("Color")?>:</B></TD>
  <TD><INPUT NAME="layercolor" SIZE=7 MAXLENGTH=7 VALUE="<?php echo empty ( $layers[$id]['cal_color'] ) ? "" :  $layers[$id]['cal_color']; ?>"> 

<INPUT TYPE="button" ONCLICK="selectColor('layercolor')" VALUE="<?php etranslate("Select")?>...">
</TD></TR>


<TR><TD><B><?php etranslate("Duplicates")?>:</B></TD>
    <TD><INPUT TYPE="checkbox" NAME="dups" VALUE="Y" <?php if ( ! empty ( $layers[$id]['cal_dups'] ) && $layers[$id]['cal_dups'] == 'Y') echo "checked"; ?> >&nbsp;&nbsp;<?php etranslate("Show layer events that are the same as your own")?></TD></TR> 


<TR><TD COLSPAN="2"><INPUT TYPE="submit" VALUE="<?php etranslate("Save")?>">
<INPUT TYPE="button" VALUE="<?php etranslate("Help")?>..."
  ONCLICK="window.open ( 'help_layers.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );">
</TD></TR>


<?php

// If this is 'Edit Layer' (a layer already exists) put a 'Delete Layer' link
if ( ! empty ( $layers[$id]['cal_layeruser'] ) )
{

?>

<TR><TD><BR><A HREF="del_layer.php?id=<?php echo $id; if ( $updating_public ) echo "&public=1"; ?>" onClick="return confirm('<?php etranslate("Are you sure you want to delete this layer?")?>');"><?php etranslate("Delete layer")?></A><BR></TD></TR>

<?php

}  // end of 'Delete Layer' link if

?>


</TABLE>

<?php if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) echo "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n"; ?>

</FORM>

<?php include_once "includes/trailer.php"; ?>
</BODY>
</HTML>
