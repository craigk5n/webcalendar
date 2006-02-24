<?php
include_once 'includes/init.php';
print_header('','','',true);
?>

<h2><?php etranslate("Help")?>: <?php etranslate("User Access Control")?></h2>

<table style="border-width:0px;">
 <tr><td colspan="2">
  <?php etranslate("User Access Control allows for finer control of user access and permissions than possible before. Users can also grant default and per individual permission if authorized by the administrator.")?>
 </td></tr>
 <tr><td colspan="2">&nbsp;</td></tr>
 <tr><td class="help">
  <?php etranslate("Can Invite")?>:</td><td>
  <?php etranslate("If disabled, this user will not see you in the participants list.")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Can Email")?>:</td><td>
  <?php etranslate("If disabled, this user will not be able to send you emails.")?>
 </td></tr>
 <tr><td class="help">
  <?php etranslate("Can See Time Only")?>:</td><td>
  <?php etranslate("If enabled, this user will not be able to view the details of any of your entries.")?>
 </td></tr>
</table>
<br /><br />

<?php print_trailer( false, true, true ); ?>
</body>
</html>
