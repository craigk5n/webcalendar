<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
$save_status = $LAYERS_STATUS;
$LAYERS_STATUS = "Y";
load_user_layers ();
$LAYERS_STATUS = $save_status;

include "includes/translate.inc";


?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>


<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR;?>">

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
      $sql = "SELECT cal_lastname, cal_firstname " .
         "FROM webcal_user WHERE cal_login = '" . $layers[$index]['cal_layeruser'] . "' " .
         "ORDER BY cal_lastname, cal_firstname";

      $res = dbi_query ( $sql );

      if ( $res ) {
         if ( $row = dbi_fetch_row ( $res ) ) {

?>
       <TR><TD VALIGN="top"><B><?php etranslate("Layer")?> <?php echo ($index+1) ?></B></TD></TR>
       <TR><TD VALIGN="top"><B><?php etranslate("Source")?>:</B></TD>

           <TD>

<?php

    $layeruser = $layers[$index]['cal_layeruser'];

    if ( strlen ( $row[0] ) ) {
      echo "$row[0]";
      if ( strlen ( $row[1] ) )
        echo ", $row[1]";
      echo " ($layeruser)"; 
    } else {
      echo "$layeruser"; 
    }

?>
           </TD></TR>

       <TR><TD><B><?php etranslate("Color")?>:</B></TD>
          <TD><?php echo ( $layers[$index]['cal_color'] ); ?></TD></TR>

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
      }
   }
?>

       <TR><TD><A HREF="edit_layer.php"><?php echo (translate("Add layer")); ?></A></TD></TR>

</TABLE>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
