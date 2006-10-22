<?php
/* $Id$
 * Page Description:
 * This page will present the user with forms for exporting calendar
 *  events.
 *
 * Input Parameters:
 * None
 *
 */
include_once 'includes/init.php';
include_once 'includes/xcal.php';

if ( empty ( $login) || $login == '__public__' ) {
  // do not allow public access
  do_redirect ( empty ( $STARTVIEW ) ? 'month.php' : "$STARTVIEW" );
  exit;
}

load_user_categories ();

$selected = ' selected="selected"';
$datem = date ( 'm' );
$dateY = date ( 'Y' );

$INC = array('js/export_import.php', 'js/visible.php/true');
print_header($INC);
?>

<h2><?php etranslate( 'Export' )?></h2>

<form action="export_handler.php" method="post" name="exportform">
<table>
<tr><td>
 <label for="exformat"><?php etranslate( 'Export format' )?>:</label></td><td>
 <?php echo generate_export_select ( 'toggel_catfilter' ); ?>
</td></tr>
<?php
  if (  is_array ( $categories ) ) { ?>
  <tr id="catfilter"><td>
  <label for="cat_filter"><?php etranslate( 'Categories' )?>:</label></td><td>
 <select name="cat_filter" id="cat_filter"> 
   <?php  
    echo '<option value="" selected="selected">' . translate( 'All' ) . "</option>\n";
    foreach ( $categories as $K => $V ){
      if ( $K  > 0 )
        echo "<option value=\"$K\">$V</option>\n";
    }
   ?>
 </select>
 </td></tr>
<?php } 
 // Only include layers if they are enabled.
 if ( ! empty ( $LAYERS_STATUS ) && $LAYERS_STATUS == 'Y' ) {
?>
<tr><td>&nbsp;
 </td><td>
 <input type="checkbox" name="include_layers" id="include_layers" value="y" />
 <label for="include_layers"><?php etranslate( 'Include all layers' )?></label>
</td></tr>
<?php } ?>
<tr><td>&nbsp;
  </td><td>
 <input type="checkbox" name="include_deleted" id="include_deleted" value="y" />
 <label for="include_deleted"><?php etranslate( 'Include deleted entries' )?></label>
</td></tr>
<tr><td>&nbsp;
 </td><td>
 <input type="checkbox" name="use_all_dates" id="exportall" value="y"  onchange="toggle_datefields( 'dateArea', this );"/>
 <label for="exportall"><?php etranslate( 'Export all dates' )?></label>
</td></tr>
<tr><td colspan="2">
<table id="dateArea">
 <tr><td>
 <label><?php etranslate( 'Start date' )?>:</label></td><td>
   <?php echo date_selection ( 'from_', date ( 'Ymd' ) ) ?>
</td></tr>

<tr><td>
 <label><?php etranslate( 'End date' )?>:</label></td><td>
   <?php echo date_selection ( 'end_', date ( 'Ymd' ) ) ?>
</td></tr>

<tr><td>
 <label><?php etranslate( 'Modified since' )?>:</label></td><td>
  <?php $week_ago = mktime ( 0, 0, 0, $datem, date ( 'd' ) - 7, $dateY );
   echo date_selection ( 'mod_', $week_ago ) ?>
</td></tr>
</table> 
</td></tr>
<tr><td colspan="2">
 <input type="submit" value="<?php etranslate( 'Export' );?>" />
</td></tr>
</table>
</form>
<?php echo print_trailer (); ?>

