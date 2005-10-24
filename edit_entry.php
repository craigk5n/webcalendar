<?php
/*
 * $Id$
 *
 * Description:
 * Presents page to edit/add an event
 *
 * Notes:
 * If htmlarea is installed, users can use WYSIWYG editing.
 * SysAdmin must enable HTML for event full descriptions.
 * This can be done by installing HTMLArea (which has been
 * discontinued) or FCKEditor.  See the WebCalendar home page
 * for download and install instructions for these packages.
 *
 * This file will not pass XHTML validation with HTMLArea enabled
 * (Not sure about FCKEditor...)
 */
include_once 'includes/init.php';

load_user_categories ();
   
// Default for using tabs is enabled
if ( empty ( $EVENT_EDIT_TABS ) )
  $EVENT_EDIT_TABS = 'Y'; // default
$useTabs = ( $EVENT_EDIT_TABS == 'Y' );

// make sure this is not a read-only calendar
$can_edit = false;

// Public access can only add events, not edit.
if ( $login == "__public__" && $id > 0 ) {
  $id = 0;
}

$month = getIntValue ( 'month' );
$day = getIntValue ( 'day' );
$year = getIntValue ( 'year' );
$date = getIntValue ( 'date' );
if ( empty ( $date ) && empty ( $month ) ) {
  if ( empty ( $year ) ) $year = date ( "Y" );
  if ( empty ( $month ) ) $month = date ( "M" );
  if ( empty ( $day ) ) $day = date ( "d" );
  $date = sprintf ( "%04d%02d%02d", $year, $month, $day );
}

// Do we use HTMLArea of FCKEditor?
// Note: HTMLArea has been discontinued, so FCKEditor is preferred.
$use_htmlarea = false;
$use_fckeditor = false;
if ( $ALLOW_HTML_DESCRIPTION == "Y" ){
  if ( file_exists ( "includes/FCKeditor-2.0/fckeditor.js" ) &&
    file_exists ( "includes/FCKeditor-2.0/fckconfig.js" ) ) {
    $use_fckeditor = true;
  } else if ( file_exists ( "includes/htmlarea/htmlarea.php" ) ) {
    $use_htmlarea = true;
  }
}

$external_users = $byweekno = $byyearday = $rpt_count = $catNames = $catList="";
$participants = $exceptions = $inclusions = array();
$byday = $bymonth = $bymonthday = $bysetpos = array();

$wkst = "MO";

if ( $readonly == 'Y' || $is_nonuser ) {
  $can_edit = false;
} else if ( ! empty ( $id ) && $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin ) {
    $can_edit = true;
  }
  $sql = "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
    "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description, cal_group_id, cal_location " .
    "FROM webcal_entry WHERE cal_id = " . $id;
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    // If current user is creator of event, then they can edit
    if ( $row[0] == $login )
      $can_edit = true;
    if ( ! empty ( $override ) && ! empty ( $date ) ) {
      // Leave $cal_date to what was set in URL with date=YYYYMMDD
      $cal_date = $date;
    } else {
      $cal_date = $row[1];
    }
    $create_by = $row[0];
    if (( $user == $create_by ) && ( $is_assistant || $is_nonuser_admin )) $can_edit = true;
    
    $year = (int) ( $cal_date / 10000 );
    $month = ( $cal_date / 100 ) % 100;
    $day = $cal_date % 100;
    $time = $row[2];
    
    $tz_offset = get_tz_offset ( $TIMEZONE, mktime ( 0, 0, 0, $month, $day, $year ) );
    // test for AllDay event, if so, don't adjust time
    if ( $time > 0  || ( $time == 0 &&  $row[5] != 1440 ) ) { /* -1 = no time specified */
      $time += ( ! empty ( $tz_offset[0] )? $tz_offset[0] : 0 )  * 10000;
      if ( $time > 240000 ) {
        $time -= 240000;
        $gmt = mktime ( 0, 0, 0, $month, $day, $year );
        $gmt += ONE_DAY;
        $month = date ( "m", $gmt );
        $day = date ( "d", $gmt );
        $year = date ( "Y", $gmt );
      } else if ( $time < 0 ) {
        $time += 240000;
        $gmt = mktime ( 0, 0, 0, $month, $day, $year );
        $gmt -= ONE_DAY;
        $month = date ( "m", $gmt );
        $day = date ( "d", $gmt );
        $year = date ( "Y", $gmt );
      }
      // Set alterted date
      $cal_date = sprintf("%04d%02d%02d",$year,$month,$day);
    }
    if ( $time >= 0 ) {
      $hour = floor($time / 10000);
      $minute = ( $time / 100 ) % 100;
      $duration = $row[5];
    } else {
      $duration = "";
      $hour = -1;
    }
    $priority = $row[6];
    $type = $row[7];
    $access = $row[8];
    $name = $row[9];
    $description = $row[10];
    $parent = $row[11];
    $location = $row[12];
    // check for repeating event info...
    // but not if we are overriding a single entry of an already repeating
    // event... confusing, eh?
    if ( ! empty ( $override ) ) {
      $rpt_type = "none";
      $rpt_end = 0;
      $rpt_end_date = $cal_date;
      $rpt_freq = 1;
    } else {
      $res = dbi_query ( "SELECT cal_id, cal_type, cal_end, cal_endtime, " .
        "cal_frequency, cal_byday, cal_bymonth, cal_bymonthday, cal_bysetpos, " .  
        "cal_byweekno, cal_byyearday, cal_wkst, cal_count  " .
    "FROM webcal_entry_repeats WHERE cal_id = $id" );
      if ( $res ) {
        if ( $row = dbi_fetch_row ( $res ) ) {
          $rpt_type = $row[1];
          if ( $row[2] > 0 )
            $rpt_end = date_to_epoch ( $row[2] );
          else
            $rpt_end = 0;
          $rpt_end_date = $row[2];
          $rpt_end_time = $row[3];
          $rpt_freq = $row[4];
          $byday = explode(",",$row[5]);
          $bymonth = explode(",",$row[6]);
          $bymonthday = explode(",", $row[7]);
          $bysetpos = explode(",", $row[8]);
          $byweekno = $row[9];
          $byyearday = $row[10];
          $wkst = $row[11];
          $rpt_count = $row[12];
               
          //Check to see if Weekends Only is applicable
          $weekdays_only = ( $rpt_type == 'daily' && $byday == 'MO,TU,WE,TH,FR' ? true : false );
        }
      }
    }
   dbi_free_result ( $res );
    //determine if Expert mode needs to be set
    $expert_mode = ( isset ( $rpt_count ) || isset ($byyearday ) || isset($byweekno) ||
      isset ($bysetpos) || isset($bymonthday) || isset ($bymonth) || isset($byday));
  
    //Get Repeat Exceptions
  $sql = "SELECT cal_date, cal_exdate FROM webcal_entry_repeats_not WHERE cal_id = $id";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[1] == 1 ) {
          $exceptions[] = $row[0];
        } else {
          $inclusions[] = $row[0];   
        }
      }
   dbi_free_result ( $res );
    }
  }
  //get global categories
  $sql = "SELECT  webcal_entry_categories.cat_id, cat_name " .
    " FROM webcal_entry_categories, webcal_categories " .
      " WHERE webcal_entry_categories.cat_id = webcal_categories.cat_id AND " .
   " webcal_entry_categories.cal_id = $id  AND " . 
      " webcal_categories.cat_owner IS NULL ";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
     $cat_id[] = "-" .$row[0];
     $cat_name[] = $row[1] . "*";    
    }
  dbi_free_result ( $res );
 }
  //get user's categories 
    $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
      $is_admin ) ) ? $user : $login;
    $sql = "SELECT  DISTINCT cal_login, webcal_entry_categories.cat_id, " .
    " webcal_entry_categories.cat_owner, cat_name " .
    " FROM webcal_entry_user, webcal_entry_categories, webcal_categories " .
      " WHERE ( webcal_entry_user.cal_id = webcal_entry_categories.cal_id AND " .
      " webcal_entry_categories.cat_id = webcal_categories.cat_id AND " .
   " webcal_entry_user.cal_id = $id ) AND " . 
      " webcal_categories.cat_owner = '" . $cat_owner . "'".
   " ORDER BY webcal_entry_categories.cat_order";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$row[0]] = 1;
      if ( $login == $user || $is_assistant  || $is_admin ) {
     $cat_id[] = $row[1];
     $cat_name[] = $row[3];    
   }
    }
  dbi_free_result ( $res );
  if ( ! empty ( $cat_name ) ) $catNames = implode("," , array_unique($cat_name));
    if ( ! empty ( $cat_id ) ) $catList = implode(",", array_unique($cat_id));
  }

 
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == "Y" ) {
    $external_users = event_get_external_users ( $id );
  }
} else {
  // New event.
  $id = 0; // to avoid warnings below about use of undefined var
  // Anything other then testing for strlen breaks either hour=0 or no hour in URL
  if ( strlen ( $hour ) ) {
    $time = $hour * 100;
  } else {
    $time = -1;
    $hour = -1;
  }
  if ( ! empty ( $defusers ) ) {
    $tmp_ar = explode ( ",", $defusers );
    for ( $i = 0; $i < count ( $tmp_ar ); $i++ ) {
      $participants[$tmp_ar[$i]] = 1;
    }
  }
  if ( $readonly == "N" ) {
    // If public, then make sure we can add events
    if ( $login == '__public__' ) {
      if ( $PUBLIC_ACCESS_CAN_ADD )
        $can_edit = true;
    } else {
      // not public user
        $can_edit = true;
    }
  }
}
$thisyear = $year;
$thismonth = $month;
$thisday = $day;
if ( empty ( $rpt_type ) || ! $rpt_type )
  $rpt_type = "none";

// avoid error for using undefined vars
if ( ! isset ( $hour ) )
  $hour = -1;
if ( empty ( $duration ) )
  $duration = 0;
if ( $duration == ( 24 * 60 ) ) {
  $hour = $minute = $duration = "";
  $allday = "Y";
} else
  $allday = "N";
if ( empty ( $name ) )
  $name = "";
if ( empty ( $description ) || $description == "<br />" )
  $description = "";
if ( empty ( $location ) )
  $location = "";
if ( empty ( $priority ) )
  $priority = 0;
if ( empty ( $access ) )
  $access = "";
if ( empty ( $rpt_freq ) )
  $rpt_freq = 0;
if ( empty ( $rpt_end_date ) )
  $rpt_end_date = 0;

if ( empty ( $cal_date ) ) {
  if ( ! empty ( $date ) )
    $cal_date = $date;
  else
    $cal_date = date ( "Ymd" );
}

if ( empty ( $thisyear ) )
  $thisdate = date ( "Ymd" );
else {
  $thisdate = sprintf ( "%04d%02d%02d",
    empty ( $thisyear ) ? date ( "Y" ) : $thisyear,
    empty ( $thismonth ) ? date ( "m" ) : $thismonth,
    empty ( $thisday ) ? date ( "d" ) : $thisday );
}
if ( empty ( $cal_date ) || ! $cal_date ) {
  $cal_date = $thisdate;
}

//Setup to display user's timezone difference if Admin or Assistane
//Even thought event is stored in GMT, an Assistant may need to know that
//the boss is in a different Timezone
if ( $is_assistant || $is_admin && ! empty ( $user ) ) { 
  $tz_offset = get_tz_offset ( $TIMEZONE, '', $cal_date );
  $user_TIMEZONE = get_pref_setting ( $user, "TIMEZONE" );
  $user_TZ = get_tz_offset ( $user_TIMEZONE, '', $cal_date );
  if ( $tz_offset[0] != $user_TZ[0] ) {  //Different TZ_Offset
    user_load_variables ( $user, "temp" );
    $tz_diff = $user_TZ[0] - $tz_offset[0];
    $tz_value = ( $tz_diff > 0? translate ("hours ahead of you") :
      translate ("hours behind you") );
    $TZ_notice = "(" . $tempfullname . " " . 
      translate ("is in a different timezone than you are. Currently") . " ";
      //TODO show hh:mm instead of abs 
    $TZ_notice .= abs ( $tz_diff ) . " " . $tz_value . ".<br />&nbsp;"; 
    $TZ_notice .= translate ("Time entered here is based on your Timezone") . ".)"; 
  }
}
if ( $ALLOW_HTML_DESCRIPTION == "Y" ){
  // Allow HTML in description
  // If they have installed the htmlarea widget, make use of it
  $textareasize = 'rows="15" cols="50"';
  if ( $use_fckeditor ) {
    $textareasize = 'rows="20" cols="50"';
    $BodyX = 'onload="timetype_handler();rpttype_handler();toggle_until()"';
    $INC = array ( 'js/edit_entry.php', 'js/visible.php' );
  } else if ( $use_htmlarea ) {
    $BodyX = 'onload="initEditor();timetype_handler();rpttype_handler();toggle_until()"';
    $INC = array ( 'htmlarea/htmlarea.php', 'js/edit_entry.php',
      'js/visible.php', 'htmlarea/core.php' );
  } else {
    // No htmlarea files found...
    $BodyX = 'onload="timetype_handler();rpttype_handler();toggle_until()"';
    $INC = array ( 'js/edit_entry.php', 'js/visible.php' );
  }
} else {
  $textareasize = 'rows="5" cols="40"';
  $BodyX = 'onload="timetype_handler();rpttype_handler();toggle_until()"';
  $INC = array('js/edit_entry.php','js/visible.php');
}

print_header ( $INC, '', $BodyX );
?>


<h2><?php if ( $id ) echo translate("Edit Entry"); else echo translate("Add Entry"); ?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_edit_entry.php<?php if ( empty ( $id ) ) echo "?add=1"; ?>', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<?php
 if ( $can_edit ) {
?>
<form action="edit_entry_handler.php" method="post" name="editentryform">

<?php
if ( ! empty ( $id ) && ( empty ( $copy ) || $copy != '1' ) ) echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
// we need an additional hidden input field
echo "<input type=\"hidden\" name=\"entry_changed\" value=\"\" />\n";

// are we overriding an entry from a repeating event...
if ( ! empty ( $override ) ) {
  echo "<input type=\"hidden\" name=\"override\" value=\"1\" />\n";
  echo "<input type=\"hidden\" name=\"override_date\" value=\"$cal_date\" />\n";
}
// if assistant, need to remember boss = user
if ( $is_assistant || $is_nonuser_admin || ! empty ( $user ) )
   echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";

// if has cal_group_id was set, need to set parent = $parent
if ( ! empty ( $parent ) )
   echo "<input type=\"hidden\" name=\"parent\" value=\"$parent\" />\n";

?>

<!-- TABS -->
<?php if ( $useTabs ) { ?>
<div id="tabs">
 <span class="tabfor" id="tab_details"><a href="#tabdetails" onclick="return showTab('details')"><?php etranslate("Details") ?></a></span>
 <?php if ( $DISABLE_PARTICIPANTS_FIELD != "Y" ) { ?>
   <span class="tabbak" id="tab_participants"><a href="#tabparticipants" onclick="return showTab('participants')"><?php etranslate("Participants") ?></a></span>
 <?php } ?> 
 <?php if ( $DISABLE_REPEATING_FIELD != "Y" ) { ?>
   <span class="tabbak" id="tab_pete"><a href="#tabpete" onclick="return showTab('pete')"><?php etranslate("Repeat") ?></a></span>
 <?php } ?>
</div>
<?php } ?>

<!-- TABS BODY -->
<?php if ( $useTabs ) { ?>
<div id="tabscontent">
 <!-- DETAILS -->
 <a name="tabdetails"></a>
 <div id="tabscontent_details">
<?php } ?>
  <table border="0">
   <tr><td style="width:14%;" class="tooltip" title="<?php etooltip("brief-description-help")?>">
    <label for="entry_brief"><?php etranslate("Brief Description")?>:</label></td><td colspan="2">
    <input type="text" name="name" id="entry_brief" size="25" value="<?php 
     echo htmlspecialchars ( $name );
    ?>" /></td></tr>
   <tr><td style="vertical-align:top;" class="tooltip" title="<?php etooltip("full-description-help")?>">
    <label for="entry_full"><?php etranslate("Full Description")?>:</label></td><td>
    <textarea name="description" id="entry_full" <?php
     echo $textareasize;
    ?>><?php
     echo htmlspecialchars ( $description );
    ?></textarea></td><td style="vertical-align:top;">

  
<?php if (( ! empty ( $categories ) ) || ( $DISABLE_ACCESS_FIELD != "Y" ) || 
         ( $DISABLE_PRIORITY_FIELD != "Y" ) ){ // new table for extra fields ?>
    <table border="0" width="90%">
<?php } ?>
<?php if ( $DISABLE_ACCESS_FIELD != "Y" ) { ?>
      <tr><td class="tooltip" title="<?php etooltip("access-help")?>">
       <label for="entry_access"><?php etranslate("Access")?>:</label></td><td width="80%">
       <select name="access" id="entry_access">
        <option value="P"<?php if ( $access == "P" || ! strlen ( $access ) ) echo " selected=\"selected\"";?>><?php etranslate("Public")?></option>
        <option value="R"<?php if ( $access == "R" ) echo " selected=\"selected\"";?>><?php etranslate("Private")?></option>
        <option value="C"<?php if ( $access == "C" ) echo " selected=\"selected\"";?>><?php etranslate("Confidential")?></option>				
       </select>
       </td></tr>
<?php } ?>
<?php if ( $DISABLE_PRIORITY_FIELD != "Y" ) { ?>
     <tr><td class="tooltip" title="<?php etooltip("priority-help")?>">
      <label for="entry_prio"><?php etranslate("Priority")?>:&nbsp;</label></td><td>
      <select name="priority" id="entry_prio">
       <option value="1"<?php if ( $priority == 1 ) echo " selected=\"selected\"";?>><?php etranslate("Low")?></option>
       <option value="2"<?php if ( $priority == 2 || $priority == 0 ) echo " selected=\"selected\"";?>><?php etranslate("Medium")?></option>
       <option value="3"<?php if ( $priority == 3 ) echo " selected=\"selected\"";?>><?php etranslate("High")?></option>
      </select>
     </td></tr>
<?php } ?>
<?php if ( ! empty ( $categories ) ) { ?>
     <tr><td class="tooltip" title="<?php etooltip("category-help")?>" valign="top">
      <label for="entry_categories"><?php etranslate("Category")?>:<br /></label>
   <input type="button" value="Edit" onClick="editCats(event)" /></td><td valign="top">
      <input  readonly=""type="text" name="catnames" 
     value="<?php echo $catNames ?>"  size="50" 
    onClick="alert('<?php etranslate("Use the Edit button to make changes.") ?>')"/>
   <input  type="hidden" name="cat_id" id="entry_categories" value="<?php echo $catList ?>" />
     </td></tr>
<?php } //end if (! empty ($categories)) ?>
<?php if (( ! empty ( $categories ) ) || ( $DISABLE_ACCESS_FIELD != "Y" ) || 
         ( $DISABLE_PRIORITY_FIELD != "Y" ) ){ // end the table ?>
   </table>
    
<?php } ?>
  </td></tr>
 <tr><td class="tooltip" title="<?php etooltip("location-help")?>">
   <?php etranslate("Location")?>:</td><td colspan="2">
    <input type="text" name="location" size="55" 
   value="<?php echo htmlspecialchars ( $location ); ?>" />
  </td></tr>
  <tr><td class="tooltip" title="<?php etooltip("date-help")?>">
   <?php etranslate("Date")?>:</td><td colspan="2">
   <?php
    print_date_selection ( "", $cal_date );
   ?>
  </td></tr>
  <tr><td>&nbsp;</td><td colspan="2">
   <select name="timetype" onchange="timetype_handler()">
    <option value="U" <?php if ( $allday != "Y" && $hour == -1 ) echo " selected=\"selected\""?>><?php etranslate("Untimed event"); ?></option>
    <option value="T" <?php if ( $allday != "Y" && $hour >= 0 ) echo " selected=\"selected\""?>><?php etranslate("Timed event"); ?></option>
    <option value="A" <?php if ( $allday == "Y" ) echo " selected=\"selected\""?>><?php etranslate("All day event"); ?></option>
   </select>
  </td></tr>
 <?php if ( ! empty ( $TZ_notice ) ) { ?>
   <tr id="timezonenotice"><td class="tooltip" title="<?php etooltip("Time entered here is based on your Timezone")?>">
   <?php etranslate ("Timezone Offset")?>:</td><td colspan="2">
   <?php echo $TZ_notice ?></td></tr>
 <?php } ?>
  <tr id="timeentrystart"><td class="tooltip" title="<?php etooltip("time-help")?>">
   <?php echo translate("Time") . ":"; ?></td><td colspan="2">
<?php
$h12 = $hour;
$amsel = " checked=\"checked\""; $pmsel = "";
if ( $TIME_FORMAT == "12" ) {
  if ( $h12 < 12 ) {
    $amsel = " checked=\"checked\""; $pmsel = "";
  } else {
    $amsel = ""; $pmsel = " checked=\"checked\"";
  }
  $h12 %= 12;
  if ( $h12 == 0 ) $h12 = 12;
}
if ( $time < 0 )
  $h12 = "";
?>
   <input type="text" name="hour" size="2" value="<?php 
    if ( $time >= 0 && $allday != 'Y' ) echo $h12;
   ?>" maxlength="2" />:<input type="text" name="minute" size="2" value="<?php 
    if ( $time >= 0 && $allday != "Y" ) printf ( "%02d", $minute );
   ?>" maxlength="2" />
<?php
if ( $TIME_FORMAT == "12" ) {
  echo "<label><input type=\"radio\" name=\"ampm\" value=\"am\" $amsel />&nbsp;" .
    translate("am") . "</label>\n";
  echo "<label><input type=\"radio\" name=\"ampm\" value=\"pm\" $pmsel />&nbsp;" .
    translate("pm") . "</label>\n";
}
?>

<?php
  $dur_h = (int)( $duration / 60 );
  $dur_m = $duration - ( $dur_h * 60 );

if ($TIMED_EVT_LEN != 'E') { ?>
   </td></tr>
  <tr id="timeentryduration"><td>
  <span class="tooltip" title="<?php 
   etooltip("duration-help")
  ?>"><?php 
   etranslate("Duration")
  ?>:&nbsp;</span></td><td colspan="2">
  <input type="text" name="duration_h" id="duration_h" size="2" maxlength="2" value="<?php 
   if ( $allday != "Y" ) printf ( "%d", $dur_h );
  ?>" />:<input type="text" name="duration_m" id="duration_m" size="2" maxlength="2" value="<?php 
   if ( $allday != "Y" ) 
    printf ( "%02d", $dur_m );
  ?>" />&nbsp;(<label for="duration_h"><?php 
   echo translate("hours")
  ?></label>: <label for="duration_m"><?php 
   echo translate("minutes")
  ?></label>)
 </td></tr>
<?php } else {
if ( $id ) {
  $t_h12 = $h12;
  if ( $TIME_FORMAT == "12" ) {
    // Convert to a twenty-four hour time scale
    if ( !empty ( $amsel ) && $t_h12 == 12 )
      $t_h12 = 0;
    if ( !empty ( $pmsel ) && $t_h12 < 12 )
      $t_h12 += 12;
  } //end 12-hour time format

  // Add duration
  $endhour = $t_h12 + $dur_h;
  $endminute = $minute + $dur_m;
  $endhour = $endhour + ( $endminute / 60 );
  $endminute %= 60;

  if ( $TIME_FORMAT == "12" ) {
    // Convert back to a standard time format
    if ( $endhour < 12 ) {
      $endamsel = " checked=\"checked\""; $endpmsel = "";
    } else {
      $endamsel = ""; $endpmsel = " checked=\"checked\"";
    } //end if ( $endhour < 12 )
    $endhour %= 12;
    if ( $endhour == 0 ) $endhour = 12;
  } //end if ( $TIME_FORMAT == "12" )
} else {
  $endhour = $h12;
  $endminute = $minute;
  $endamsel = $amsel;
  $endpmsel = $pmsel;
} //end if ( $id )
if ( $allday != "Y" && $hour == -1 ) {
  $endhour = "";
  $endminute = "";
} //end if ( $allday != "Y" && $hour == -1 )
?>
 <span id="timeentryend" class="tooltip" title="<?php etooltip("end-time-help")?>">&nbsp;-&nbsp;
  <input type="text" name="endhour" size="2" value="<?php 
   if ( $allday != "Y" ) echo $endhour;
  ?>" maxlength="2" />:<input type="text" name="endminute" size="2" value="<?php 
   if ( $time >= 0 && $allday != "Y" ) printf ( "%02d", $endminute );
  ?>" maxlength="2" />
  <?php
   if ( $TIME_FORMAT == "12" ) {
    echo "<label><input type=\"radio\" name=\"endampm\" value=\"am\" $endamsel />&nbsp;" .
     translate("am") . "</label>\n";
    echo "<label><input type=\"radio\" name=\"endampm\" value=\"pm\" $endpmsel />&nbsp;" .
     translate("pm") . "</label>\n";
   }
  ?>
 </span>
</td></tr>
<?php } ?>
</table>
<table>
<?php
// site-specific extra fields (see site_extras.php)
// load any site-specific fields and display them
if ( $id > 0 )
  $extras = get_site_extra_fields ( $id );
  //if we have more than one reminder (per RFC2445) then append some data
  //to the $site_extras array for diplay
  if ( ! empty ( $extras ) ) {
   $rem_count = 0;
    foreach ( $extras as $K => $V) {
      if ( $V['cal_type'] == EXTRA_REMINDER ) {
        $rem_count++;
        if ( $rem_count > 0 ) {
          $rem_array = array ( $V['cal_name'], 'Send Reminder', EXTRA_REMINDER, $V['cal_data'], 6);
          $site_additions[] = $rem_array;
          $site_extras[count($site_extras)] = $rem_array;
        }
			} 
    }
 } 
  if ( ! empty ( $site_additions ) ) {
   sort ( $site_additions );
    $serial_site_extras = base64_encode( serialize ( $site_additions ) );
    echo "<input type=\"hidden\" name=\"serial_site_extras\" value=\'$serial_site_extras\' />";
 }
for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
  $extra_name = $site_extras[$i][0];
  $extra_descr = $site_extras[$i][1];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];
  //echo "<tr><td>Extra " . $extra_name . " - " . $site_extras[$i][2] . 
  //  " - " . $extras[$extra_name]['cal_name'] .
  //  "arg1: $extra_arg1, arg2: $extra_arg2 </td></tr>\n";
  if ( $extra_type == EXTRA_MULTILINETEXT )
    echo "<tr><td style=\"vertical-align:top; font-weight:bold;\"><br />\n";
  else
    echo "<tr><td style=\"font-weight:bold;\">";
  echo translate ( $extra_descr ) .  ":</td><td>\n";
  if ( $extra_type == EXTRA_URL ) {
    echo "<input type=\"text\" size=\"50\" name=\"" . $extra_name .
      "\" value=\"" . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . "\" />";
  } else if ( $extra_type == EXTRA_EMAIL ) {
    echo "<input type=\"text\" size=\"30\" name=\"" . $extra_name . "\" value=\"" . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . "\" />";
  } else if ( $extra_type == EXTRA_DATE ) {
    if ( ! empty ( $extras[$extra_name]['cal_date'] ) )
      print_date_selection ( $extra_name, $extras[$extra_name]['cal_date'] );
    else
      print_date_selection ( $extra_name, $cal_date );
  } else if ( $extra_type == EXTRA_TEXT ) {
    $size = ( $extra_arg1 > 0 ? $extra_arg1 : 50 );
    echo "<input type=\"text\" size=\"" . $size . "\" name=\"" . $extra_name .
      "\" value=\"" . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . "\" />";
  } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
    $cols = ( $extra_arg1 > 0 ? $extra_arg1 : 50 );
    $rows = ( $extra_arg2 > 0 ? $extra_arg2 : 5 );
    echo "<textarea rows=\"" . $rows . "\" cols=\"" . $cols . "\" name=\"" . $extra_name . "\">" . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . "</textarea>";
  } else if ( $extra_type == EXTRA_USER ) {
    // show list of calendar users...
    echo "<select name=\"" . $extra_name . "\">\n";
    echo "<option value=\"\">None</option>\n";
    $userlist = get_my_users ();
    for ( $j = 0; $j < count ( $userlist ); $j++ ) {
      if ( access_is_enabled () &&
        ! access_can_view_user_calendar ( $userlist[$j]['cal_login'] ) )
        continue; // cannot view calendar so cannot add to their cal
      echo "<option value=\"" . $userlist[$j]['cal_login'] . "\"";
        if ( ! empty ( $extras[$extra_name]['cal_data'] ) &&
          $userlist[$j]['cal_login'] == $extras[$extra_name]['cal_data'] )
          echo " selected=\"selected\"";
        echo ">" . $userlist[$j]['cal_fullname'] . "</option>\n";
    }
    echo "</select>\n";
  } else if ( $extra_type == EXTRA_REMINDER ) {
    $rem_status = 0; // don't send
    echo "<label><input type=\"radio\" name=\"" . $extra_name . "\" value=\"1\"";
    if ( empty ( $id ) ) {
      // adding event... check default
      if ( ( $extra_arg2 & EXTRA_REMINDER_DEFAULT_YES ) > 0 ) 
      $rem_status = 1;
    } else {
      // editing event... check status
      if ( ! empty ( $extras[$extra_name]['cal_remind'] ) )
        $rem_status = 1;
    }
    if ( $rem_status )
      echo " checked=\"checked\"";
    echo " />";
    etranslate ( "Yes" );
    echo "</label>&nbsp;<label><input type=\"radio\" name=\"" . $extra_name . "\" value=\"0\"";
    if ( ! $rem_status )
      echo " checked=\"checked\"";
    echo " />";
    etranslate ( "No" );
    echo "</label>&nbsp;&nbsp;";
    if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_DATE ) > 0 ) {
      if ( ! empty ( $extras[$extra_name]['cal_date'] ) &&
        $extras[$extra_name]['cal_date'] > 0 )
        print_date_selection ( $extra_name, $extras[$extra_name]['cal_date'] );
      else
        print_date_selection ( $extra_name, $cal_date );
    } else if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
      if ( ! empty ( $extras[$extra_name]['cal_data'] ) )
        $minutes = $extras[$extra_name]['cal_data'];
      else
        $minutes = $extra_arg1;
      // will be specified in total minutes
      $d = (int) ( $minutes / ( 24 * 60 ) );
      $minutes -= ( $d * 24 * 60 );
      $h = (int) ( $minutes / 60 );
      $minutes -= ( $h * 60 );
      echo "<label><input type=\"text\" size=\"2\" name=\"" . $extra_name .
        "_days\" value=\"$d\" /> " .  translate("days") . "</label>&nbsp;\n";
      echo "<label><input type=\"text\" size=\"2\" name=\"" . $extra_name .
        "_hours\" value=\"$h\" /> " .  translate("hours") . "</label>&nbsp;\n";
      echo "<label><input type=\"text\" size=\"2\" name=\"" . $extra_name .
        "_minutes\" value=\"$minutes\" /> " .  translate("minutes") . "&nbsp;" . translate("before event") . "</label>";
    }
  } else if ( $extra_type == EXTRA_SELECTLIST ) {
    // show custom select list.
    echo "<select name=\"" . $extra_name . "\">\n";
    if ( is_array ( $extra_arg1 ) ) {
      for ( $j = 0; $j < count ( $extra_arg1 ); $j++ ) {
        echo "<option";
        if ( ! empty ( $extras[$extra_name]['cal_data'] ) &&
          $extra_arg1[$j] == $extras[$extra_name]['cal_data'] )
          echo " selected=\"selected\"";
        echo ">" . $extra_arg1[$j] . "</option>\n";
      }
    }
    echo "</select>\n";
  }
  echo "</td></tr>\n";
}
// end site-specific extra fields
?>
</table>
<?php if ( $useTabs ) { ?>
</div>
<?php } /* $useTabs */ ?>

<!-- PARTICIPANTS -->
<?php if ( $useTabs ) { ?>
<a name="tabparticipants"></a>
<div id="tabscontent_participants">
<?php } /* $useTabs */ ?>
<table>
<?php
// Only ask for participants if we are multi-user.
$show_participants = ( $DISABLE_PARTICIPANTS_FIELD != "Y" );
if ( $is_admin )
  $show_participants = true;
if ( $login == "__public__" && $PUBLIC_ACCESS_OTHERS != "Y" )
  $show_participants = false;

if ( $single_user == "N" && $show_participants ) {
  $userlist = get_my_users ();
  if ($NONUSER_ENABLED == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($NONUSER_AT_TOP == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  $num_users = 0;
  $size = 0;
  $users = "";
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    $l = $userlist[$i]['cal_login'];
    $size++;
    $users .= "<option value=\"" . $l . "\"";
    if ( $id > 0 ) {
      if ( ! empty ($participants[$l]) )
        $users .= " selected=\"selected\"";
    } else {
      if ( ! empty ($defusers) ) {
        // default selection of participants was in the URL
        if ( ! empty ( $participants[$l] ) )
          $users .= " selected=\"selected\"";
      } else {
        if ( ($l == $login && ! $is_assistant  && ! $is_nonuser_admin) || (! empty ($user) && $l == $user) )
          $users .= " selected=\"selected\"";
      }
      if ( $l == '__public__' &&
        ! empty ($PUBLIC_ACCESS_DEFAULT_SELECTED) &&
         $PUBLIC_ACCESS_DEFAULT_SELECTED == 'Y' )
           $users .= " selected=\"selected\"";
    }
    $users .= ">" . $userlist[$i]['cal_fullname'] . "</option>\n";
  }

  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  print "<tr title=\"" . 
 tooltip("participants-help") . "\"><td class=\"tooltipselect\">\n<label for=\"entry_part\">" . 
 translate("Participants") . ":</label></td><td>\n";
  print "<select name=\"participants[]\" id=\"entry_part\" size=\"$size\" multiple=\"multiple\">$users\n";
  print "</select>\n";
  if ( $GROUPS_ENABLED == "Y" ) {
    echo "<input type=\"button\" onclick=\"selectUsers()\" value=\"" .
      translate("Select") . "...\" />\n";
  }
  echo "<input type=\"button\" onclick=\"showSchedule()\" value=\"" .
    translate("Availability") . "...\" />\n";
  print "</td></tr>\n";

  // external users
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == "Y" ) {
    print "<tr title=\"" .
      tooltip("external-participants-help") . "\"><td style=\"vertical-align:top;\" class=\"tooltip\">\n<label for=\"entry_extpart\">" .
      translate("External Participants") . ":</label></td><td>\n";
    print "<textarea name=\"externalparticipants\" id=\"entry_extpart\" rows=\"5\" cols=\"40\">";
    print $external_users . "</textarea>\n</td></tr>\n";
  }
}
?>
</table>
<?php if ( $useTabs ) { ?>
</div>
<?php } /* $useTabs */ ?>

<!-- REPEATING INFO -->
<?php if ( $DISABLE_REPEATING_FIELD != "Y" ) { ?>
<?php if ( $useTabs ) { ?>
<a name="tabpete"></a>
<div id="tabscontent_pete">
<?php } /* $useTabs */ ?>

<table border="0">
 <tr>
 <td class="tooltip" title="<?php etooltip("repeat-type-help")?>">
 <label for="rpttype"><?php etranslate("Type")?>:</label></td><td>
 <select name="rpt_type" id="rpttype" onchange="rpttype_handler();rpttype_weekly()">
<?php
 echo "  <option value=\"none\"" . 
  ( strcmp ( $rpt_type, 'none' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("None") . "</option>\n";
 echo "  <option value=\"daily\"" . 
  ( strcmp ( $rpt_type, 'daily' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("Daily") . "</option>\n";
 echo "  <option value=\"weekly\"" . 
  ( strcmp ( $rpt_type, 'weekly' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("Weekly") . "</option>\n";
 echo "  <option value=\"monthlyByDay\"" . 
  ( strcmp ( $rpt_type, 'monthlyByDay' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("Monthly") . " (" . translate("by day") . ")" . "</option>\n";
 echo "  <option value=\"monthlyByDate\"" . 
  ( strcmp ( $rpt_type, 'monthlyByDate' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("Monthly") . " (" . translate("by date") . ")" . "</option>\n";
 echo "  <option value=\"monthlyBySetPos\"" . 
  ( strcmp ( $rpt_type, 'monthlyBySetPos' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("Monthly") . " (" . translate("by position") . ")" . "</option>\n";
 echo "  <option value=\"yearly\"" . 
  ( strcmp ( $rpt_type, 'yearly' ) == 0 ? " selected=\"selected\"" : "" ) . ">" . 
  translate("Yearly") . "</option>\n";
?>
 </select>&nbsp;&nbsp;&nbsp;
<label id ="rpt_mode"><input type="checkbox" name="rptmode"  id="rptmode" 
  value="y" onclick="rpttype_handler()" <?php echo ( ! empty ($expert_mode)?"checked=\"checked\"":"") ?>/>
<?php etranslate("Expert Mode")?></label>
</td></tr>
<tr id="rptenddate" style="visibility:hidden;">
 <td class="tooltip" title="<?php etooltip("repeat-end-date-help")?>">
  <label for="rpt_day"><?php etranslate("Ending")?>:</label></td>
 <td><input  type="radio" name="rpt_end_use" id="rpt_until" value="f" <?php 
  echo (  empty ( $rpt_end ) && empty ( $rpt_count )? " checked=\"checked\"" : "" ); 
 ?>  onChange="toggle_until()" />&nbsp;<label><?php etranslate("Forever")?></label><br />
 <input  type="radio" name="rpt_end_use" id="rpt_until" value="u" <?php 
  echo ( ! empty ( $rpt_end ) ? " checked=\"checked\"" : "" ); 
 ?> onChange="toggle_until()" />&nbsp;<label><?php etranslate("Use end date")?></label>
 &nbsp;&nbsp;&nbsp;
 <span class="end_day_selection" name="rpt_end_day_select"><?php
  print_date_selection ( "rpt_", $rpt_end_date ? $rpt_end_date : $cal_date )
 ?></span><br />
 <input type="radio" name="rpt_end_use" id="rpt_until" value="c" <?php 
  echo ( ! empty ( $rpt_count ) ? " checked=\"checked\"" : "" ); 
 ?> onChange="toggle_until()" />&nbsp;<label><?php etranslate("Number of times")?></label>
 <input type="text" name="rpt_count" id="rpt_count" size="4" maxlength="4" value="<?php echo $rpt_count; ?>" />
 
</td></tr>

 <tr id="rptfreq" style="visibility:hidden;" title="<?php etooltip("repeat-frequency-help")?>"><td class="tooltip">
 <label for="entry_freq"><?php etranslate("Frequency")?>:</label></td><td>
 <input type="text" name="rpt_freq" id="entry_freq" size="4" maxlength="4" value="<?php echo $rpt_freq; ?>" />
 &nbsp;&nbsp;&nbsp;&nbsp;
 <label id="weekdays_only"><input  type="checkbox" name="weekdays_only" value="y" <?php echo ( ! empty ( $weekdays_only )? " checked=\"checked\"" : "" ) ?> />
 <?php etranslate("Weekdays Only")?></label>
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<span id="rptwkst">
 <select   name="wkst">
    <option value="MO" <?php ( strcmp ( $wkst, 'MO' ) == 0 ? " selected=\"selected\"" : "" ) ?>>MO</option>
    <option value="SU" <?php ( strcmp ( $wkst, 'SU' ) == 0 ? " selected=\"selected\"" : "" ) ?>>SU</option>
 </select>&nbsp;&nbsp;<label for="rptwkst" ><?php etranslate("Week Start")?></label></span>
 </td>
 </tr>

 <tr id="rptbydayextended" style="visibility:hidden;" title="<?php etooltip("repeat-bydayextended-help")?>"><td class="tooltip">
 <label><?php echo translate("ByDay") ?>:</label></td><td>
 <?php
   //display byday extended selection
  //We use BUTTONS  in a triple state configuration, but this data will not get
  //posted along with the form. So, we create hidden text fields to pass the data 
  //to the form handler. If there is  a better/easier way to do this....let us know.
   echo "<table cellpadding=\"2\" cellspacing=\"0\" border=\"1\" ><tr><td></td>";
  for ( $rpt_byday_label =0;$rpt_byday_label <=6; $rpt_byday_label++){
    echo "<th align=\"center\" width=\"50px\"><label >" . translate($weekday_names[$rpt_byday_label]) . "</label></th>\n";
  }
  echo "</tr><tr>\n<th align=\"center\">ALL</th>";
  for ( $rpt_byday_single =0;$rpt_byday_single <=6; $rpt_byday_single++){
    echo "<td align=\"center\"><input type=\"checkbox\" name=\"bydayext1[]\" id=\"$byday_names[$rpt_byday_single]\" value=\"$byday_names[$rpt_byday_single]\"" 
     . (in_array($byday_names[$rpt_byday_single],$byday)?" checked=\"checked\"":"") . " />\n</td>\n";
  }
  echo "</tr><tr id=\"rptbydayln\" style=\"visibility:hidden;\">\n";
  for ( $loop_ctr=1; $loop_ctr < 6; $loop_ctr++) {
    echo "<th align=\"center\"><label>" . $loop_ctr . "/" . ($loop_ctr - 6) . "</label></th>\n";
    for ( $rpt_byday =0;$rpt_byday <=6; $rpt_byday++){
       $buttonvalue = (in_array($loop_ctr . $byday_names[$rpt_byday],$byday) 
      ?$loop_ctr . $byday_names[$rpt_byday]
     : (in_array(($loop_ctr -6) . $byday_names[$rpt_byday],$byday)
     ?($loop_ctr -6) . $byday_names[$rpt_byday]:"        ")); 

    echo "<td align=\"center\"><input type=\"hidden\" name=\"bydayext2[]\"  " .
      " id=\"$loop_ctr$byday_names[$rpt_byday]\" value=\"$buttonvalue\" />\n" .
     " <input  type=\"button\" name=\"byday2[]\"" .
      " id=\"$loop_ctr$byday_names[$rpt_byday]\"" .
      " value=\"$buttonvalue\"" .
     " onclick=\"toggle_byday(this)\" /></td>\n";
    }
   echo  "</tr>\n";
    if ( $loop_ctr  < 5 ) echo  "<tr id=\"rptbydayln$loop_ctr\" style=\"visibility:hidden;\">";
 }
   echo "</table>";
?></td></tr>

<tr id="rptbymonth" style="visibility:hidden;" title="<?php etooltip("repeat-month-help")?>"><td class="tooltip">
 <?php etranslate("ByMonth")?>:&nbsp;</td><td>
 <?php
   //display bymonth selection
   echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"1\"><tr>";
  for ( $rpt_month =1;$rpt_month <=12; $rpt_month++){
     echo "<td><label><input type=\"checkbox\" name=\"bymonth[]\" value=\"$rpt_month\"" 
      . (in_array($rpt_month,$bymonth)?" checked=\"checked\"":"") . " />&nbsp;" . 
   translate(date("M",mktime(0,0,0,$rpt_month))) . 
     "</label>\n</td>";
    if ( $rpt_month == 6 ) echo  "</tr><tr>";
  }
   echo "</tr></table>";
?></td></tr>
 
 
  <tr  id="rptbysetpos" style="visibility:hidden;" title="<?php etooltip("repeat-bysetpos-help")?>">
 <td class="tooltip" id="BySetPoslabel">
<?php etranslate("BySetPos")?>:&nbsp;</td><td>
 <?php
   //display bysetpos selection
   echo "<table cellpadding=\"2\" cellspacing=\"0\" border=\"1\" ><tr><td></td>";
  for ( $rpt_bysetpos_label =1;$rpt_bysetpos_label <=11; $rpt_bysetpos_label++){
    echo "<th align=\"center\" width=\"37px\"><label >$rpt_bysetpos_label</label></th>\n";
  }
  echo "</tr><tr>\n";
  for ( $loop_ctr=1; $loop_ctr <32; $loop_ctr++) {
       $buttonvalue = (in_array($loop_ctr,$bysetpos) 
      ?($loop_ctr):(in_array(($loop_ctr -32),$bysetpos)
     ?($loop_ctr -32):"      ")); 
      if ( $loop_ctr == 1 || $loop_ctr == 12  ) 
        echo "<th align=\"center\"><label>" . $loop_ctr . "-" . ($loop_ctr + 10) . "</label></th>\n";
      if ( $loop_ctr == 23 ) 
        echo "<th align=\"center\"><label>" . $loop_ctr . "-31"  . "</label></th>\n";
    echo "<td align=\"center\"><input type=\"hidden\" name=\"bysetpos2[]\"  " .
      " id=\"bysetpos$loop_ctr\" value=\"$buttonvalue\" />\n" .
     " <input  type=\"button\" name=\"bysetpos[]\"" .
      " id=\"bysetpos$loop_ctr\"" .
      " value=\"$buttonvalue\"" .
     " onclick=\"toggle_bysetpos(this)\" /></td>\n";
       if ( $loop_ctr == 11 || $loop_ctr == 22 ) echo  "</tr><tr>\n";
    
 }
   echo "</tr></table>";
 ?></td></tr>

 <tr  id="rptbymonthdayextended" style="visibility:hidden;" title="<?php etooltip("repeat-bymonthdayextended-help")?>">
 <td class="tooltip" id="ByMonthDaylabel">
<?php etranslate("ByMonthDay")?>:&nbsp;</td><td>
 <?php
   //display bymonthday extended selection
   echo "<table cellpadding=\"2\" cellspacing=\"0\" border=\"1\" ><tr><td></td>";
  for ( $rpt_bymonthday_label =1;$rpt_bymonthday_label <=11; $rpt_bymonthday_label++){
    echo "<th align=\"center\" width=\"37px\"><label >$rpt_bymonthday_label</label></th>\n";
  }
  echo "</tr><tr>\n";
  for ( $loop_ctr=1; $loop_ctr <32; $loop_ctr++) {
       $buttonvalue = (in_array($loop_ctr,$bymonthday) 
      ?($loop_ctr):(in_array(($loop_ctr -32),$bymonthday)
     ?($loop_ctr -32):"      ")); 
      if ( $loop_ctr == 1 || $loop_ctr == 12  ) 
        echo "<th align=\"center\"><label>" . $loop_ctr . "-" . ($loop_ctr + 10) . "</label></th>\n";
      if ( $loop_ctr == 23 ) 
        echo "<th align=\"center\"><label>" . $loop_ctr . "-31"  . "</label></th>\n";
    echo "<td align=\"center\"><input type=\"hidden\" name=\"bymonthday[]\"  " .
      " id=\"bymonthday$loop_ctr\" value=\"$buttonvalue\" />\n" .
     " <input  type=\"button\" name=\"bymonthday2[]\"" .
      " id=\"bymonthday$loop_ctr\"" .
      " value=\"$buttonvalue\"" .
     " onclick=\"toggle_bymonthday(this)\" /></td>\n";
       if ( $loop_ctr == 11 || $loop_ctr == 22 ) echo  "</tr><tr>\n";
    
 }
   echo "</tr></table>";

 //Populate Repeat Exceptions data for later use
 $excepts = '';
 for ( $i = 0; $i < count ( $exceptions ); $i++ ) {
   $excepts .= "<option -" . $exceptions[$i] . ">-" . $exceptions[$i] . "</option>\n";
 }
  //Populate Repeat Inclusions data for later use
 for ( $i = 0; $i < count ( $inclusions ); $i++ ) {
   $excepts .= "<option +" . $inclusions[$i] . ">+" . $inclusions[$i] . "</option>\n";
 }
?>
 </td> 
 </tr>


 <tr id="rptbyweekno" style="visibility:hidden;" title="<?php etooltip("repeat-byweekno-help")?>"><td class="tooltip">
 <?php etranslate("ByWeekNo")?>:</td><td>
 <input type="text" name="byweekno" id="byweekno" size="50" maxlength="100" value="<?php echo $byweekno; ?>" />
</td></tr>

 <tr id="rptbyyearday" style="visibility:hidden;" title="<?php etooltip("repeat-byyearday-help")?>"><td class="tooltip">
 <?php etranslate("ByYearDay")?>:</td><td>
 <input type="text" name="byyearday" id="byyearday" size="50" maxlength="100" value="<?php echo $byyearday; ?>" />
</td></tr> 

 <tr id="rptexceptions" style="visibility:hidden;"  title="<?php etooltip("repeat-exceptions-help")?>">
 <td class="tooltip">
 <?php echo translate("Exclusions") . "/<br />" . translate("Inclusions")?>:</td><td>
 <table bgcolor="#CCCCCC"  border="0" width="250px">
 <tr ><td colspan="2">
 <?php print_date_selection ( "except_", $rpt_end_date ? $rpt_end_date : $cal_date )?>
 </td></tr><tr><td align="right" valign="top" width="100">
 <label id="select_exceptions_not" style="visibility:<?php echo ( empty ( $excepts )? "visible" : "hidden" ) ?>;"></label>
 <select id="select_exceptions"  name="exceptions[]"  multiple="multiple" style="visibility:<?php echo ( ! empty ( $excepts )? "visible" : "hidden" ) ?>;" size="4" >
 <?php echo $excepts ?></select></td><td valign="top">
  <input  align="left" type="button" name="addException"  value="<?php etranslate("Add Exception") ?>" onclick="add_exception(0)" /><br />
   <input  align="left" type="button" name="addInclusion"  value="<?php etranslate("Add Inclusion") ?>" onclick="add_exception(1)" /><br />
 <input  align="left" type="button" name="delSelected"  value="<?php etranslate("Delete Selected") ?>" onclick="del_selected()" />
</td></tr></table>

</td></tr></table>
<?php if ( $useTabs ) { ?>
</div> <!-- End tabscontent_pete -->
<?php } /* $useTabs */ ?>
<?php } ?>
</div> <!-- End tabscontent -->
<table  style="border-width:0px;">
<tr><td>
 <script type="text/javascript">
<!-- <![CDATA[
  document.writeln ( '<input type="button" value="<?php etranslate("Save")?>" onclick="validate_and_submit()" />' );
//]]> -->
 </script>
 <noscript>
  <input type="submit" value="<?php etranslate("Save")?>" />
 </noscript>
</td></tr>
</table>
<input type="hidden" name="participant_list" value="" />

<?php if ( $use_fckeditor ) { ?>
<script type="text/javascript" src="includes/FCKeditor-2.0/fckeditor.js"></script>
<script type="text/javascript">
   var myFCKeditor = new FCKeditor( 'description' ) ;
   myFCKeditor.BasePath = 'includes/FCKeditor-2.0/' ;
   myFCKeditor.ToolbarSet = 'Medium' ;
   myFCKeditor.Config['SkinPath'] = './skins/office2003/' ;
   myFCKeditor.ReplaceTextarea() ;
</script>
<?php /* $use_fckeditor */ } ?>

</form>

<?php if ( $id > 0 && ( $login == $create_by || $single_user == "Y" || $is_admin ) ) { ?>
 <a href="del_entry.php?id=<?php echo $id;?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?")?>');"><?php etranslate("Delete entry")?></a><br />
<?php 
 } //end if clause for delete link
} else { 
  echo translate("You are not authorized to edit this entry") . ".";
} //end if ( $can_edit )
?>

<?php print_trailer(); ?>
</body>
</html>
