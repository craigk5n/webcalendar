<?php

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

send_no_cache_header ();
load_global_settings ();
load_user_preferences ();
$save_status = $LAYERS_STATUS;
$LAYERS_STATUS = "Y";
load_user_layers ();

include "includes/translate.php";


?>
<HTML>
<HEAD>
<TITLE><?php etranslate($application_name)?></TITLE>


<?php include "includes/styles.php"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>" CLASS="defaulttext">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Layers")?></FONT></H2>

<?php
etranslate("Layers are currently");
echo " <B>";
//$sql = "SELECT cal_value FROM webcal_user_pref " .
//  "WHERE cal_setting = 'LAYERS_STATUS' AND cal_login = '$login'";
//$res = dbi_query ( $sql );
//if ( $res ) {
//  $row = dbi_fetch_row ( $res );
//  $PREF_LAYERS_ENABLED = $row[0];
//  dbi_free_result ( $res );
//}

$LAYERS_STATUS = $save_status;
//echo "LAYERS_STATUS: $LAYERS_STATUS <P>";

if ( $LAYERS_STATUS == "N" ) {
  etranslate ( "Disabled" );
} else {
  etranslate ( "Enabled" );
}
echo "</B>.";


?>
<P>


<TABLE BORDER=0>

<?php

   for($index = 0; $index < sizeof($layers); $index++)
   {
      $layeruser = $layers[$index]['cal_layeruser'];
      user_load_variables ( $layeruser, "layer" );
?>
       <TR><TD VALIGN="top"><B><?php etranslate("Layer")?> <?php echo ($index+1) ?></B></TD></TR>
       <TR><TD VALIGN="top"><B><?php etranslate("Source")?>:</B></TD>
           <TD> <?php echo $layerfullname; ?> </TD></TR>

       <TR><TD><B><?php etranslate("Color")?>:</B></TD>
          <TD BGCOLOR="<?php echo $CELLBG;?>"><FONT COLOR="<?php echo ( $layers[$index]['cal_color'] ); ?>"><?php echo ( $layers[$index]['cal_color'] ); ?></FONT></TD></TR>

       <TR><TD><B><?php etranslate("Duplicates")?>:</B></TD>
          <TD>
              <?php
              if( $layers[$index]['cal_dups'] == 'N')
                etranslate("No");
              else
                etranslate("Yes");
              ?>
          </TD></TR>



       <TR><TD><A HREF="edit_layer.php?id=<?php echo ($index); ?>"><?php echo (translate("Edit layer")) ?></A></TD></TR>
       <TR><TD><A HREF="del_layer.php?id=<?php echo ($index); ?>" onClick="return confirm('<?php etranslate("Are you sure you want to delete this layer?")?>');"><?php etranslate("Delete layer")?></A><BR></TD></TR>


       <TR><TD><BR></TD></TR>

<?php
   }
?>

       <TR><TD><A HREF="edit_layer.php"><?php echo (translate("Add layer")); ?></A></TD></TR>

</TABLE>

<FORM>
<INPUT TYPE="button" VALUE="<?php etranslate("Help")?>..."
  ONCLICK="window.open ( 'help_layers.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420' );">
</FORM>

<?php include "includes/trailer.php"; ?>
</BODY>
</HTML>
