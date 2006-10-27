<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';  
print_header('', '', '', true);
echo $helpListStr;
?>

<h2><?php etranslate('Help')?>: <?php etranslate('Layers')?></h2>

<table>
 <tr><td colspan="2">
  <p><?php etranslate( 'Layers are useful for displaying other users&#39; events in your own calendar.  You can specify the user and the color the events will be displayed in.')?></p>
 </td></tr>
 <tr><td colspan="2">&nbsp;</td></tr>
 <tr><td class="help">
  <?php etranslate('Add/Edit/Delete')?>:</td><td>
  <?php etranslate('Clicking the Edit Layers link in the admin section at the bottom of the page will allow you to add/edit/delete layers.')?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate('Source')?>:</td><td>
  <?php etranslate('Specifies the user that you would like to see displayed in your calendar.')?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate( 'Colors' )?>:</td><td>
  <?php etranslate('The text color of the new layer that will be displayed in your calendar.')?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate('Duplicates')?>:</td><td>
  <?php etranslate('If checked, events that are duplicates of your events will be shown.')?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate('Disabling')?>:</td><td>
  <?php etranslate('Press the Disable Layers link in the admin section at the bottom of the page to turn off layers.')?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate('Enabling')?>:</td><td>
  <?php etranslate('Press the Enable Layers link in the admin section at the bottom of the page to turn on layers.')?>
 </td></tr>
</table>

<?php if ( $ALLOW_COLOR_CUSTOMIZATION ) { ?>
 <h3><?php etranslate( 'Colors' )?></h3>
 <p><?php etranslate('colors-help')?></p>
<?php } // if $ALLOW_COLOR_CUSTOMIZATION 

echo print_trailer( false, true, true ); ?>

