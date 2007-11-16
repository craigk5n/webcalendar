<?php 
/*
 * $Id$
 *
 * Page Description:
 * Installation Step 1
 */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// Session check
$_SESSION['check'] = 0;
$_SESSION['check']++;
$session_check = ( $_SESSION['check'] == 1 );

//[0]Display Text  [1]ini_get name [2]required value [3]ini_get string search value
$php_settings = array (
  array ( translate ( 'Display Errors' ), 'display_errors', 'ON', false),
  array ( translate ( 'File Uploads' ), 'file_uploads', 'ON', false),
  array ( translate ( 'Allow URL fopen' ), 
	  'allow_url_fopen', 'ON', false),
  array ( translate ('Safe Mode'), 'safe_mode', 'OFF', false) 
);
//Add 'Safe Mode Allowed Vars' if 'Safe Mode' is enabled
if ( get_php_setting ( 'safe_mode' )== 'ON' )
  $php_settings[] =  array ( 
    translate ('Safe Mode Allowed Vars'),
	  'safe_mode_allowed_env_vars', 'TZ', 'TZ');
		
// set up array to test for some constants (display name, constant name, preferred value )
$php_constants = array (
  //array (' CRYPT_STD_DES', CRYPT_STD_DES, 1) 
  //future expansion
  // array ('CRYPT_STD_DES',CRYPT_STD_DES, 1)
  // array ('CRYPT_MD5',CRYPT_MD5, 1)
  // array ('CRYPT_BLOWFISH',CRYPT_BLOWFISH, 1)
  );
$gdstring = 'GD  (' . translate ( 'needed for Gradient Image Backgrounds' ) . ')';
$captchastring = 'imagecreatetruecolor  (' . translate ( 'needed for CAPTCHA capability' ) . ')';
$xmlstring = translate ( 'Simple XML' );
$filegetstring = translate ( 'File Get Contents' );
$php_modules = array (
  array ($gdstring,'imagepng,imagegif','ON'),
  array ($captchastring,'imagecreatetruecolor','ON'),
  array ($xmlstring,'simplexml_load_string','ON'),
	array ($filegetstring,'file_get_contents','ON'),
);
$recImage = '<img src="../images/recommended.gif" alt=""/>&nbsp;';
$notrecImage = '<img src="../images/not_recommended.jpg" alt=""/>&nbsp;';
?>
<table border="1" width="90%" align="center">
<tr><th class="pageheader"  colspan="2"><?php echo $wizardStr ?> 1</th></tr>
<tr><td colspan="2" width="50%">
<?php etranslate ( 'This installation wizard will guide you...' ) ?>:<br />
<a href="../docs/WebCalendar-SysAdmin.html" target="_blank">System Administrator's Guide</a>,
<a href="../docs/WebCalendar-SysAdmin.html#faq" target="_blank">FAQ</a>,
<a href="../docs/WebCalendar-SysAdmin.html#trouble" target="_blank">Troubleshooting</a>,
<a href="../docs/WebCalendar-SysAdmin.html#help" target="_blank">Getting Help</a>,
<a href="../UPGRADING.html" target="_blank">Upgrading Guide</a>
</td></tr>
<tr><th class="header"  colspan="2"><?php etranslate ( 'PHP Version Check' ) ?></th></tr>
<tr><td>
<?php etranslate ( 'Check to see if PHP 4.1.0 or greater is installed' ) ?>. 
</td>
  <?php
    $class = ( version_compare(phpversion(), '4.1.0', '>=') ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class='recommended') {
      echo $recImage;
    } else {
      echo $notrecImage;
    }
    echo translate ( 'PHP version') . ' ' . phpversion();
   ?>
</td></tr>
<tr><th class="header" colspan="2">
 <?php etranslate ( 'PHP Settings' );
 if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
  &nbsp;<input name="action" type="button" value="<?php etranslate ( 'Detailed PHP Info' ) ?>" onclick="testPHPInfo()" />
<?php } ?>
</th></tr>
<?php foreach ( $php_settings as $setting ) { ?>
  <tr><td class="prompt"><?php echo $setting[0];?></td>
  <?php
    $ini_get_result = get_php_setting ( $setting[1], $setting[3] );
    $class = ( $ini_get_result == $setting[2] ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class == 'recommended') {
      echo $recImage;
    } else {
      echo $notrecImage;
    }
    etranslate ( $ini_get_result );
   ?>
   </td></tr>
<?php }
 foreach ( $php_constants as $constant ) { ?>
  <tr><td class="prompt"><?php echo $constant[0];?></td>
  <?php
    $class = (  $constant[1] ) == $constant[2]  ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class == 'recommended') {
      echo $recImage . translate ( 'ON' );
    } else {
      echo $notrecImage . translate ( 'OFF' );
    }
   ?>
   </td></tr>
<?php }  

 foreach ( $php_modules as $module ) { ?>
  <tr><td class="prompt"><?php echo $module[0];?></td>
  <?php
    $class = ( get_php_modules ( $module[1] ) == $module[2] ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ($class == 'recommended') {
      echo $recImage;
    } else {
      echo $notrecImage;
    }
    etranslate ( get_php_modules ( $module[1] ) );
   ?>
   </td></tr>
<?php } ?>  

 <tr><th class="header" colspan="2"><?php etranslate ( 'Session Check' ) ?></th></tr>
 <tr><td>
  <?php echo translate ( 'PHP Sessions required...' ) ?></td>
<?php
    $class = ( $session_check ) ?
      'recommended' : 'notrecommended';
    echo "<td class=\"$class\">";
    if ( $session_check ) {
     echo $recImage . translate ( 'OK' );
    } else {
      echo $notrecImage . translate ( 'OFF' );
    }
?>
 </td></tr>
<?php //if the settings file doesn't exist or we can't write to it, echo an error header..
$class = ( ! $exists || ! $canWrite ? 'redheader' : 'header') ; ?>
 <tr><th class="<?php echo $class ?>" colspan="2"><?php echo 
   translate ( 'Settings.php Status' ) 
	 . ( $class == 'redheader' ? ': ' . translate ( 'Error' ) : '' )?></th></tr>
<?php 
 //if the settings file exists, but we can't write to it..
 if ( $exists && ! $canWrite ) { ?>
  <tr><td>
   <img src="../images/not_recommended.jpg" alt=""/>&nbsp;<?php 
     etranslate ( 'The file permissions of <b>settings.php</b> are set...' ) ?>:</td><td>
   <blockquote><b>
    <?php echo realpath ( $file ); ?>
   </b></blockquote>
  </td></tr>
<?php //or, if the settings file doesn't exist & we can't write to the includes directory..
 } else if ( ! $exists && ! $canWrite ) { ?>
  <tr><td colspan="2">
   <img src="../images/not_recommended.jpg" alt=""/>&nbsp;<?php 
     etranslate ( 'The file permissions of the <b>includes</b> directory are set...' ) ?>:
   <blockquote><b>
    <?php echo realpath ( $fileDir ); ?>
   </b></blockquote>
  </td></tr>
<?php //if settings.php DOES exist & we CAN write to it..
 } else { ?>
  <tr><td>
   <?php etranslate ( 'Your <b>settings.php</b> file appears to be valid' ) 
     ?>.</td><td class="recommended">
   <img src="../images/recommended.gif" alt=""/>&nbsp;<?php etranslate ( 'OK' )?>
  </td></tr>

<?php if (  empty ( $_SESSION['validuser'] ) ) { ?>
 <tr><th colspan="2" class="header"><?php 
   etranslate ( 'Configuration Wizard Password' ) ?></th></tr>
 <tr><td colspan="2" align="center" style="border:none">
 <?php if ( $doLogin ) { ?>
  <form action="index.php" method="post" name="dblogin">
   <table>
    <tr><th>
     <?php echo $passwordStr ?>:</th><td>
     <input name="password3" type="password" />
     <input type="submit" value="<?php echo $loginStr ?>" />
    </td></tr>
   </table>
  </form>
 <?php } else if ( $forcePassword ) { ?>
  <form action="index.php" method="post" name="dbpassword">
   <table border="0">
    <tr><th colspan="2" class="header">
     <?php etranslate ( 'Create Settings File Password' ) ?>
    </th></tr>
    <tr><th>
     <?php echo $passwordStr ?>:</th><td>
     <input name="password1" type="password" />
    </td></tr>
    <tr><th>
     <?php etranslate ( 'Password (again)' ) ?>:</th><td>
     <input name="password2" type="password" />
    </td></tr>
    <tr><td colspan="2" align="center">
     <input type="submit" value="<?php etranslate ( 'Set Password' ) ?>" />
    </td></tr>
   </table>
  </form>
 <?php }
  }
} ?> 
</td></tr></table>
<?php if ( ! empty ( $_SESSION['validuser'] ) ) { ?>
<table border="0" width="90%" align="center">
 <tr><td align="center">
  <form action="index.php?action=switch&amp;page=2" method="post">
   <input type="submit" value="<?php echo $nextStr ?> ->" />
  </form>
 </td></tr>
</table>
<?php }//end of step1 ?>

