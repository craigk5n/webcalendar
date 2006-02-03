<?php
 include_once 'includes/init.php';
 print_header('','','',true);
?>

<h2><?php etranslate("Help")?>: <?php etranslate("Adding/Editing Calendar Entries")?></h2>

<table style="border-width:0px;">
 <tr><td class="help">
  <?php etranslate("Brief Description")?>:</td><td>
  <?php etranslate("brief-description-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Full Description")?>:</td><td>
  <?php etranslate("full-description-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Date")?>:</td><td>
  <?php etranslate("date-help")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Time")?>:</td><td>
  <?php etranslate("time-help")?>
 </td></tr>
   <?php if ( $TIMED_EVT_LEN != 'E' ) { ?>
 <tr><td class="help">
  <?php etranslate("Duration")?>:</td><td>
  <?php etranslate("duration-help")?>
 </td></tr>
   <?php } else { ?>
 <tr><td class="help">
   <?php etranslate("End Time")?>:</td><td>
   <?php etranslate("end-time-help")?>
  </td></tr>
   <?php } ?>
 <?php if ( $DISABLE_PRIORITY_FIELD != "Y" ) { ?>
  <tr><td class="help">
   <?php etranslate("Priority")?>:</td><td>
   <?php etranslate("priority-help")?>
  </td></tr>
 <?php } ?>
 <?php if ( $DISABLE_ACCESS_FIELD != "Y" ) { ?>
  <tr><td class="help">
   <?php etranslate("Access")?>:</td><td>
   <?php etranslate("access-help")?>
  </td></tr>
 <?php } ?>
 <?php
  $show_participants = ( $DISABLE_PARTICIPANTS_FIELD != "Y" );
  if ( $is_admin )
   $show_participants = true;
  if ( $single_user == "N" && $show_participants ) { ?>
   <tr><td class="help">
    <?php etranslate("Participants")?>:</td><td>
    <?php etranslate("participants-help")?>
   </td></tr>
  <?php } ?>
 <?php if ( $DISABLE_REPEATING_FIELD != "Y" ) { ?>
  <tr><td class="help">
   <?php etranslate("Repeat Type")?>:</td><td>
   <?php etranslate("repeat-type-help")?>
  </td></tr>
  <tr><td class="help">
   <?php etranslate("Repeat End Date")?>:</td><td>
   <?php etranslate("repeat-end-date-help")?>
  </td></tr>
  <tr><td class="help">
   <?php etranslate("Repeat Day")?>:</td><td>
   <?php etranslate("repeat-day-help")?>
  </td></tr>
  <tr><td class="help">
   <?php etranslate("Frequency")?>:</td><td>
   <?php etranslate("repeat-frequency-help")?>
  </td></tr>
 <?php } ?>
</table>

<?php print_trailer( false ); ?>

</body>
</html>
