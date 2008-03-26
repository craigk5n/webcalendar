<?php 
/*
 * $Id$
 *
 * Page Description:
 * Installation Step 3
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$_SESSION['db_updated'] = false;
if ( $_SESSION['old_program_version'] == _WEBCAL_PROGRAM_VERSION  && 
   empty ( $_SESSION['blank_database'] ) ){
   $response_msg = translate ( 'database up to date...' );
  $_SESSION['db_updated'] = true; 
  } else if ( $_SESSION['old_program_version'] == 'new_install' ) {
   $response_msg = translate ( 'This appears to be a new installation. If this is not correct, please') . ' ' .
      translate ( 'go back to the previous page and correct your settings' ) . '.';  
  } else if ( ! empty ( $_SESSION['blank_database'] ) ){
   $response_msg =translate ( 'The database requires some data input' ) . '. ' . 
      translate ( 'Click <b>Update Database</b> to complete the upgrade' ) . '.';  
  } else {
     $response_msg = translate ( 'This appears to be an upgrade from version' )  . 
     '&nbsp;' .   $_SESSION['old_program_version'] . '&nbsp;' .
     translate ( 'to' ) . ' ' .  _WEBCAL_PROGRAM_VERSION . '.';
  }
?>
<table border="1" width="90%" align="center">
<tr><th class="pageheader" colspan="2"><?php echo $wizardStr ?> 3</th></tr>
<tr><td colspan="2" width="50%">
<?php echo translate ( 'Step3 Intro' ) ?>
</td></tr>
<tr><th colspan="2" class="header"><?php etranslate ( 'Database Status' ) ?></th></tr>
<tr><td>
<?php echo $response_msg; ?>
</td></tr>
<?php if ( ! empty ( $_SESSION['db_updated'] ) ){ ?>
<tr><th colspan="2" class="header"><?php etranslate ( 'No database actions are required' ) ?></th></tr>
<?php } else { ?>
<tr><th colspan="2" class="redheader"><?php etranslate ( 'The following database actions are required' ) ?></th></tr>
 <?php if ( $settings['db_type']  == 'odbc' &&  empty ( $_SESSION['db_updated'] ) ) {
 if ( empty ( $_SESSION['odbc_db'] ) ) $_SESSION['odbc_db'] = 'mysql'; ?>
<tr><td id="odbc_db" align="center" nowrap>
<form action="index.php?action=set_odbc_db" method="post" name="set_odbc_db">
<b><?php etranslate ( 'ODBC Underlying Database' ) ?>:</b> <select name="odbc_db"  onchange="document.set_odbc_db.submit();">
  <option value="mysql"
   <?php echo $_SESSION['odbc_db'] == 'mysql'? SELECTED : '' ; ?> >MySQL</option>
  <option value="mssql"
   <?php echo $_SESSION['odbc_db'] == 'mssql'? SELECTED : '' ; ?> >MS SQL</option>
  <option value="oracle"
   <?php echo $_SESSION['odbc_db'] == 'oracle'? SELECTED : '' ; ?> >Oracle</option>
  <option value="pgsql"
  <?php echo $_SESSION['odbc_db'] == 'pgsql'? SELECTED : '' ; ?> >PostgreSQL</option>
  <option value="ibase" 
  <?php echo $_SESSION['odbc_db'] == 'ibase'? SELECTED :''  ; ?> >Interbase</option>
</select>
</form>
</td></tr>
  <?php } ?>
<tr>
  <td class="recommended" align="center">
 <?php if ( ! empty ( $settings['db_type'] ) && empty ( $_SESSION['blank_database'] ) &&
   ( $settings['db_type'] == 'ibase' || $settings['db_type'] == 'oracle' ) ) {
  etranslate ( 'Automatic installation not supported' ) ?>. 
 <?php } else {
  etranslate ( 'This may take several minutes to complete' ) ?>.
	  <form action="index.php?action=install" method="post">
  <?php if ( $_SESSION['old_program_version'] == 'new_install' &&
   empty ( $_SESSION['blank_database'] ) ){ ?>
      <input type="submit" value="<?php etranslate ( 'Install Database' ) ?>" />
    </form>
  <?php } else {//We're doing an upgrade ?>
    <input type="hidden" name="install_file" value="<?php echo $_SESSION['install_file']; ?>" />
      <input type="submit" value="<?php etranslate ( 'Update Database' ) ?>" />
    </form>
  <?php }
 } ?>
 </td></tr>
  <?php if ( ! empty ( $settings['db_type'] ) && $settings['db_type'] != 'sqlite' &&
   empty ( $_SESSION['blank_database'] ) ) { ?>
 <tr><td align="center">
   <form action="index.php?action=install" method="post" name="display">
    <input type="hidden" name="install_file" value="<?php echo $_SESSION['install_file']; ?>" />
   <input type="hidden" name="display_sql" value="1" />
      <input type="submit" value="<?php etranslate ( 'Display Required SQL' ) ?>" /><br />
 <?php if ( ! empty ( $sql_displayStr ) ) { ?>
    <textarea name="displayed_sql" cols="100" rows="12" ><?php echo $sql_displayStr; ?></textarea>
   <br />
      <p class="recommended"><?php 
  etranslate ( 'Return to previous page after processing sql' ) ?>.</p>
 <?php } ?>
  </form>  
  </td></tr>
 <?php } 
} ?>
</table>
<table border="0" width="90%" align="center">
<tr><td align="right" width="40%">
  <form action="index.php?action=switch&amp;page=2" method="post">
    <input type="submit" value="<- <?php echo $backStr ?>" />
  </form>
</td><td align="center" width="20%">
  <form action="index.php?action=switch&amp;page=4" method="post">
    <input type="submit" value="<?php echo $nextStr ?> ->" <?php echo ( empty ($_SESSION['db_updated'] )? 'disabled' : '' ); ?> />
  </form>
</td><td align="left" width="40%">
  <form action="" method="post">
  <input type="button" value="<?php echo $logoutStr ?>" <?php echo ( ! empty ($_SESSION['validuser'] )? '' : 'disabled' ); ?>
   onclick="document.location.href='index.php?action=logout'" />
 </form>
</td></tr>
</table>
<?php //end of step3 ?>

