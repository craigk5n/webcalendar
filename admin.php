<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/date_formats.php';
if ( file_exists ( 'install/default_config.php' ) )
  include_once 'install/default_config.php';

//force the css cache to clear by incrementing webcalendar_csscache cookie
//admin.php will not use this cached css, but we want to make sure it's flushed
$webcalendar_csscache = 1;
if  ( isset ( $_COOKIE['webcalendar_csscache'] ) ) {
  $webcalendar_csscache += $_COOKIE['webcalendar_csscache'];
}
SetCookie ( 'webcalendar_csscache', $webcalendar_csscache );
  
function save_pref( $prefs, $src) {
  global $my_theme;
  while ( list ( $key, $value ) = each ( $prefs ) ) {
    if ( $src == 'post' ) {
      $setting = substr ( $key, 6 );
      $prefix = substr ( $key, 0, 6 );
      if ( $key == 'currenttab')
        continue;
      // validate key name.  should start with "admin_" and not include
      // any unusual characters that might cause SQL injection
      if ( ! preg_match ( '/admin_[A-Za-z0-9_]+$/', $key ) ) {
        die_miserable_death ( 'Invalid admin setting name "' .
          $key . '"' );
      }
    } else {
      $setting = $key;
      $prefix = 'admin_';    
    }  
    if ( strlen ( $setting ) > 0 && $prefix == 'admin_' ) {
      if ( $setting == 'THEME' &&  $value != 'none' )
        $my_theme = strtolower ( $value );
      $setting = strtoupper ( $setting );
      $sql = 'DELETE FROM webcal_config WHERE cal_setting = ?';
      if ( ! dbi_execute ( $sql, array( $setting ) ) ) {
        $error = db_error ( false, $sql );
        break;
      }
      if ( strlen ( $value ) > 0 ) {
        $sql = 'INSERT INTO webcal_config ' .
          '( cal_setting, cal_value ) VALUES ( ?, ? )';
        if ( ! dbi_execute ( $sql, array( $setting, $value ) ) ) {
          $error = db_error ( false, $sql );
          break;
        }
      }
    }
  }
  // Reload preferences so any css changes will take effect
  load_global_settings ();
  load_user_preferences ();  
}

$error = '';
$currenttab = '';

if ( ! $is_admin ) {
  $error = print_not_auth ();
}

if ( ! empty ( $_POST ) && empty ( $error )) {
  $my_theme = '';
  $currenttab = getPostValue ( 'currenttab' );    
  if ( $error == '' ) {
    save_pref ( $_POST, 'post' );
  }
  
  if ( ! empty ( $my_theme ) ) {
    $theme = 'themes/'. strtolower ( $my_theme ). '.php';
    include_once $theme;
    save_pref ( $webcal_theme, 'theme' );  
  }
}  

//load any new config settings. Existing ones will not be affected
//this function is in the install/default_config.php file
if ( function_exists ( 'db_load_config' ) && empty ( $_POST )  )
  db_load_config ();

$res = dbi_execute ( 'SELECT cal_setting, cal_value FROM webcal_config' );
$s = array ();
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $setting = $row[0];
    $value = $row[1];
    $s[$setting] = $value;
  }
  dbi_free_result ( $res );
}
//get list of theme files from /themes directory
$themes = array();
$dir = 'themes';
if (is_dir($dir)) {
   if ($dh = opendir($dir)) {
       while (($file = readdir($dh)) !== false) {
         if ( strpos ( $file, '_admin.php' ) ) {
           $themes[0][] = strtoupper( str_replace ( '_admin.php', '', $file ) );
           $themes[1][] = strtoupper( str_replace ( '.php', '', $file ) );
        } else if ( strpos ( $file, '_pref.php' ) ) {
           $themes[0][] = strtolower( str_replace ( '_pref.php', '', $file ) );
           $themes[1][] = strtolower( str_replace ( '.php', '', $file ) );
        }
       }
       sort ( $themes );
       closedir($dh);
   }
}

//get list of menu themes
$menuthemes = array();
$dir = 'includes/menu/themes/';
if ( is_dir( $dir ) ) {
   if ( $dh = opendir( $dir ) ) {
       while ( ( $file = readdir( $dh ) ) !== false ) {
         if ( $file == '.' || $file == '..' || $file == 'CVS' ) continue;
         if ( is_dir ( $dir.$file ) ) $menuthemes[] = $file;
       }
       closedir($dh);
   }
}

//make globals values passed to styles.php are for this user
//makes the demo calendar and Page title accurate
$GLOBALS['APPLICATION_NAME'] = $s['APPLICATION_NAME'];
$GLOBALS['TODAYCELLBG'] = $s['TODAYCELLBG'];
$GLOBALS['TABLEBG'] = $s['TABLEBG'];
$GLOBALS['THBG'] = $s['THBG'];
$GLOBALS['THFG'] = $s['THFG'];
$GLOBALS['CELLBG'] = $s['CELLBG'];
$GLOBALS['WEEKENDBG'] = $s['WEEKENDBG'];
$GLOBALS['OTHERMONTHBG'] = $s['OTHERMONTHBG'];
$GLOBALS['FONTS'] = $s['FONTS'];
$GLOBALS['MYEVENTS'] = $s['MYEVENTS'];
$GLOBALS['BGCOLOR'] = $s['BGCOLOR'];
$GLOBALS['TEXTCOLOR'] = $s['TEXTCOLOR'];
$GLOBALS['H2COLOR'] = $s['H2COLOR'];
$GLOBALS['HASEVENTSBG'] = $s['HASEVENTSBG'];
$GLOBALS['WEEKNUMBER'] = $s['WEEKNUMBER'];
$GLOBALS['MENU_THEME'] = $s['MENU_THEME'];


//determine if we can set timezones, if not don't display any options
$can_set_timezone = set_env ( 'TZ', $s['SERVER_TIMEZONE'] );

$checked = ' checked="checked" ';
$selected = ' selected="selected" ';
$select = translate( 'Select' ) . '...';
$option = '</option>' . "\n";

$openStr ="\"window.open('edit_template.php?type=%s','cal_template','dependent,menubar,scrollbars,height=500,width=500,outerHeight=520,outerWidth=520');\"";
$choices = array ( 'day.php', 'week.php', 'month.php', 'year.php' );
$choices_text = array ( translate ( 'Day' ), translate ( 'Week' ),
  translate ( 'Month' ), translate ( 'Year' ) );
//determine if allow_url_fopen is enabled
$allow_url_fopen = preg_match ( "/(On|1|true|yes)/i", ini_get ( 'allow_url_fopen' ) );

$BodyX = 'onload="popup_handler(); public_handler(); eu_handler(); sr_handler(); attach_handler(); comment_handler(); email_handler();';
$BodyX .= ( ! empty ( $currenttab ) ? "showTab( '". $currenttab . "' );\"" : '"' );
$INC = array('js/admin.php','js/visible.php');
//We need to load CSS inline so we can override GLOBALS
print_header ( $INC, '', $BodyX, false, false );
include "includes/styles.php";
?>

<h2><?php etranslate( 'System Settings' )?>&nbsp;<img src="images/help.gif" alt="<?php etranslate( 'Help' )?>" class="help" onclick="window.open ( 'help_admin.php', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<form action="admin.php" method="post" onsubmit="return valid_form(this);" name="prefform">
<?php if ( ! $error ) {
  echo display_admin_link() . "&nbsp;&nbsp;&nbsp;\n";
?>
<input type="hidden" name="currenttab" id="currenttab" value="<?php echo $currenttab ?>" />
<input type="submit" value="<?php etranslate( 'Save' )?>" name="" />
<br/><br/>

<!-- TABS -->
<div id="tabs">
 <span class="tabfor" id="tab_settings"><a href="" onclick="return setTab('settings')"><?php etranslate( 'Settings' )?></a></span>
 <span class="tabbak" id="tab_public"><a href="" onclick="return setTab('public')"><?php etranslate( 'Public Access' )?></a></span>
 <span class="tabbak" id="tab_uac"><a href="" onclick="return setTab('uac')"><?php etranslate ( 'User Access Control' )?></a></span>
 <span class="tabbak" id="tab_groups"><a href="" onclick="return setTab('groups')"><?php etranslate( 'Groups' )?></a></span>
 <span class="tabbak" id="tab_nonuser"><a href="" onclick="return setTab('nonuser')"><?php etranslate( 'NonUser Calendars' )?></a></span>
 <span class="tabbak" id="tab_other"><a href="" onclick="return setTab('other')"><?php etranslate( 'Other' )?></a></span>
 <span class="tabbak" id="tab_email"><a href="" onclick="return setTab('email')"><?php etranslate( 'Email' )?></a></span>
 <span class="tabbak" id="tab_colors"><a href="" onclick="return setTab('colors')"><?php etranslate( 'Colors' )?></a></span>
</div>

<!-- TABS BODY -->
<div id="tabscontent">
 <!-- DETAILS -->
 <div id="tabscontent_settings">
<fieldset>
 <legend><?php etranslate('System options')?></legend>
 <table width="100%">
 <tr><td class="tooltip" title="<?php etooltip( 'app-name-help' )?>">
  <label for="admin_APPLICATION_NAME"><?php etranslate( 'Application Name' )?>:</label></td><td>
  <input type="text" size="40" name="admin_APPLICATION_NAME" id="admin_APPLICATION_NAME" value="<?php 
   echo htmlspecialchars ( $s['APPLICATION_NAME'] );
  ?>" />&nbsp;&nbsp;
  <?php if ( $s['APPLICATION_NAME'] == 'Title' )
    echo translate( 'Translated Name' ) . ' ( ' . translate( 'Title' ) .' )';?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'server-url-help' )?>">
  <label for="admin_SERVER_URL"><?php etranslate( 'Server URL' )?>:</label></td><td>
  <input type="text" size="40" name="admin_SERVER_URL" id="admin_SERVER_URL" value="<?php 
   echo htmlspecialchars ( $s['SERVER_URL'] );
  ?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'home-url-help' )?>">
  <label for="admin_HOME_LINK"><?php etranslate( 'Home URL' )?>:</label></td><td>
  <input type="text" size="40" name="admin_HOME_LINK" id="admin_HOME_LINK" value="<?php 
   echo ( ! empty ( $s['HOME_LINK'] )? htmlspecialchars ( $s['HOME_LINK'] ): '');
  ?>" />
 </td></tr>
 <tr><td class="tooltipselect" title="<?php etooltip( 'language-help' );?>">
  <label for="admin_LANGUAGE"><?php etranslate( 'Language' )?>:</label></td><td>
  <select name="admin_LANGUAGE" id="admin_LANGUAGE">
   <?php
    define_languages (); //load the language list
    reset ( $languages );
    while ( list ( $key, $val ) = each ( $languages ) ) {
     echo '<option value="' . $val . '"';
     if ( $val == $s['LANGUAGE'] ) echo $selected;
     echo '>' . translate ( $key ) . $option;
    }
   ?>
  </select>&nbsp;&nbsp;
  <?php echo translate( 'Your browser default language is' ) . ' ' . 
   translate ( get_browser_language ( true ) )  . '.'; ?>
 </td></tr>
<tr><td><label>
 <?php etranslate( 'Allow user to use themes' )?>:</label></td><td colspan="3">
 <?php echo print_radio ( 'ALLOW_USER_THEMES' ) ?>
</td></tr> 
 <tr><td  class="tooltip" title="<?php etooltip( 'themes-help' );?>">
 <label for="admin_THEME"><?php etranslate( 'Themes' )?>:</label></td><td>
 <select name="admin_THEME" id="admin_THEME">
<?php
  echo '<option disabled="disabled">' . translate( 'AVAILABLE THEMES' ) . $option;
  //always use 'none' as default so we don't overwrite manual settings
  echo '<option  value="none"' . $selected . '>' . translate( 'None' ) . $option;
  for ( $i = 0, $cnt = count ( $themes); $i <= $cnt; $i++ ) {
     echo '<option value="' . $themes[1][$i] . '">' . $themes[0][$i] . $option;
  }
?>
 </select>&nbsp;&nbsp;&nbsp;
 <input type="button" name="preview" value="<?php etranslate ( 'Preview' ) ?>" onclick="return showPreview()" />
 </td></tr> 
 </table>
</fieldset>

<fieldset>
 <legend><?php etranslate('Site customization')?></legend>
 <table>
 <tr><td class="tooltip" title="<?php etooltip( 'custom-script-help' );?>">
  <?php etranslate( 'Custom script/stylesheet' )?>:</td><td>
  <?php echo print_radio ( 'CUSTOM_SCRIPT' ) ?>&nbsp;&nbsp;
  <input type="button" value="<?php etranslate( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'S' ) ?> name="" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'custom-header-help' );?>">
  <?php etranslate( 'Custom header' )?>:</td><td>
  <?php echo print_radio ( 'CUSTOM_HEADER' ) ?>&nbsp;&nbsp;
  <input type="button" value="<?php etranslate( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'H' ) ?> name="" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'custom-trailer-help' );?>">
  <?php etranslate( 'Custom trailer' )?>:</td><td>
  <?php echo print_radio ( 'CUSTOM_TRAILER' ) ?>&nbsp;&nbsp;
  <input type="button" value="<?php etranslate( 'Edit' );?>..." onclick=<?php
    printf ( $openStr, 'T' ) ?> name="" />
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'enable-external-header-help' );?>">
  <?php etranslate( 'Allow external file for header/script/trailer' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_EXTERNAL_HEADER' ) ?>
 </td></tr>

<tr><td><label>
 <?php etranslate( 'Allow user to override header/trailer' )?>:</label></td><td colspan="3">
 <?php echo print_radio ( 'ALLOW_USER_HEADER' ) ?>
</td></tr>
 </table>
</fieldset>


<fieldset>
 <legend><?php etranslate('Date and Time')?></legend>
 <table>
  <?php if ( $can_set_timezone == true ) { ?>
 <tr><td class="tooltipselect" title="<?php etooltip( 'tz-help' )?>">
  <label for="admin_SERVER_TIMEZONE"><?php etranslate( 'Server Timezone Selection' )?>:</label></td><td>
  <?php
   $tz_offset = date('Z') /ONE_HOUR;
   echo print_timezone_select_html ( 'admin_', $s['SERVER_TIMEZONE']);
   echo  "&nbsp;&nbsp;" . translate( 'Your current GMT offset is' ) . '&nbsp;' .
       $tz_offset . '&nbsp;' .translate( 'hours' ) . '.';
  ?>
</td></tr>
 <?php } // end $can_set_timezone ?>
 <tr><td class="tooltip" title="<?php etooltip( 'display-general-use-gmt-help' );?>">
  <?php etranslate( 'Display Common Use Date/Times as GMT' )?>:</td><td>
  <?php echo print_radio ( 'GENERAL_USE_GMT' ) ?>
 </td></tr>
 <tr><td class="tooltipselect" title="<?php etooltip( 'date-format-help' );?>">
  <?php etranslate( 'Date format' )?>:</td><td>
  <select name="admin_DATE_FORMAT">
   <?php
    for ( $i = 0, $cnt = count ( $datestyles ); $i < $cnt; $i += 2 ) {
     echo '<option value="' . $datestyles[$i] . '"';
     if ( $s['DATE_FORMAT'] == $datestyles[$i] )
      echo $selected;
     echo '>' . $datestyles[$i + 1] . $option;
    }
   ?>
  </select>&nbsp;<?php echo $choices_text[2] . ' ' . $choices_text[0] .
    ' ' . $choices_text[3];?><br />

  <select name="admin_DATE_FORMAT_MY">
   <?php
    for ( $i = 0, $cnt = count ( $datestyles_my ); $i < $cnt; $i += 2 ) {
     echo '<option value="' . $datestyles_my[$i] . '"';
     if ( $s['DATE_FORMAT_MY'] == $datestyles_my[$i] )
      echo $selected;
     echo '>' . $datestyles_my[$i + 1] . $option;
    }
   ?>
  </select>&nbsp;<?php echo $choices_text[2] .' ' . $choices_text[3];?><br />

  <select name="admin_DATE_FORMAT_MD">
   <?php
    for ( $i = 0, $cnt = count ( $datestyles_md ); $i < $cnt; $i += 2 ) {
     echo '<option value="' . $datestyles_md[$i] . '"';
     if ( $s['DATE_FORMAT_MD'] == $datestyles_md[$i] )
      echo $selected;
     echo '>' . $datestyles_md[$i + 1] . $option;
    }
   ?>
  </select>&nbsp;<?php echo $choices_text[2] .' ' . $choices_text[0];?><br />

  <select name="admin_DATE_FORMAT_TASK">
   <?php
    for ( $i = 0, $cnt = count ( $datestyles_task ); $i < $cnt; $i += 2 ) {
     echo '<option value="' . $datestyles_task[$i] . '"';
     if ( $s['DATE_FORMAT_TASK'] == $datestyles_task[$i] )
      echo $selected;
     echo '>' . $datestyles_task[$i + 1] . $option;
    }
   ?>
  </select>&nbsp;<?php echo translate ( 'Small Task Date' );?>
 </td></tr>

	<tr><td class="tooltip" title="<?php etooltip( 'display-week-starts-on' )?>">
	 <?php etranslate( 'Week starts on' )?>:</td><td>
	 <select name="admin_WEEK_START" id="admin_WEEK_START">
	<?php
	 for ( $i = 0; $i < 7; $i++ ) {
		echo "<option value=\"$i\"" .
		 ( $i == $s['WEEK_START'] ? $selected : '' ) .
		 ">" . translate( weekday_name( $i ) ) . "</option>\n";
	 }
	?>
	 </select>
	</td></tr>
	
	<tr><td class="tooltip" title="<?php etooltip( 'display-weekend-starts-on' )?>">
	 <?php etranslate( 'Weekend starts on' )?>:</td><td>
	 <select name="admin_WEEKEND_START" id="admin_WEEKEND_START">
	<?php
	 for ( $i = -1; $i < 6; $i++ ) {
		$j = ( $i == -1 ? 6 : $i ); //make sure start with Saturday
		echo "<option value=\"$j\"" .
		 ( $j == $s['WEEKEND_START'] ? $selected : '' ) .
		 ">" . translate( weekday_name( $j ) ) . "</option>\n";
	 }
	?>
	 </select>
	</td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'time-format-help' )?>">
  <?php etranslate( 'Time format' )?>:</td><td>
  <?php echo print_radio ( 'TIME_FORMAT', 
    array ( '12'=>translate( '12 hour' ), '24'=>translate( '24 hour' ) ) ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'timed-evt-len-help' )?>">
  <?php etranslate( 'Specify timed event length by' )?>:</td><td>
  <?php echo print_radio ( 'TIMED_EVT_LEN', 
    array ('D'=>translate( 'Duration' ), 'E'=>translate( 'End Time' ) ) ) ?>
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'work-hours-help' )?>">
  <?php etranslate( 'Work hours' )?>:</td><td>
  <label for="admin_WORK_DAY_START_HOUR"><?php etranslate( 'From' )?>&nbsp;</label>
  <select name="admin_WORK_DAY_START_HOUR" id="admin_WORK_DAY_START_HOUR">
   <?php
    for ( $i = 0; $i < 24; $i++ ) {
     echo "<option value=\"$i\"" .
      ( $i == $s['WORK_DAY_START_HOUR'] ? $selected : '' ) .
     '>' . display_time ( $i * 10000, 1 ) . $option;
    }
   ?>
  </select>&nbsp;
  <label for="admin_WORK_DAY_END_HOUR"><?php etranslate( 'to' )?>&nbsp;</label>
  <select name="admin_WORK_DAY_END_HOUR" id="admin_WORK_DAY_END_HOUR">
   <?php
    for ( $i = 0; $i < 24; $i++ ) {
     echo "<option value=\"$i\"" .
      ( $i == $s['WORK_DAY_END_HOUR'] ? $selected : '' ) .
     '>' . display_time ( $i * 10000, 1 ) . $option;
    }
   ?>
  </select>
 </td></tr>
</table> 
</fieldset>
<fieldset>
 <legend><?php etranslate('Appearance')?></legend>
 <table>
 <tr><td class="tooltip" title="<?php etooltip('preferred-view-help');?>">
<label for="admin_STARTVIEW"><?php etranslate('Preferred view')?>:</label></td><td>
<select name="admin_STARTVIEW" id="admin_STARTVIEW">
<?php
for ( $i = 0, $cnt = count ( $choices ); $i < $cnt; $i++ ) {
  echo '<option value="' . $choices[$i] . '" ';
  if ( $s['STARTVIEW'] == $choices[$i] )
    echo $selected;
  echo ' >' . $choices_text[$i] . $option;
}
// Allow user to select a view also
for ( $i = 0, $cnt = count ( $views ); $i < $cnt; $i++ ) {
  if ( $views[$i]['cal_is_global'] != 'Y' )
    continue;
  $xurl = $views[$i]['url'];
  echo '<option value="';
  echo $xurl . '" ';
  $xurl_strip = str_replace ( '&amp;', '&', $xurl );
  if ( $s['STARTVIEW'] == $xurl_strip )
    echo $selected;
  echo '>' . $views[$i]['cal_name'] . $option;
}
?>
</select>
 </td></tr>
<tr><td><label>
 <?php etranslate( 'Allow top menu' )?>:</label></td><td colspan="3">
 <?php echo print_radio ( 'MENU_ENABLED' ) ?>
 </td></tr>
 <tr><td><label>
 <?php etranslate( 'Date Selectors position' )?>:</label></td><td colspan="3">
 <?php echo print_radio ( 'MENU_DATE_TOP', 
   array ( 'Y'=>translate ( 'Top' ), 'N'=>translate ( 'Bottom' ) ) ) ?>
 </td></tr> 
 
  <tr><td  class="tooltip" title="<?php etooltip( 'menu-themes-help' );?>">
 <label for="admin_MENU_THEME"><?php etranslate( 'Menu theme' )?>:</label></td><td>
 <select name="admin_MENU_THEME" id="admin_MENU_THEME">
<?php
  echo '<option  value="none"' . $selected . '>' . translate( 'None' ) . $option;
  foreach ( $menuthemes as $menutheme ) {
     echo '<option value="' . $menutheme . '"';
     if ($s['MENU_THEME'] == $menutheme ) echo $selected;
     echo '>' . $menutheme . $option;
  }
?>
 </select>&nbsp;&nbsp;&nbsp;
 </td></tr> 
 <tr><td class="tooltip" title="<?php etooltip( 'fonts-help' ) ?>">
  <label for="admin_FONTS"><?php etranslate( 'Fonts' )?>:</label></td><td>
  <input type="text" size="40" name="admin_FONTS" id="admin_FONTS" value="<?php 
            echo htmlspecialchars ( $s['FONTS'] );
           ?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'display-sm_month-help' );?>">
  <?php etranslate( 'Display small months' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_SM_MONTH' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'display-weekends-help' );?>">
  <?php etranslate( 'Display weekends' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_WEEKENDS' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'display-long-daynames-help' );?>">
  <?php etranslate( 'Display long day names' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_LONG_DAYS' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'display-alldays-help' );?>">
  <?php etranslate( 'Display all days in month view' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) ?>
 </td></tr>
  <tr><td class="tooltip" title="<?php etooltip( 'display-week-number-help' )?>">
  <?php etranslate( 'Display week number' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_WEEKNUMBER' ) ?>
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'display-desc-print-day-help' );?>">
  <?php etranslate( 'Display description in printer day view' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_DESC_PRINT_DAY' ) ?>
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'yearly-shows-events-help' );?>">
  <?php etranslate( 'Display days with events in bold in month and year views' )?>:</td><td>
  <?php echo print_radio ( 'BOLD_DAYS_IN_YEAR' ) ?>
 </td></tr>

<tr><td class="tooltip" title="<?php etooltip("display-minutes-help")?>">
 <?php etranslate( 'Display 00 minutes always' )?>:</td><td>
 <?php echo print_radio ( 'DISPLAY_MINUTES' ) ?>
</td></tr>

  <tr><td class="tooltip" title="<?php etooltip( 'allow-view-add-help' )?>">
  <?php etranslate( 'Include add event link in views' )?>:</td><td>
  <?php echo print_radio ( 'ADD_LINK_IN_VIEWS' ) ?>
 </td></tr>

<tr><td class="tooltip" title="<?php etooltip( 'lunar-help' )?>">
  <?php etranslate( 'Display Lunar Phases in month view' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_MOON_PHASES' ) ?>
 </td></tr>
</table> 
</fieldset>
<fieldset>
 <legend><?php etranslate('Restrictions')?></legend>
 <table>
 <tr><td class="tooltip" title="<?php etooltip( 'allow-view-other-help' )?>">
  <?php etranslate( 'Allow viewing other user&#39;s calendars' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_VIEW_OTHER' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'require-approvals-help' );?>">
  <?php etranslate( 'Require event approvals' )?>:</td><td>
  <?php echo print_radio ( 'REQUIRE_APPROVALS' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'display-unapproved-help' );?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Display unapproved' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_UNAPPROVED' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'conflict-check-help' )?>">
  <?php etranslate( 'Check for event conflicts' )?>:</td><td>
  <?php //this control is logically reversed
    echo print_radio ( 'ALLOW_CONFLICTS',
      array ( 'N'=>translate ( 'Yes' ), 'Y'=>translate ( 'No' ) ) ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'conflict-months-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Conflict checking months' )?>:</td><td>
  <input type="text" size="3" name="admin_CONFLICT_REPEAT_MONTHS" value="<?php echo htmlspecialchars ( $s['CONFLICT_REPEAT_MONTHS'] );?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'conflict-check-override-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Allow users to override conflicts' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_CONFLICT_OVERRIDE' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'limit-appts-help' )?>">
  <?php etranslate( 'Limit number of timed events per day' )?>:</td><td>
  <?php echo print_radio ( 'LIMIT_APPTS' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'limit-appts-number-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Maximum timed events per day' )?>:</td><td>
  <input type="text" size="3" name="admin_LIMIT_APPTS_NUMBER" value="<?php echo htmlspecialchars ( $s['LIMIT_APPTS_NUMBER'] );?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'crossday-help' )?>">
  <?php etranslate( 'Disable Cross-Day Events' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_CROSSDAY_EVENTS' ) ?>
 </td></tr>
 </table>
</fieldset>
<fieldset>
 <legend><?php etranslate('Events')?></legend>
 <table>
  <tr><td class="tooltip" title="<?php etooltip( 'disable-location-field-help' )?>">
  <?php etranslate( 'Disable Location field' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_LOCATION_FIELD' ) ?>
 </td></tr>
  <tr><td class="tooltip" title="<?php etooltip( 'disable-url-field-help' )?>">
  <?php etranslate( 'Disable URL field' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_URL_FIELD' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'disable-priority-field-help' )?>">
  <?php etranslate( 'Disable Priority field' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_PRIORITY_FIELD' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'disable-access-field-help' )?>">
  <?php etranslate( 'Disable Access field' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_ACCESS_FIELD' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'disable-participants-field-help' )?>">
  <?php etranslate( 'Disable Participants field' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_PARTICIPANTS_FIELD' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'disable-repeating-field-help' )?>">
  <?php etranslate( 'Disable Repeating field' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_REPEATING_FIELD' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'allow-html-description-help' )?>">
  <?php etranslate( 'Allow HTML in Description' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_HTML_DESCRIPTION' ) ?>
 </td></tr>
</table>
</fieldset>
<fieldset>
<legend><?php etranslate('Popups')?></legend>
<table>
 <tr><td class="tooltip" title="<?php etooltip( 'disable-popups-help' )?>">
  <?php etranslate( 'Disable Pop-Ups' )?>:</td><td>
  <?php echo print_radio ( 'DISABLE_POPUPS', '', 'popup_handler' ) ?>
 </td></tr>
 <tr id="pop1"><td class="tooltip" title="<?php etooltip( 'popup-includes-siteextras-help' )?>">
  <?php etranslate( 'Display Site Extras in popup' )?>:</td><td>
  <?php echo print_radio ( 'SITE_EXTRAS_IN_POPUP' ) ?>
 </td></tr>
 <tr id="pop2"><td class="tooltip" title="<?php etooltip( 'popup-includes-participants-help' )?>">
  <?php etranslate( 'Display Participants in popup' )?>:</td><td>
  <?php echo print_radio ( 'PARTICIPANTS_IN_POPUP' ) ?>
 </td></tr>
</table>
</fieldset>
<fieldset>
 <legend><?php etranslate('Miscellaneous')?></legend>
 <table>
 <tr><td class="tooltip" title="<?php etooltip( 'remember-last-login-help' )?>">
  <?php etranslate( 'Remember last login' )?>:</td><td>
  <?php echo print_radio ( 'REMEMBER_LAST_LOGIN' ) ?>
 </td></tr>
<tr><td class="tooltip" title="<?php etooltip( 'summary_length-help' )?>">
  <?php etranslate( 'Brief Description Length' )?>:</td><td>
  <input type="text" size="3" name="admin_SUMMARY_LENGTH" value="<?php echo $s['SUMMARY_LENGTH'];?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'user_sort-help' )?>">
  <label for="admin_USER_SORT_ORDER"><?php etranslate( 'User Sort Order' )?>:</label></td><td>
  <select name="admin_USER_SORT_ORDER" id="admin_USER_SORT_ORDER">
   <option value="cal_lastname, cal_firstname" <?php 
    if ( $s['USER_SORT_ORDER'] == "cal_lastname, cal_firstname" ) 
     echo $selected?>><?php etranslate( 'Lastname, Firstname' )?></option>
   <option value="cal_firstname, cal_lastname" <?php 
    if ( $s['USER_SORT_ORDER'] == "cal_firstname, cal_lastname" ) 
     echo $selected?>><?php etranslate( 'Firstname, Lastname' )?></option>
  </select>
 </td></tr>
</table>
</fieldset>
</div>
<!-- END SETTINGS -->


<!--
// <h3><?php etranslate( 'Plugins' )?></h3>
// <table class="standard" cellspacing="1" cellpadding="2">
// <tr><td class="tooltip" title="<?php etooltip( 'plugins-enabled-help' );?>"><?php etranslate( 'Enable Plugins' )?>:</td>
//   <td><?php echo print_radio ( 'PLUGINS_ENABLED' ) ?>
//</td></tr>

// <?php
// if ( $PLUGINS_ENABLED == 'Y' ) {
//   $plugins = get_plugin_list ( true );
//   
//   for ( $i = 0, $cnt = count ( $plugins ); $i < $cnt; $i++ ) {
//     $val = $s[$plugins[$i] . ".plugin_status'];
//     echo '<tr><td class="tooltip" title="' .
//       tooltip('plugins-sort-key-help') . '">&nbsp;&nbsp;&nbsp;' .
//       translate( 'Plugin' ) . ' ' . $plugins[$i] . ":</td>\n";
//     echo '<td><input type="radio" name="admin_' .
//        $plugins[$i] . '_plugin_status" value="Y" ';
//     if ( $val != 'N' ) echo $checked;
//     echo ' /> ' . translate('Yes');
//     echo '<input type="radio" name="admin_' .
//        $plugins[$i] . '_plugin_status" VALUE="N" ';
//     if ( $val == 'N' ) echo $checked;
//     echo ' /> ' . translate('No') . "</td></tr>\n";
//   }
// }
// ?>
//</table>
-->

<!-- BEGIN PUBLIC ACCESS -->

<div id="tabscontent_public">
 <table>
  <tr><td class="tooltip" title="<?php etooltip( 'allow-public-access-help' )?>">
   <?php etranslate( 'Allow public access' )?>:</td><td>
   <?php echo print_radio ( 'PUBLIC_ACCESS', '', 'public_handler' ) ?>
  </td></tr>
  <tr id="pa1"><td class="tooltip" title="<?php etooltip( 'public-access-default-visible' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Public access visible by default' )?>:</td><td>
  <?php echo print_radio ( 'PUBLIC_ACCESS_DEFAULT_VISIBLE' ) ?>
  </td></tr>
  <tr id="pa2"><td class="tooltip" title="<?php etooltip( 'public-access-default-selected' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Public access is default participant' )?>:</td><td>
  <?php echo print_radio ( 'PUBLIC_ACCESS_DEFAULT_SELECTED' ) ?>
  </td></tr>
  <tr id="pa3"><td class="tooltip" title="<?php etooltip( 'public-access-view-others-help' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Public access can view other users' )?>:</td><td>
  <?php echo print_radio ( 'PUBLIC_ACCESS_OTHERS' ) ?>
  </td></tr>
  <tr id="pa4"><td class="tooltip" title="<?php etooltip( 'public-access-can-add-help' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Public access can add events' )?>:</td><td>
   <?php echo print_radio ( 'PUBLIC_ACCESS_CAN_ADD' ) ?>
  </td></tr>
  <tr id="pa5"><td class="tooltip" title="<?php 
    etooltip( 'public-access-add-requires-approval-help' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Public access new events require approval' )?>:</td><td>
  <?php echo print_radio ( 'PUBLIC_ACCESS_ADD_NEEDS_APPROVAL' ) ?>
  </td></tr>
  <tr id="pa6"><td class="tooltip" title="<?php 
   etooltip( 'public-access-sees-participants-help' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Public access can view participants' )?>:</td><td>
  <?php echo print_radio ( 'PUBLIC_ACCESS_VIEW_PART' ) ?>
  </td></tr>
  <tr id="pa7" valign="top"><td class="tooltip" title="<?php 
   etooltip( 'public-access-override-help' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;<?php 
   etranslate( 'Override event name/description for public access' )?>:</td><td>
   <?php echo print_radio ( 'OVERRIDE_PUBLIC' ) ?>
  </td></tr>
  <tr id="pa7a" valign="top"><td class="tooltip" title="<?php 
  etooltip( 'public-access-override-text-help' )?>">
   &nbsp;&nbsp;&nbsp;&nbsp;
   &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Text to display to public access' )?>:</td><td>
   <label><input name="admin_OVERRIDE_PUBLIC_TEXT" value="<?php echo $s['OVERRIDE_PUBLIC_TEXT'];?>" size="25" /></label>
  </td></tr>
</table>
</div>

<!-- BEGIN USER ACCESS CONTROL -->
<div id="tabscontent_uac">
<table>
<tr><td class="tooltip" title="<?php etooltip( 'uac-enabled-help' )?>">
   <?php etranslate( 'User Access Control enabled' )?>:</td><td>
   <?php echo print_radio ( 'UAC_ENABLED' ) ?>
</td></tr>
</table>
</div>


<!-- BEGIN GROUPS -->
<div id="tabscontent_groups">
<table>
 <tr><td class="tooltip" title="<?php etooltip( 'groups-enabled-help' )?>">
  <?php etranslate( 'Groups enabled' )?>:</td><td>
  <?php echo print_radio ( 'GROUPS_ENABLED' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'user-sees-his-group-help' )?>">
  <?php etranslate( 'User sees only his groups' )?>:</td><td>
  <?php echo print_radio ( 'USER_SEES_ONLY_HIS_GROUPS' ) ?>
 </td></tr>
</table>
</div>

<!-- BEGIN NONUSER -->
<div id="tabscontent_nonuser">
<table>
 <tr><td class="tooltip" title="<?php etooltip( 'nonuser-enabled-help' )?>">
  <?php etranslate( 'Nonuser enabled' )?>:</td><td>
  <?php echo print_radio ( 'NONUSER_ENABLED' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'nonuser-list-help' )?>">
  <?php etranslate( 'Nonuser list' )?>:</td><td>
  <?php echo print_radio ( 'NONUSER_AT_TOP',
    array ( 'Y'=>translate ( 'Top' ), 'N'=>translate ( 'Bottom' ) ) ) ?>
</td></tr>
</table>
</div>

<!-- BEGIN REPORTS -->
<div id="tabscontent_other">
<table>
<tr><td class="tooltip" title="<?php etooltip( 'reports-enabled-help' )?>">
 <?php etranslate( 'Reports enabled' )?>:</td><td>
 <?php echo print_radio ( 'REPORTS_ENABLED' ) ?>
</td></tr>


<!-- BEGIN PUBLISHING -->

<tr><td class="tooltip" title="<?php etooltip( 'subscriptions-enabled-help' )?>">
 <?php etranslate( 'Allow remote subscriptions' )?>:</td><td>
 <?php echo print_radio ( 'PUBLISH_ENABLED' ) ?>
</td></tr>
<?php if ( $allow_url_fopen ) { ?>
<tr><td class="tooltip" title="<?php etooltip( 'remotes-enabled-help' )?>">
 <?php etranslate( 'Allow remote calendars' )?>:</td><td>
 <?php echo print_radio ( 'REMOTES_ENABLED' ) ?>
</td></tr>
<?php } ?>
<tr><td class="tooltip" title="<?php etooltip( 'rss-enabled-help' )?>">
 <?php etranslate( 'Enable RSS feed' )?>:</td><td>
 <?php echo print_radio ( 'RSS_ENABLED' ) ?>
</td></tr>


<!-- BEGIN CATEGORIES -->

 <tr><td class="tooltip" title="<?php etooltip( 'categories-enabled-help' )?>">
  <?php etranslate( 'Categories enabled' )?>:</td><td>
  <?php echo print_radio ( 'CATEGORIES_ENABLED' ) ?>
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'icon_upload-enabled-help' )?>">
  <?php etranslate( 'Category Icon Upload enabled' )?>:</td><td>
  <?php echo print_radio ( 'ENABLE_ICON_UPLOADS' ) ?>
  &nbsp;<?php if ( ! is_dir ( 'icons/' ) ) echo '( ' . translate( 'Requires' ) 
    . " 'icons' " . translate( 'folder to exist' ) . ' )'?>
 </td></tr>
 
<!-- Display Task Preferences -->
 <tr><td class="tooltip" title="<?php etooltip( 'display-tasks-help' )?>">
  <?php etranslate( 'Display small task list' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_TASKS' ) ?>
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'display-tasks-in-grid-help' )?>">
  <?php etranslate( 'Display tasks in Calendars' )?>:</td><td>
  <?php echo print_radio ( 'DISPLAY_TASKS_IN_GRID' ) ?>
 </td></tr>

<!-- BEGIN EXT PARTICIPANTS -->

 <tr><td class="tooltip" title="<?php etooltip( 'allow-external-users-help' )?>">
  <?php etranslate( 'Allow external users' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_EXTERNAL_USERS', '', 'eu_handler' ) ?>
 </td></tr>
 <tr id="eu1"><td class="tooltip" title="<?php 
 etooltip( 'external-can-receive-notification-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php 
  etranslate( 'External users can receive email notifications' )?>:</td><td>
  <?php echo print_radio ( 'EXTERNAL_NOTIFICATIONS' ) ?>
 </td></tr>
 <tr id="eu2"><td class="tooltip" title="<?php 
 etooltip( 'external-can-receive-reminder-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php 
  etranslate( 'External users can receive email reminders' )?>:</td><td>
  <?php echo print_radio ( 'EXTERNAL_REMINDERS' ) ?>
 </td></tr>
 
 <!-- BEGIN SELF REGISTRATION -->

 <tr><td class="tooltip" title="<?php etooltip( 'allow-self-registration-help' )?>">
  <?php etranslate( 'Allow self-registration' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_SELF_REGISTRATION', '', 'sr_handler' ) ?>
 </td></tr>
 <tr id="sr1"><td class="tooltip" title="<?php etooltip( 'use-blacklist-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Restrict self-registration to blacklist' )?>:</td><td>
  <?php echo print_radio ( 'SELF_REGISTRATION_BLACKLIST', '', 'sr_handler' ) ?>
 </td></tr>
 <tr id="sr2"><td class="tooltip" title="<?php 
 etooltip( 'allow-self-registration-full-help' )?>">
  &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Use self-registration email notifications' )?>:</td><td>
  <?php echo print_radio ( 'SELF_REGISTRATION_FULL', '', 'sr_handler' ) ?>
 </td></tr>
<!-- TODO add account aging feature -->


 <!-- BEGIN ATTACHMENTS/COMMENTS -->

 <tr><td class="tooltip" title="<?php etooltip( 'allow-attachment-help' )?>">
  <?php etranslate( 'Allow file attachments to events' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_ATTACH', '', 'attach_handler' ) ?>
  <span id="at1">
  <br/><strong>Note:</strong>
  <?php etranslate( 'Admin and owner can always add attachments if enabled' );?><br/>
  <?php echo print_checkbox ( array (
      'ALLOW_ATTACH_PART', 'Y', translate( 'Participant' ) ) );
    echo print_checkbox ( array ( 
      'ALLOW_ATTACH_ANY', 'Y', translate( 'Anyone' ) ) ); ?>
  </span>
 </td></tr>

 <tr><td class="tooltip" title="<?php etooltip( 'allow-comments-help' )?>">
  <?php etranslate( 'Allow comments to events' )?>:</td><td>
  <?php echo print_radio ( 'ALLOW_COMMENTS', '', 'comment_handler' ) ?>
  <br/>
  <span id="com1">
  <br/><strong>Note:</strong>
  <?php etranslate( 'Admin and owner can always add comments if enabled' );?><br/>
  <?php echo print_checkbox ( array (
      'ALLOW_COMMENTS_PART', 'Y', translate( 'Participant' ) ) );
     echo print_checkbox ( array (
      'ALLOW_COMMENTS_ANY', 'Y', translate( 'Anyone' ) ) ); ?>
  </span>
 </td></tr>

 <!-- END ATTACHMENTS/COMMENTS -->

</table>
</div>

<!-- BEGIN EMAIL -->
<div id="tabscontent_email">
<table>
<tr><td class="tooltip" title="<?php etooltip( 'email-enabled-help' )?>">
 <?php etranslate( 'Email enabled' )?>:</td><td>
 <?php echo print_radio ( 'SEND_EMAIL', '', 'email_handler' ) ?>
</td></tr>
<tr id="em1"><td class="tooltip" title="<?php etooltip( 'email-default-sender' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Default sender address' )?>:</td><td>
 <input type="text" size="30" name="admin_EMAIL_FALLBACK_FROM" value="<?php echo htmlspecialchars ( $EMAIL_FALLBACK_FROM );?>" />
</td></tr>

<tr id="em2"><td class="tooltip" title="<?php etooltip( 'email-mailer' )?>">
<?php etranslate( 'Email Mailer' )?>:</td><td>
 <select name="admin_EMAIL_MAILER"  onchange="email_handler()">
   <option value="smtp" <?php if ( $s['EMAIL_MAILER'] == 
     'smtp' ) echo $selected?>>SMTP</option>
   <option value="mail" <?php if ( $s['EMAIL_MAILER'] == 
     'mail' ) echo $selected?>>PHP mail</option>
   <option value="sendmail" <?php if ( $s['EMAIL_MAILER'] == 
     'sendmail' ) echo $selected?>>sendmail</option>
  </select>   
</td></tr>

<tr id="em3"><td class="tooltip" title="<?php etooltip( 'email-smtp-host' )?>">
<?php etranslate( 'SMTP Host name(s)' )?>:</td><td>
 <input type="text" size="50" name="admin_SMTP_HOST" value="<?php echo $s['SMTP_HOST'];?>" />
</td></tr>
<tr id="em3a"><td class="tooltip" title="<?php etooltip( 'email-smtp-port' )?>">
<?php etranslate( 'SMTP Port Number' )?>:</td><td>
 <input type="text" size="4" name="admin_SMTP_PORT" value="<?php echo $s['SMTP_PORT'];?>" />
</td></tr>

<tr id="em4"><td class="tooltip" title="<?php etooltip( 'email-smtp-auth' )?>">
 <?php etranslate( 'SMTP Authentication' )?>:</td><td>
 <?php echo print_radio ( 'SMTP_AUTH', '', 'email_handler' ) ?>
</td></tr>

<tr id="em5"><td class="tooltip" title="<?php etooltip( 'email-smtp-username' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'SMTP Username' )?>:</td><td>
 <input type="text" size="30" name="admin_SMTP_USERNAME" value="<?php echo ( ! empty ( $s['SMTP_USERNAME'] ) ? $s['SMTP_USERNAME']:'');?>" />
</td></tr>

<tr id="em6"><td class="tooltip" title="<?php etooltip( 'email-smtp-password' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'SMTP Password' )?>:</td><td>
 <input type="text" size="30" name="admin_SMTP_PASSWORD" value="<?php echo ( ! empty ($s['SMTP_PASSWORD'])?$s['SMTP_PASSWORD']:'');?>" />
</td></tr>

<tr id="em7"><td colspan="2" class="bold">
 <?php etranslate( 'Default user settings' )?>:
</td></tr>
<tr id="em8"><td class="tooltip" title="<?php etooltip( 'email-event-reminders-help' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Event reminders' )?>:</td><td>
 <?php echo print_radio ( 'EMAIL_REMINDER' ) ?>
</td></tr>
<tr id="em9"><td class="tooltip" title="<?php etooltip( 'email-event-added' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Events added to my calendar' )?>:</td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_ADDED' ) ?>
</td></tr>
<tr id="em10"><td class="tooltip" title="<?php etooltip( 'email-event-updated' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Events updated on my calendar' )?>:</td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_UPDATED' ) ?>
</td></tr>
<tr id="em11"><td class="tooltip" title="<?php etooltip( 'email-event-deleted' );?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Events removed from my calendar' )?>:</td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_DELETED' ) ?>
</td></tr>
<tr id="em12"><td class="tooltip" title="<?php etooltip( 'email-event-rejected' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Event rejected by participant' )?>:</td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_REJECTED' ) ?>
</td></tr>
<tr id="em13"><td class="tooltip" title="<?php etooltip( 'email-event-create' )?>">
 &nbsp;&nbsp;&nbsp;&nbsp;<?php etranslate( 'Event that I create' )?>:</td><td>
 <?php echo print_radio ( 'EMAIL_EVENT_CREATE' ) ?>
</td></tr>
</table>
</div>

<!-- BEGIN COLORS -->
<div id="tabscontent_colors">
<fieldset>
 <legend><?php etranslate('Color options')?></legend>
<table   width="100%">
<tr><td width="30%"><label>
 <?php etranslate( 'Allow user to customize colors' )?>:</label></td><td colspan="5">
 <?php echo print_radio ( 'ALLOW_COLOR_CUSTOMIZATION' ) ?>
</td></tr>
<tr><td class="tooltip" title="<?php etooltip( 'gradient-colors' )?>"><label>
 <?php etranslate( 'Enable gradient images for background colors' )?>:</label></td><td colspan="5">
<?php if ( function_exists ( 'imagepng' ) || function_exists ( 'imagegif' )) { 
  echo print_radio ( 'ENABLE_GRADIENTS' );
 } else {
   etranslate( 'Not available');
 } ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'BGCOLOR',
   translate( 'Document background' ) ) ?>
</td>
<td rowspan="15" width="1%">&nbsp;</td>
<td rowspan="15" width="45%" class="aligncenter ligntop">
<!-- BEGIN EXAMPLE MONTH -->
<table style="width:90%; background-color:<?php echo $BGCOLOR?>"><tr>
<td width="1%" rowspan="3">&nbsp;</td>
<td style="text-align:center; color:<?php 
  echo $H2COLOR?>; font-weight:bold;"><?php
  echo date_to_str ( date ('Ymd'), $DATE_FORMAT_MY, false );?></td>
<td width="1%" rowspan="3">&nbsp;</td></tr>
<tr><td bgcolor="<?php echo $BGCOLOR?>">
<?php 
set_today( date ('Ymd') );
echo display_month ( date ('m') , date('Y') , true);
?>
</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
<!-- END EXAMPLE MONTH -->
</td>
</tr>
<tr><td>
 <?php echo print_color_input_html ( 'H2COLOR',
   translate( 'Document title' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'TEXTCOLOR', 
   translate( 'Document text' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'MYEVENTS', 
   translate( 'My event text' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'TABLEBG', 
   translate( 'Table grid color' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'THBG', 
   translate( 'Table header background' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'THFG', 
   translate( 'Table header text' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'CELLBG', 
  translate( 'Table cell background' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'TODAYCELLBG', 
  translate( 'Table cell background for current day' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'HASEVENTSBG', 
  translate( 'Table cell background for days with events' ) ) ?>
</td></tr>
<tr><td>
  <?php echo print_color_input_html ( 'WEEKENDBG', 
    translate( 'Table cell background for weekends' ) ) ?>
</td></tr>
<tr><td>
  <?php echo print_color_input_html ( 'OTHERMONTHBG', 
    translate( 'Table cell background for other month' ) ) ?>
</td></tr>
<tr><td>
  <?php echo print_color_input_html ( 'WEEKNUMBER', 
    translate( 'Week number color' ) ) ?>
</td></tr>
<tr><td>
 <?php echo print_color_input_html ( 'POPUP_BG', 
   translate( 'Event popup background' ) ) ?>
</td></tr>
<tr><td>
  <?php echo print_color_input_html ( 'POPUP_FG', 
    translate( 'Event popup text' ) ) ?>
</td></tr>
</table>
</fieldset>
<fieldset>
 <legend><?php etranslate('Background Image options')?></legend>
<table>
 <tr><td class="tooltip" title="<?php etooltip( 'bgimage-help' )?>">
  <label for="admin_BGIMAGE"><?php etranslate( 'Background Image' )?>:</label></td><td>
  <input type="text" size="75" name="admin_BGIMAGE" id="admin_BGIMAGE" value="<?php 
   echo ( ! empty ( $s['BGIMAGE'] )? htmlspecialchars ( $s['BGIMAGE'] ): '');
  ?>" />
 </td></tr>
 <tr><td class="tooltip" title="<?php etooltip( 'bgrepeat-help' )?>">
  <label for="admin_BGREPEAT"><?php etranslate( 'Background Repeat' )?>:</label></td><td>
  <input type="text" size="30" name="admin_BGREPEAT" id="admin_BGREPEAT" value="<?php 
   echo ( ! empty ( $s['BGREPEAT'] )? $s['BGREPEAT']: '');
  ?>" />
 </td></tr>
</table>
</fieldset>
</div>
</div>

<br /><br />
<div>
 <input type="submit" value="<?php etranslate( 'Save' )?>" name="" />
</div>
</form>

<?php } else {// if $error 
  echo print_error ( $error, true );  
} 
echo print_trailer (); ?>

