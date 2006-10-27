<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';  
print_header('', '', '', true);
echo $helpListStr;
?>

<h2><?php etranslate('Help')?>: <?php etranslate('Import')?></h2>

<h3><?php echo 'Palm Desktop';?></h3>
  <p><?php etranslate('This form will allow you to import entries from the Palm Desktop Datebook.'); ?><br />
  <?php etranslate('It should be located in your Palm directory in <tt>datebook/datebook.dat</tt> in a subdirectory named by your username.') ?></p>

<p><?php etranslate('The following entries will not be imported')?>:</p>
  <ul>
    <li><?php etranslate('Entries older than the current date')?></li>
    <li><?php etranslate( 'Entries created in the Palm Desktop that have not been HotSync&#39;d' )?></li>
  </ul>

  <p><?php etranslate('Anything imported from Palm will be overwritten during the next import (unless the event date has passed).') .
  etranslate('Therefore, updates should be made in the Palm Desktop.');?></p>

<h3><?php echo 'vCal' ?></h3>
  <p><?php etranslate('This form will import vCalendar (.vcs) 1.0 events');?>.</p>

<p><?php etranslate('The following formats have been tested');?>:</p>
  <ul>
    <li><?php echo 'Palm Desktop 4'; ?></li>
    <li><?php echo 'Lotus Organizer 6'; ?></li>
    <li><?php echo 'Microsoft Outlook 2002'; ?></li>
  </ul>

<h3><?php echo 'iCalendar' ?></h3>
  <p><?php etranslate('This form will import iCalendar (.ics) events');?>.
  <?php etranslate('Enabling <b>Overwrite Prior Import</b> will cause events imported previously that used the same UID as an event from the new import file to be marked as deleted.  This should allow an updated iCalendar file to be imported without creating duplicates.'); ?></p>

<?php echo print_trailer( false, true, true ); ?>

