<?php
/*
 * $Id$
 * Page Description:
 * This page will present the user with forms for exporting calendar
 *  events.
 *
 * Input Parameters:
 * None
 *
 */
include_once 'includes/init.php';

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
<table style="border-width:0px;">
<tr><td>
 <label for="exformat"><?php etranslate( 'Export format' )?>:</label></td><td>
 <select name="format" id="exformat" onchange="toggel_catfilter();">
  <option value="ical">iCalendar</option>
  <option value="vcal">vCalendar</option>
  <option value="pilot-csv">Pilot-datebook CSV (<?php etranslate( 'Palm Pilot' )?>)</option>
  <option value="pilot-text">Install-datebook (<?php etranslate( 'Palm Pilot' )?>)</option>
 </select>
</td></tr>
<?php  // Only include layers if they are enabled.
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
 <input type="checkbox" name="use_all_dates" id="exportall" value="y" />
 <label for="exportall"><?php etranslate( 'Export all dates' )?></label>
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
<?php } ?>
<tr><td>
 <label><?php etranslate( 'Start date' )?>:</label></td><td>
 <select name="fromday">
  <?php
   $day = date ( 'd' );
   for ( $i = 1; $i <= 31; $i++ ) echo '<option' . ( $i == $day ? $selected : '' ) . ">$i</option>\n";
  ?>
 </select>
 <select name="frommonth">
  <?php
   $month = $datem;
   $year = $dateY;
   for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    echo "<option value=\"$i\"" . ( $i == $month ? $selected : '' ) . ">$m</option>\n";
   }
  ?>
 </select>
 <select name="fromyear">
  <?php
   $year = $dateY - 1;
   for ( $i = -1; $i < 5; $i++ ) {
    $y = $dateY + $i;
    echo "<option value=\"$y\"" . ( $y == $year ? $selected : '' ) . ">$y</option>\n";
   }
  ?>
 </select>
 <input type="button" onclick="selectDate('fromday','frommonth','fromyear', '', event)" value="<?php etranslate( 'Select' )?>..." />
</td></tr>

<tr><td>
 <label><?php etranslate( 'End date' )?>:</label></td><td>
 <select name="endday">
  <?php
   $day = date ( 'd' );
   for ( $i = 1; $i <= 31; $i++ ) echo '<option' . ( $i == $day ? $selected : '' ) . ">$i</option>\n";
  ?>
 </select>
 <select name="endmonth">
  <?php
   $month = $datem;
   $year = $dateY;
   for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    echo "<option value=\"$i\"" . ( $i == $month ? $selected : '') . ">$m</option>\n";
   }
  ?>
 </select>
 <select name="endyear">
  <?php
   $year = $dateY + 1;
   for ( $i = -1; $i < 5; $i++ ) {
    $y = $dateY + $i;
    echo "<option value=\"$y\"" . ( $y == $year ? $selected : '' ) . ">$y</option>\n";
   }
  ?>
 </select>
 <input type="button" onclick="selectDate('endday','endmonth','endyear', '', event)" value="<?php etranslate( 'Select' )?>..." />
</td></tr>

<tr><td>
 <label><?php etranslate( 'Modified since' )?>:</label></td><td>
 <select name="modday">
  <?php
   $week_ago = mktime ( 0, 0, 0, $datem, date ( 'd' ) - 7, $dateY );
   $day = date ( 'd', $week_ago );
   for ( $i = 1; $i <= 31; $i++ ) echo '<option' . ( $i == $day ? $selected : '' ) . ">$i</option>\n";
  ?>
 </select>
 <select name="modmonth">
  <?php
   $month = date ( 'm', $week_ago );
   $year = date ( 'Y', $week_ago );
   for ( $i = 1; $i <= 12; $i++ ) {
    $m = month_short_name ( $i - 1 );
    echo "<option value=\"$i\"" . ( $i == $month ? $selected : '' ) . ">$m</option>\n";
   }
  ?>
 </select>
 <select name="modyear">
  <?php
   $year = date ( 'Y', $week_ago );
   for ( $i = -1; $i < 5; $i++ ) {
    $y = $dateY + $i;
    echo "<option value=\"$y\"" . ( $y == $year ? $selected : '' ) . ">$y</option>\n";
   }
  ?>
 </select>
 <input type="button" onclick="selectDate('modday','modmonth','modyear', '', event)" value="<?php etranslate( 'Select' )?>..." />
</td></tr>

<tr><td colspan="2">
 <input type="submit" value="<?php etranslate( 'Export' );?>" />
</td></tr>
</table>
</form>
<?php echo print_trailer (); ?>
</body>
</html>
