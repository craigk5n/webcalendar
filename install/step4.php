<?php 
/*
 * $Id$
 *
 * Page Description:
 * Installation Step 4
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
 <table border="1" width="90%" align="center">
   <th class="pageheader" colspan="2"><?php echo $wizardStr ?> 4</th>
   <tr><td colspan="2" width="50%">
     <?php etranslate ( 'Step4 Intro' ) ?>.
   </td></tr>
   <?php if ( ! empty ( $_SESSION['tz_conversion'] ) && 
     $_SESSION['tz_conversion'] != 'Y' ) { ?>
  <th class="header" colspan="2"><?php etranslate ( 'Timezone Conversion' ) ?></th></tr>
  <tr><td colspan="2">
 <?php if ( $_SESSION['tz_conversion'] != 'Success' ) {?>
   <form action="index.php?action=tz_convert" method="post">
  <ul><li>
<?php echo translate ( 'TZ Conversion Intro' ) ?>
    </li></ul>
   <div align="center">
     <input  type="submit" value="<?php etranslate ( 'Convert Data to GMT') ?>:"  /></div>
   </form>
 <?php } else if ( $_SESSION['tz_conversion'] == 'Success' ) { ?>
    <ul><li><?php echo $tzSuccessStr ?></li></ul>
 <?php } ?>
 </td></tr>
  <?php } //end Timezone Conversion ?>
 <th class="header" colspan="2"><?php etranslate ( 'Application Settings' ) ?></th>
 <tr><td colspan="2"><ul>
  <?php if ( empty ( $PHP_AUTH_USER ) ) { ?>
   <li><?php echo translate ( 'HTTP-auth not detected...' ) ?>
   </li>
  <?php } else { ?>
   <li><?php echo translate ( 'HTTP-auth was detected...' ) ?>
   </li>
  <?php } ?>
 </ul></td></tr>

   <tr><td>
 <?php $will_load_admin = ( ( $_SESSION['old_program_version'] == 'new_install' )? 
  CHECKED :''); ?>
  <table width="75%" align="center" border="0"><tr>
  <form action="index.php?action=switch&amp;page=4" method="post" enctype='multipart/form-data' name="form_app_settings">
    <input type="hidden" name="app_settings"  value="1"/>
    <td class="prompt"><?php etranslate ( 'Create Default Admin Account' ) ?>:</td>
    <td><input type="checkbox" name="load_admin" value="Yes" <?php 
      echo $will_load_admin ?> /><?php 
         if ( ! isset ( $_SESSION['admin_exists']  ) ) {
           echo '<span class="notrecommended"> ( ' . 
           translate ( 'Admin Account Not Found' ) . ' )</span>';
         } ?></td></tr>
    <tr><td class="prompt"><?php etranslate ( 'Application Name' ) ?>:</td>
   <td>   
     <input type="text" size="40" name="form_application_name" id="form_application_name" value="<?php 
           echo $_SESSION['application_name'];?>" /></td></tr>
     <tr><td class="prompt"><?php etranslate( 'Server URL' ) ?>:</td>
   <td>   
     <input type="text" size="40" name="form_server_url" id="form_server_url" value="<?php 
           echo $_SESSION['server_url'];?>" /></td></tr>     
      
   <tr><td class="prompt"><?php etranslate ( 'User Authentication' ) ?>:</td>
   <td>
    <select name="form_user_inc" onchange="auth_handler()">
  <?php
   echo "<option value=\"User\" " .
    ( $settings['user_inc'] == 'User' && 
     $settings['use_http_auth'] != 'true' ? SELECTED : '' ) .
    ">". translate ( 'Web-based via WebCalendar (default)' ) . "</option>\n";
  
   echo "<option value=\"http\" " .
    ( $settings['user_inc'] == 'User' && 
     $settings['use_http_auth'] == 'true' ? SELECTED : '' ) .
    ">" . translate ( 'Web Server' ) .
    ( empty ( $PHP_AUTH_USER ) ? '(not detected)' : '(detected)' ) .
    "</option>\n";
  
   if ( function_exists ( 'ldap_connect' ) ) {
    echo '<option value="UserLdap" ' .
     ( $settings['user_inc'] == 'UserLdap' ? SELECTED : '' ) .
     ">LDAP</option>\n";
   }
  
   if ( function_exists ( 'yp_match' ) ) {
    echo '<option value="UserNis" ' .
     ( $settings['user_inc'] == 'UserNis' ? SELECTED : '' ) .
     ">NIS</option>\n";
   }

   echo '<option value="UserImap" ' .
     ( $settings['user_inc'] == 'UserImap' ? SELECTED : '' ) .
     ">IMAP</option>\n"; 

   echo '<option value="UserAppJoomla" ' .
     ( $settings['user_inc'] == 'UserAppJoomla' ? SELECTED : '' ) .
     ">Joomla</option>\n";

   echo '<option value="UserAppPostnuke" ' .
     ( $settings['user_inc'] == 'UserAppPostnuke' ? SELECTED : '' ) .
     ">PostNuke</option>\n";
      
   echo '<option value="none" ' .
    ( $settings['user_inc'] == 'User' && 
     $settings['single_user'] == 'true' ? SELECTED : '' ) .
    '>' . translate ( 'None (Single-User)' ) . "</option>\n</select>\n";
  ?>
    </td>
   </tr>
   <tr id="singleuser">
    <td class="prompt">&nbsp;&nbsp;&nbsp;<?php echo 
     $singleUserStr . ' ' . $loginStr ?>:</td>
    <td>
     <input name="form_single_user_login" size="20" value="<?php echo $settings['single_user_login'];?>" /></td>
   </tr>
   <tr id="userapppath">
    <td class="prompt">&nbsp;&nbsp;&nbsp;<?php etranslate ( 'Application Path' ) ?>:</td>
    <td>
     <input name="form_user_app_path" size="40" value="<?php echo $settings['user_app_path'];?>" /></td>
   </tr>
   <tr id="imapserver">
    <td class="prompt">&nbsp;&nbsp;&nbsp;<?php etranslate ( 'IMAP Server' ) ?>:</td>
    <td>
     <input name="form_imap_server" size="40" value="<?php echo $settings['imap_server'];?>" /></td>
   </tr>
   <tr>
    <td class="prompt"><?php etranslate ( 'Read-Only' ) ?>:</td>
    <td>
     <input name="form_readonly" value="true" type="radio"
 <?php echo ( $settings['readonly'] == 'true' )? CHECKED : '';?> /><?php etranslate ( 'Yes' ) ?>
 &nbsp;&nbsp;&nbsp;&nbsp;
 <input name="form_readonly" value="false" type="radio"
 <?php echo ( $settings['readonly'] != 'true' )? CHECKED : '';?> /><?php etranslate ( 'No' ) ?>
     </td>
    </tr>
   <tr>
    <td class="prompt"><?php etranslate ( 'Environment' ) ?>:</td>
    <td>
     <select name="form_mode">
     <?php if ( preg_match ( "/dev/", $settings['mode'] ) )
         $mode = 'dev'; // development
        else
         $mode = 'prod'; //production
     ?>
     <option value="prod" <?php if ( $mode == 'prod' ) 
      echo SELECTED ?>><?php etranslate ( 'Production' ) ?></option>
     <option value="dev" <?php if ( $mode == 'dev' ) 
      echo SELECTED ?>><?php etranslate ( 'Development' ) ?></option>
     </select>
     </td>
    </tr> 
  </table>
 </td></tr>
 <table width="80%"  align="center">
 <tr><td align="center">
  <?php if ( ! empty ( $_SESSION['db_success'] ) && $_SESSION['db_success']  && empty ( $dologin ) ) { ?>
  <input name="action" type="button" value="<?php etranslate ( 'Save Settings' ) ?>" onclick="return validate();" />
   <?php if ( ! empty ( $_SESSION['old_program_version'] ) && 
    $_SESSION['old_program_version'] == PROGRAM_VERSION  && ! empty ( $setup_complete )) { ?>
    <input type="button"  name="action2" value="<?php etranslate ( 'Launch WebCalendar' ) ?>" onclick="window.open('../index.php', 'webcalendar');" />
   <?php }
  } 
  if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
  <input type="button" value="<?php echo $logoutStr ?>"
   onclick="document.location.href='index.php?action=logout'" />
  <?php } ?>
 </form>
 </td></tr></table>
<?php //end of step4 ?>

