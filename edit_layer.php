<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

?>


<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>

<SCRIPT LANGUAGE="JavaScript">

function valid_color ( str ) {
  var ch, j;
  var valid = "0123456789abcdefABCDEF";

  if ( str.length == 0 )
    return true;

  if ( str.charAt ( 0 ) != '#' || str.length != 7 )
    return false;

  for ( j = 1; j < str.length; j++ ) {
   ch = str.charAt ( j );
   if ( valid.indexOf ( ch ) < 0 )
     return false;
  }
  return true;
}

function valid_form ( form ) {
  var err = "";
  if ( ! valid_color ( form.layercolor.value ) )
    err += "<?php etranslate("Invalid color")?>.\n";

  if ( err.length > 0 ) {
    alert ( "Error:\n\n" + err + "\n\n<?php etranslate("Color format should be '#RRGGBB'")?>" );
    return false;
  }
  return true;
}

function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}
</SCRIPT>

<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php if ( strlen( $layers[$id]['cal_layeruser'] ) ) echo translate("Edit Layer"); else echo translate("Add Layer"); ?></FONT></H2>



<FORM ACTION="edit_layer_handler.php" METHOD="POST" ONSUBMIT="return valid_form(this);" NAME="prefform">

<?php if ( strlen ( $layers[$id]['cal_layeruser']) ) echo "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n"; ?>

<TABLE BORDER=0>


<?php
if ( ! strlen ( $single_user_login ) ) {
  $userlist = user_get_users ();
  $num_users = 0;
  $size = 0;
  $users = "";
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    $size++;
    $users .= "<OPTION VALUE=\"" . $userlist[$i]['cal_login'] . "\"";
    if ( strlen ($layers[$id]['cal_layeruser']) > 0 ) {
      if ( $layers[$id]['cal_layeruser'] == $userlist[$i]['cal_login'] )
        $users .= " SELECTED";
    } 
    $users .= "> " . $userlist[$i]['cal_fullname'];
  }
  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  if ( $size > 1 ) {
    print "<TR><TD VALIGN=\"top\"><B>" .
      translate("Source") . ":</B></TD>";
    print "<TD><SELECT NAME=\"layeruser\" SIZE=1>$users\n";
    print "</SELECT></TD></TR>\n";
  }
}
?>

<TR><TD><B><?php etranslate("Color")?>:</B></TD>
  <TD><INPUT NAME="layercolor" SIZE=7 MAXLENGTH=7 VALUE="<?php echo ($layers[$id]['cal_color']); ?>"> 

<INPUT TYPE="button" ONCLICK="selectColor('layercolor')" VALUE="<?php etranslate("Select")?>...">
</TD></TR>


<TR><TD><B><?php etranslate("Duplicates")?>:</B></TD>
    <TD><INPUT TYPE="checkbox" NAME="dups" VALUE="Y" <?php if($layers[$id]['cal_dups'] == 'Y') echo "checked"; ?> >&nbsp;&nbsp;<?php etranslate("Show layer events that are the same as your own")?></TD></TR> 


<TR><TD><INPUT TYPE="submit" VALUE="<?php etranslate("Save")?>"></TD></TR>


<?php

// If this is 'Edit Layer' (a layer already exists) put a 'Delete Layer' link
if ( strlen( $layers[$id]['cal_layeruser'] ) )
{

?>

<TR><TD><BR><A HREF="del_layer.php?id=<?php echo $id; ?>" onClick="return confirm('<?php etranslate("Are you sure you want to delete this layer?")?>');"><?php etranslate("Delete layer")?></A><BR></TD></TR>

<?php

}  // end of 'Delete Layer' link if

?>


</TABLE>

</FORM>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
