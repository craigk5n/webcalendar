<?php 
/*
 * $Id$
 *
 * Page Description:
 * Installation Step 2
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
$notrecImage = '<img src="../images/not_recommended.jpg" alt=""/>&nbsp;';
?>
<table border="1" width="90%" align="center">
 <tr><th class="pageheader" colspan="2">
  <?php echo $wizardStr ?> 2
 </th></tr>
 <tr><td colspan="2" width="50%">
  <?php echo translate ( 'Step2 Intro' )?>.
 </td></tr>
 <tr><th colspan="2" class="header">
  <?php etranslate ( 'Database Status' ) ?>
 </th></tr>
 <tr><td>
  <ul>
<?php if ( ! empty ( $_SESSION['db_success'] ) && $_SESSION['db_success']  ) { 
   if ( ! empty ( $response_msg )  && empty ( $response_msg2 ) ) { ?>
  <li class="recommended"><img src="../images/recommended.gif" alt=""/>&nbsp;<?php 
    echo $response_msg; ?></li>
   <?php } elseif ( empty ( $response_msg2 )&& empty ( $_SESSION['db_success'] ) ) {?>
  <li class="notrecommended"><?php echo $notrecImage . '<b>' 
	  . translate ( 'Please Test Settings' ) ?></b></li>  
  <?php } 
 } else { ?>
  <li class="notrecommended"><?php echo $notrecImage . translate ( 'Your current database settings are not...' ) ?>.</li>
  <?php if ( ! empty ( $response_msg ) ) { ?>
  <li class="notrecommended"><?php echo $notrecImage . $response_msg; ?></li>
   <?php }
 } 
 if (  ! empty ( $response_msg2 ) ) { ?>
  <li class="notrecommended"><?php echo $notrecImage . '<b>' .$response_msg2; ?></b></li>  
<?php }  ?>
</ul>
</td></tr>
<tr><th class="header" colspan="2">
 <?php etranslate ( 'Database Settings' ) ?>
</th></tr>
<tr><td>
 <form action="index.php" method="post" name="dbform" onsubmit="return chkPassword()">
 <table align="right" width="100%" border="0">
  <tr><td rowspan="8" width="20%">&nbsp;
   </td><td class="prompt" width="25%" valign="bottom">
   <label for="db_type"><?php etranslate ( 'Database Type' ) ?>:</label></td><td valign="bottom">
   <select name="form_db_type" id="db_type" onchange="db_type_handler();">
<?php
  $supported = array ();
  if ( function_exists ( 'db2_pconnect' ) )
    $supported['ibm_db2'] = 'IBM DB2 Universal Database';
  if ( function_exists ( 'ibase_connect' ) )
    $supported['ibase'] = 'Interbase';
  if ( function_exists ( 'mssql_connect' ) )
    $supported['mssql'] = 'MS SQL Server';
  if ( function_exists ( 'mysql_connect' ) )
    $supported['mysql'] = 'MySQL';
  if ( function_exists ( 'mysqli_connect' ) )
    $supported['mysqli'] = 'MySQL (Improved)';
  if ( function_exists ( 'OCIPLogon' ) )
    $supported['oracle'] = 'Oracle (OCI)';
  if ( function_exists ( 'odbc_pconnect' ) )
    $supported['odbc'] = 'ODBC';
  if ( function_exists ( 'pg_pconnect' ) )
    $supported['pgsql'] = 'PostgreSQL';
  if ( function_exists ( 'sqlite_open' ) )
    $supported['sqlite'] = 'SQLite';

  asort ( $supported );
  foreach ( $supported as $key => $value ) {
    echo '
     <option value="' . $key . '" '
     . ( $settings['db_type'] == $key ? SELECTED : '' )
     . '>' . $value . '</option>';
  }
  $supported = array ();

?>
   </select>
  </td></tr>
  <tr><td class="prompt">
   <label for="server"><?php etranslate ( 'Server' ) ?>:</label></td><td colspan="2">
   <input name="form_db_host" id="server" size="20" value="<?php echo $settings['db_host'];?>" />
  </td></tr>
  <tr><td class="prompt">
   <label for="login"><?php echo $loginStr ?>:</label></td><td colspan="2">
   <input name="form_db_login" id="login" size="20" value="<?php echo $settings['db_login'];?>" />
  </td></tr>
  <tr><td class="prompt">
   <label for="pass"><?php echo $passwordStr ?>:</label></td><td colspan="2">
   <input name="form_db_password" id="pass"  size="20" value="<?php echo $settings['db_password'];?>" />
  </td></tr>
  <tr><td class="prompt" id="db_prefix">
   <label for="prefix"><?php echo $datebasePrefixStr ?>:</label></td><td colspan="2">
   <input name="form_db_prefix" id="prefix" size="20" value="<?php echo $settings['db_prefix'];?>" />
  </td></tr>
  <tr><td class="prompt" id="db_name">
   <label for="database"><?php echo $datebaseNameStr ?>:</label></td><td colspan="2">
   <input name="form_db_database" id="database" size="20" value="<?php echo $settings['db_database'];?>" />
  </td></tr>

<?php  
  if ( substr( php_sapi_name(), 0, 3) <> 'cgi' && 
        ini_get( $settings['db_type'] . '.allow_persistent' ) ){ ?>
  <tr><td class="prompt">
   <label for="conn_pers"><?php etranslate ( 'Connection Persistence' ) ?>:</label></td><td colspan="2">
   <label><input name="form_db_persistent"  id="conn_pers" value="true" type="radio"<?php 
    echo ( $settings['db_persistent'] == 'true' ) ? CHECKED : ''; ?> /><?php etranslate ( 'Enabled' ) ?></label>
  &nbsp;&nbsp;&nbsp;&nbsp;
   <label><input name="form_db_persistent" value="false" type="radio"<?php 
    echo ( $settings['db_persistent'] != 'true' )? CHECKED : ''; ?> /><?php etranslate ( 'Disabled' ) ?></label>
<?php } else { // Need to set a default value ?>
   <input name="form_db_persistent" value="false" type="hidden" />
<?php } ?>
  </td></tr>
  <?php if ( function_exists ( 'file_get_contents' ) ) { ?>
  <tr><td class="prompt"><?php echo $cachedirStr ?>:</td>
   <td><?php if ( empty ( $settings['db_cachedir'] ) ) $settings['db_cachedir'] = '';  ?>
   <input  type="text" size="70" name="form_db_cachedir" id="form_db_cachedir" value="<?php 
     echo $settings['db_cachedir']; ?>"/></td></tr>  
<?php } //end test for file_get_contents 
   if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
 <tr><td align="center" colspan="3">
  <?php 
    $class = ( ! empty ( $_SESSION['db_success'] ) ) ?
      'recommended' : 'notrecommended';
    echo "<input name=\"action\" type=\"submit\" value=\"" . 
      $testSettingsStr . "\" class=\"$class\" />\n";

   if ( ! empty ( $_SESSION['db_noexist'] ) &&  empty ( $_SESSION['db_success'] ) ){
       echo "<input name=\"action2\" type=\"submit\" value=\"" . 
       $createNewStr. "\" class=\"recommended\" />\n";
   } 
  ?>
</td></tr>
</table>
</form> 
</td></tr></table>

<?php } ?>

<table border="0" width="90%" align="center">
<tr><td align="right" width="40%">
  <form action="index.php?action=switch&amp;page=1" method="post">
    <input type="submit" value="<- <?php echo $backStr ?>" />
  </form>
</td><td align="center" width="20%">
  <form action="index.php?action=switch&amp;page=3" method="post">
    <input type="submit" value="<?php echo $nextStr ?> ->" <?php echo ( ! empty ($_SESSION['db_success'] )? '' : 'disabled' ); ?> />
  </form>
</td><td align="left" width="40%">
  <form action="" method="post">
 <input type="button" value="<?php echo $logoutStr ?>" <?php echo ( ! empty ($_SESSION['validuser'] )? '' : 'disabled' ); ?> 
  onclick="document.location.href='index.php?action=logout'" />
  </form>
</td></tr>
</table>
<?php //end of step2 ?>

