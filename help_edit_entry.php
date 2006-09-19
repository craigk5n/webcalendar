<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';  
print_header('', '', '', true);
echo $helpListStr;
?>

<h2><?php etranslate('Help')?>: <?php etranslate('Adding/Editing Calendar Entries')?></h2>

 <p class="helptext">
  <span class="helplabel"><?php etranslate('Brief Description')?>:</span>
  <?php etranslate('brief-description-help')?></p>
 <p class="helptext">
  <span class="helplabel"><?php etranslate('Full Description')?>:</span>
  <?php etranslate('full-description-help')?></p>
 <p class="helptext">
  <span class="helplabel"><?php etranslate('Date')?>:</span>
  <?php etranslate('date-help')?></p>
 <p class="helptext">
  <span class="helplabel"><?php etranslate('Time')?>:</span>
  <?php etranslate('time-help')?></p>
 <p class="helptext">
  <span class="helplabel">
   <?php if ( $TIMED_EVT_LEN != 'E' ) { 
     etranslate('Duration')?>:</span>
     <?php etranslate('duration-help');
    } else {
     etranslate('End Time')?>:</span>
     <?php etranslate('end-time-help')?>
   <?php } ?></p>

  <?php if ( $DISABLE_PRIORITY_FIELD != 'Y' ) { ?>
   <p class="helptext">
   <span class="helplabel"><?php etranslate('Priority')?>:</span>
   <?php etranslate('priority-help')?></p>
 <?php }
 if ( $DISABLE_ACCESS_FIELD != 'Y' ) { ?>
   <p class="helptext">
   <span class="helplabel"><?php etranslate('Access')?>:</span>
   <?php etranslate('access-help')?></p>
 <?php }
  $show_participants = ( $DISABLE_PARTICIPANTS_FIELD != 'Y' );
  if ( $is_admin )
   $show_participants = true;
  if ( $single_user == 'N' && $show_participants ) { ?>
    <p class="helptext">
    <span class="helplabel"><?php etranslate('Participants')?>:</span>
    <?php etranslate('participants-help')?></p>
  <?php } 
  if ( $DISABLE_REPEATING_FIELD != 'Y' ) { ?>
    <p class="helptext">
    <span class="helplabel"><?php etranslate('Repeat Type')?>:</span>
   <?php etranslate('repeat-type-help')?>
   <a class="underline" href="/docs/WebCalendar-UserManual.html#repeat"><?php 
    etranslate( 'For More Information...') ?></a></p>
    <p class="helptext">
    <span class="helplabel"><?php etranslate('Repeat End Date')?>:</span>
   <?php etranslate('repeat-end-date-help')?></p>

    <p class="helptext">
    <span class="helplabel"><?php etranslate('Repeat Day')?>:</span>
   <?php etranslate('repeat-day-help')?></p>

    <p class="helptext">
    <span class="helplabel"><?php etranslate('Frequency')?>:</span>
   <?php etranslate('repeat-frequency-help')?></p>
 <?php } 

echo print_trailer( false, true, true ); ?>

