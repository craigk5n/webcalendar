<?php
/*
 * $Id$
 *
 * Description:
 * Presents page to edit/add an event/task/journal
 *
 * Notes:
 * If htmlarea is installed, users can use WYSIWYG editing.
 * SysAdmin must enable HTML for event full descriptions.
 * This can be done by installing HTMLArea (which has been
 * discontinued) or FCKEditor.  See the WebCalendar home page
 * for download and install instructions for these packages.
 *
 *
 */
include_once 'includes/init.php';

/**
 * Generate HTML for a time selection for use in a form.
 *
 * @param string $prefix Prefix to use in front of form element names
 * @param string $time   Currently selected time in HHMMSS
 * @param bool $trigger   Add onchange event trigger that
 *  calls javascript function $prefix_timechanged()
 *
 * @return string HTML for the selection box
 */
function time_selection ( $prefix, $time='', $trigger=false ) {
  global $TIME_FORMAT, $ENTRY_SLOTS, $WORK_DAY_START_HOUR, $checked, $selected;
  $ret = '';
  $hournameid = 'name="' . $prefix . 'hour" id="' . $prefix . 'hour" ';
  $minnameid = 'name="' . $prefix . 'minute" id="' . $prefix . 'minute" ';
  $trigger_str = ( $trigger ? 'onchange="' . $prefix . 'timechanged() ' : '');
  if ( ! isset ( $time ) && $time != 0 ) {
    $hour = $WORK_DAY_START_HOUR;
    $minute = 0;
  } else {
    $hour = floor($time / 10000);
    $minute = ( ( $time / 100 ) % 100 ) % 60;  
  }
  if ( $TIME_FORMAT == '12' ) {
    $maxhour = 12;
    if ( $hour < 12 ) {
      $amsel = $checked; $pmsel = '';
    } else {
      $amsel = ''; $pmsel = $checked;
    }
    $hour %= 12;
    if ( $hour == 0 ) $hour = 12;
  } else {
    $maxhour = 24;
    $hour = sprintf ( "%02d", $hour );  
  }
  $minute = sprintf ( "%02d", $minute ); 
  $ret .= '<select ' . $hournameid . $trigger_str . " >\n";
  for ( $i = 0; $i < $maxhour; $i++ ) {
    $ihour = ( $TIME_FORMAT == '24' ? sprintf ( "%02d", $i ) : $i );
    if ( $i == 0 && $TIME_FORMAT == '12' ) $ihour = 12;
    $ret .= "<option value=\"$i\"" .
      ( $ihour == $hour ? $selected : '' ) . ">$ihour</option>\n";
  }
  $ret .= "</select>:\n<select " . $minnameid . $trigger_str . " >\n";
  //we use $TIME_SLOTS to populate the minutes pulldown
  $found = false;
  for ( $i = 0; $i <= 59; ) {
    $imin = sprintf ( "%02d", $i );
    $isselected = '';
    if ( $imin == $minute ) {
      $found = true;
      $isselected = $selected;  
    }
    $ret .= "<option value=\"$i\"$isselected >$imin</option>\n";
    $i += (1440 / $ENTRY_SLOTS);
  }
  //we'll add an option with the exact time if not found above
  if ( $found == false ) {
    $ret .= "<option value=\"$minute\" $selected >$minute</option>\n";
  }
  $ret .= "</select>\n";

  if ( $TIME_FORMAT == '12' ) {
    $ret .= '<label><input type="radio" name="' . $prefix . 
      'ampm" id="'. $prefix . 'ampmA" value="0" ' ."$amsel />&nbsp;" . 
      translate( 'am' ) . "</label>\n";
    $ret .= '<label><input type="radio" name="' . $prefix . 
      "ampm\" id=\"". $prefix . "ampmP\" value=\"12\" $pmsel />&nbsp;" . 
      translate( 'pm' ) . "</label>\n";
  } else {
    $ret .= '<input type="hidden" name="' . $prefix . 'ampm" value="0" />' ."\n";
  }
  return $ret;
}

load_user_categories ();
   
// Default for using tabs is enabled
if ( empty ( $EVENT_EDIT_TABS ) )
  $EVENT_EDIT_TABS = 'Y'; // default
$useTabs = ( $EVENT_EDIT_TABS == 'Y' );
// make sure this is not a read-only calendar
$can_edit = false;

$checked = ' checked="checked" ';
$selected = ' selected="selected" ';

// Public access can only add events, not edit.
if ( $login == '__public__' && $id > 0 ) {
  $id = 0;
}

$eType = getGetValue ( 'eType');
if ( empty ( $eType ) ) $eType =  'event';
$month = getIntValue ( 'month' );
$day = getIntValue ( 'day' );
$year = getIntValue ( 'year' );
$date = getIntValue ( 'date' );
if ( empty ( $date ) && empty ( $month ) ) {
  if ( empty ( $year ) ) $year = date ( 'Y' );
  if ( empty ( $month ) ) $month = date ( 'm' );
  if ( empty ( $day ) ) $day = date ( 'd' );
  $date = sprintf ( "%04d%02d%02d", $year, $month, $day );
}

// Do we use HTMLArea of FCKEditor?
// Note: HTMLArea has been discontinued, so FCKEditor is preferred.
$use_htmlarea = false;
$use_fckeditor = false;
if ( $ALLOW_HTML_DESCRIPTION == 'Y' ){
  if ( file_exists ( 'includes/FCKeditor-2.0/fckeditor.js' ) &&
    file_exists ( 'includes/FCKeditor-2.0/fckconfig.js' ) ) {
    $use_fckeditor = true;
  } else if ( file_exists ( 'includes/htmlarea/htmlarea.php' ) ) {
    $use_htmlarea = true;
  }
}

$external_users = $byweekno = $byyearday = $rpt_count = $catNames = $catList='';
$participants = $exceptions = $inclusions = $reminder = array();
$byday = $bymonth = $bymonthday = $bysetpos = array();
$wkst = 'MO';
$create_by = $login;

$real_user =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
  $is_admin ) ) ? $user : $login;

if ( $readonly == 'Y' || $is_nonuser ) {
  $can_edit = false;
} else if ( ! empty ( $id ) && $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin ) {
    $can_edit = true;
  }
  $sql = 'SELECT cal_create_by, cal_date, cal_time, cal_mod_date, ' .
    'cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, ' .
    ' cal_name, cal_description, cal_group_id, cal_location,  ' .
    ' cal_due_date, cal_due_time, cal_completed, cal_url ' .
    'FROM webcal_entry WHERE cal_id = ?';

  $res = dbi_execute ( $sql, array( $id ) );
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
    if (( $user == $create_by ) && ( $is_assistant || $is_nonuser_admin ))
      $can_edit = true;

    $cal_time = sprintf( "%06d", $row[2] );
    $due_date = $row[13];
    $due_time = $row[14];
    
    $calTS = date_to_epoch ( $cal_date . $cal_time );
    //Don't adjust for All Day entries
    if ( $cal_time > 0 || ( $cal_time == 0 &&  $row[5] != 1440 ) ) {
      $cal_date = date ( 'Ymd', $calTS );
      $cal_time = date (  'His', $calTS );
    }
    $hour = floor($cal_time / 10000);
    $minute = ( $cal_time / 100 ) % 100;
  
    $dueTS = date_to_epoch ( $due_date . $due_time );
    $due_date = date ( 'Ymd', $dueTS );
    $due_time = date (  'His', $dueTS );
    $due_hour = floor($due_time / 10000);
    $due_minute = ( $due_time / 100 ) % 100;
   
    $priority = $row[6];
    $type = $row[7];
    $access = $row[8];
    $name = $row[9];
    $description = $row[10];
    $completed = ( ! empty ( $row[15] )? $row[15] : date ( 'Ymd'));
    $location = $row[12];
    $cal_url = $row[16];    
     
    //what kind of entry are we dealing with?
    if ( $type == 'E' || $type == 'M' ) {
      $eType = 'event';
    } else if ( $type == 'T' || $type == 'N' ) {      
      $eType = 'task';
    } else if ( $type == 'J' || $type == 'O' ) {      
      $eType = 'journal';
    }

    // Public access has no access to tasks
    if ( $login == '__public__' && $eType == 'task') {
      echo translate( 'You are not authorized to edit this task' ) . '.';
    }
     
    //check UAC
    if ( access_is_enabled () ) {
      $can_edit = access_user_calendar ( 'edit', $create_by, $login, $type, $access );
    }
    
    $year = (int) ( $cal_date / 10000 );
    $month = ( $cal_date / 100 ) % 100;
    $day = $cal_date % 100;
    $time = $row[2];

    if ( $time >= 0 ) {
      $duration = $row[5];
    } else {
      $duration = '';
      $hour = -1;
    }
    $priority = $row[6];
    $type = $row[7];
    $access = $row[8];
    $name = $row[9];
    $description = $row[10];
    $parent = $row[11];
    $location = $row[12];
//  }
//   dbi_free_result ( $res );
    // check for repeating event info...
    // but not if we are overriding a single entry of an already repeating
    // event... confusing, eh?
    if ( ! empty ( $override ) ) {
      $rpt_type = 'none';
      $rpt_end = 0;
      $rpt_end_date = $cal_date;
      $rpt_freq = 1;
    } else {
      $res = dbi_execute ( 'SELECT cal_id, cal_type, cal_end, cal_endtime, ' .
        'cal_frequency, cal_byday, cal_bymonth, cal_bymonthday, cal_bysetpos, ' .  
        'cal_byweekno, cal_byyearday, cal_wkst, cal_count  ' .
        'FROM webcal_entry_repeats WHERE cal_id = ?', array( $id ) );
      if ( $res ) {
        if ( $row = dbi_fetch_row ( $res ) ) {
          $rpt_type = $row[1];
          if ( $row[2] > 0 )
            $rpt_end = date_to_epoch( $row[2] . $row[3] );
          else
            $rpt_end = 0;
          if ( ! empty ( $row[2] ) ) {
             $rpt_endTS = date_to_epoch( $row[2] . $row[3] );
             $rpt_end_date = date( 'Ymd', $rpt_endTS );
             $rpt_end_time = date( 'His', $rpt_endTS );
          }  else {
            $rpt_end_date = $cal_date;
            $rpt_end_time = $cal_time;
          }        
          $rpt_freq = $row[4];
          $byday = explode(',',$row[5]);
          $bydayStr = $row[5];
          $bymonth = explode(',',$row[6]);
          $bymonthday = explode(',', $row[7]);
          $bymonthdayStr = $row[7];
          $bysetpos = explode(',', $row[8]);
          $bysetposStr = $row[8];
          $byweekno = $row[9];
          $byyearday = $row[10];
          $wkst = $row[11];
          $rpt_count = $row[12];
               
          //Check to see if Weekends Only is applicable
          $weekdays_only = ( $rpt_type == 'daily' &&
    $byday == 'MO,TU,WE,TH,FR' ? true : false );
        }
        dbi_free_result ( $res );
      }
    }
   
  $sql = 'SELECT cal_login,  cal_percent, cal_status ' .
   ' FROM webcal_entry_user WHERE cal_id = ?';
  $res = dbi_execute ( $sql, array( $id ) );
 if ( $res ) {
   while ( $row = dbi_fetch_row ( $res ) ) {
     $overall_percent[] = $row; 
     if ( $login == $row[0] || ( $is_admin && $user == $row[0]) ) {
       $task_percent = $row[1];
       $task_status = $row[2];
     } 
  }  
    dbi_free_result ( $res ); 
 }
 
    //determine if Expert mode needs to be set
    $expert_mode = ( isset ( $rpt_count ) || isset ($byyearday ) || isset($byweekno) ||
      isset ($bysetpos) || isset($bymonthday) || isset ($bymonth) || isset($byday));
  
    //Get Repeat Exceptions
  $sql = 'SELECT cal_date, cal_exdate FROM webcal_entry_repeats_not WHERE cal_id = ?';
    $res = dbi_execute ( $sql, array( $id ) );
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
  if ( $CATEGORIES_ENABLED == 'Y' ) {
    //get global categories
    $sql = 'SELECT  webcal_entry_categories.cat_id, cat_name ' .
      ' FROM webcal_entry_categories, webcal_categories ' .
      ' WHERE webcal_entry_categories.cat_id = webcal_categories.cat_id AND ' .
      ' webcal_entry_categories.cal_id = ?  AND ' . 
      ' webcal_categories.cat_owner IS NULL ';
    $res = dbi_execute ( $sql, array( $id ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
       $cat_id[] = '-' .$row[0];
       $cat_name[] = $row[1] . '*';    
      }
    dbi_free_result ( $res );
   }
    //get user's categories 
    $sql = 'SELECT  webcal_entry_categories.cat_id, ' .
      ' webcal_entry_categories.cat_owner, webcal_entry_categories.cat_order, cat_name ' .
      ' FROM webcal_entry_categories, webcal_categories ' .
      ' WHERE ( webcal_entry_categories.cat_id = webcal_categories.cat_id AND ' .
      ' webcal_entry_categories.cal_id = ? ) AND ' . 
      ' webcal_categories.cat_owner = ?'.
      ' ORDER BY webcal_entry_categories.cat_order';
    $res = dbi_execute ( $sql, array( $id, $real_user ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( empty ( $user ) || $login == $user || $is_assistant  || $is_admin ) {
          $cat_id[] = $row[0];
          $cat_name[] = $row[3];    
        }
      }
      dbi_free_result ( $res );
      if ( ! empty ( $cat_name ) ) $catNames = implode(',' , array_unique($cat_name));
      if ( ! empty ( $cat_id ) ) $catList = implode(',', array_unique($cat_id));
    }
  } //end CATEGORIES_ENABLED test

  //get reminders 
  $reminder = getReminders ( $id ); 
  $reminder_offset = ( ! empty (  $reminder ) ? $reminder['offset'] : 0 );
  
  //get participants
  $sql = 'SELECT cal_login FROM webcal_entry_user WHERE cal_id = ? AND ' .
    " cal_status IN ('A', 'W' )";
  $res = dbi_execute ( $sql, array( $id ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$row[0]] = 1;
    }
    dbi_free_result ( $res );    
  }
//Not allowed for tasks or journals 
  if (  $eType == 'event'  && ! empty ( $ALLOW_EXTERNAL_USERS ) && 
    $ALLOW_EXTERNAL_USERS == 'Y' ) {
    $external_users = event_get_external_users ( $id );
  }
} else {
  // ##########   New entry.   ################
  $id = 0; // to avoid warnings below about use of undefined var
 //We'll use $WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR
 // As our starting and due times
 $cal_time = $WORK_DAY_START_HOUR . '0000';
 $due_time = $WORK_DAY_END_HOUR . '0000';
 $due_hour = $WORK_DAY_END_HOUR;
 $due_minute = 0;
 $task_percent = 0;
 $completed = '';
 $overall_percent =  array();
 
 //reminder settings
 $reminder_offset = ($REMINDER_WITH_DATE =='N' ? $REMINDER_OFFSET:0);
 

 if ( $eType == 'task' ) {
   $hour = $WORK_DAY_START_HOUR;
 }
  // Anything other then testing for strlen breaks either hour=0 or no hour in URL
  if ( strlen ( $hour ) ) {
    $time = $hour * 100;  
  } else {
    $time = -1;
    $hour = -1;
  }
  if ( ! empty ( $defusers ) ) {
    $tmp_ar = explode ( ',', $defusers );
    for ( $i = 0, $cnt = count ( $tmp_ar ); $i < $cnt; $i++ ) {
      $participants[$tmp_ar[$i]] = 1;
    }
  }
  if ( $readonly == 'N' ) {
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
  $rpt_type = 'none';
// avoid error for using undefined vars
if ( ! isset ( $hour ) && $hour != 0 ) {
  $hour = -1;
} else if ( isset ( $hour ) && $hour >= 0 ){
  $cal_time = ( $hour * 10000 ) + ( isset ( $minute ) ? $minute * 100 : 0 );  
}

if ( empty ( $duration ) )
  $duration = 0;
if ( $duration == 1440 && $time == 0 ) {
  $hour = $minute = $duration = '';
  $allday = 'Y';
} else {
  $allday = 'N';
}
if ( empty ( $name ) )
  $name = '';
if ( empty ( $description ) || $description == '<br />' )
  $description = '';
if ( empty ( $location ) )
  $location = '';
if ( empty ( $priority ) )
  $priority = 0;
if ( empty ( $access ) )
  $access = '';
if ( empty ( $rpt_freq ) )
  $rpt_freq = 0;
if ( empty ( $rpt_end_date ) )
  $rpt_end_date = 0;
if ( empty ( $rpt_end_time ) )
  $rpt_end_time = 0;

if ( empty ( $cal_date ) ) {
  if ( ! empty ( $date ) && $eType != 'task' )
    $cal_date = $date;
  else
    $cal_date = date ( 'Ymd' );
  if ( empty ( $due_date ) )
    $due_date = date ( 'Ymd' );
}
if ( empty ( $thisyear ) )
  $thisdate = date ( 'Ymd' );
else {
  $thisdate = sprintf ( "%04d%02d%02d",
    empty ( $thisyear ) ? date ( 'Y' ) : $thisyear,
    empty ( $thismonth ) ? date ( 'm' ) : $thismonth,
    empty ( $thisday ) ? date ( 'd' ) : $thisday );
}
if ( empty ( $cal_date ) || ! $cal_date ) {
  $cal_date = $thisdate;
}
if ( empty ( $due_date ) || ! $due_date )
  $due_date = $thisdate;

//Setup to display user's timezone difference if Admin or Assistane
//Even though event is stored in GMT, an Assistant may need to know that
//the boss is in a different Timezone
if ( $is_assistant || $is_admin && ! empty ( $user ) ) {
  $tz_offset = date ( 'Z', date_to_epoch ( $cal_date . $cal_time ) );   
  $user_TIMEZONE = get_pref_setting ( $user, 'TIMEZONE' );
  set_env ( 'TZ', $user_TIMEZONE );
  $user_tz_offset = date ( 'Z', date_to_epoch ( $cal_date . $cal_time ) );
  if ( $tz_offset != $user_tz_offset ) {  //Different TZ_Offset
    user_load_variables ( $user, 'temp' );
    $tz_diff = ( $user_tz_offset - $tz_offset ) / ONE_HOUR;
    $tz_value = ( $tz_diff > 0? translate ( 'hours ahead of you' ) :
      translate ( 'hours behind you' ) );
    $tz_value = ( $tz_diff == 1? translate ( 'hour ahead of you' ) :
      translate ( 'hour behind you' ) );
    $TZ_notice = '(' . $tempfullname . ' ' . 
      translate ( 'is in a different timezone than you are. Currently' ) . ' ';
      //TODO show hh:mm instead of abs 
    $TZ_notice .= abs ( $tz_diff ) . ' ' . $tz_value . '.<br />&nbsp;'; 
    $TZ_notice .= translate ( 'Time entered here is based on your Timezone' ) . '.)'; 
  }
  //return to $login TIMEZONE
  set_env ( 'TZ', $TIMEZONE );
}

$textareasize = 'rows="15" cols="50"';
$INC = array (  'js/visible.php/true', "js/edit_entry.php/false/$user" );
$BodyX = 'onload="onLoad();"';
if ( $ALLOW_HTML_DESCRIPTION == 'Y' ){
  // Allow HTML in description
  // If they have installed the htmlarea widget, make use of it
  if ( $use_fckeditor ) {
    $textareasize = 'rows="20" cols="50"';
  } else if ( $use_htmlarea ) {
    $BodyX = 'onload="initEditor();onLoad()"';
    $INC[] = 'htmlarea/htmlarea.php/true';
    $INC[] = 'htmlarea/core.php/true';
  }
}
print_header ( $INC, '', $BodyX );

$eType_label = ' ( ' . translate ( $eType ) . ' )';
?>
<h2><?php  echo ( $id? translate( 'Edit Entry' ): translate( 'Add Entry' )) . $eType_label;?>&nbsp;<img src="images/help.gif" alt="<?php etranslate( 'Help' )?>" class="help" onclick="window.open ( 'help_edit_entry.php<?php if ( empty ( $id ) ) echo '?add=1'; ?>', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<?php
   if ( $can_edit ) {
?>
<form action="edit_entry_handler.php" method="post" name="editentryform">

<?php
echo "<input type=\"hidden\" name=\"eType\" value=\"$eType\" />\n";
if ( ! empty ( $id ) && ( empty ( $copy ) || $copy != '1' ) ) echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
// we need an additional hidden input field
echo '<input type="hidden" name="entry_changed" value="" />' ."\n";

// are we overriding an entry from a repeating event...
if ( ! empty ( $override ) ) {
  echo '<input type="hidden" name="override" value="1" />' ."\n";
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
 <span class="tabfor" id="tab_details"><a href="#tabdetails" onclick="return showTab('details')"><?php etranslate( 'Details' ) ?></a></span>
 <?php if ( $DISABLE_PARTICIPANTS_FIELD != 'Y' ) { ?>
   <span class="tabbak" id="tab_participants"><a href="#tabparticipants" onclick="return showTab('participants')"><?php etranslate( 'Participants' ) ?></a></span>
 <?php } 
if ( $DISABLE_REPEATING_FIELD != 'Y' ) { ?>
   <span class="tabbak" id="tab_pete"><a href="#tabpete" onclick="return showTab('pete')"><?php etranslate( 'Repeat' ) ?></a></span>
 <?php } 
if ( $DISABLE_REMINDER_FIELD != 'Y' ) { ?>
   <span class="tabbak" id="tab_reminder"><a href="#tabreminder" onclick="return showTab('reminder')"><?php etranslate( 'Reminders' ) ?></a></span>
 <?php } ?>
</div>
<?php } ?>

<!-- TABS BODY -->
<?php if ( $useTabs ) { ?>
<div id="tabscontent">
 <!-- DETAILS -->
 <a name="tabdetails"></a>
 <div id="tabscontent_details">
<?php } else { ?>
<fieldset>
 <legend><?php etranslate('Details')?></legend>
<?php } ?>
  <table border="0">
   <tr><td style="width:14%;" class="tooltip" title="<?php 
    etooltip( 'brief-description-help' )?>">
    <label for="entry_brief"><?php 
      etranslate( 'Brief Description' )?>:</label></td><td colspan="2">
    <input type="text" name="name" id="entry_brief" size="25" value="<?php 
     echo htmlspecialchars ( $name );
    ?>" /></td></tr>
   <tr><td class="tooltip aligntop" title="<?php 
    etooltip( 'full-description-help' )?>">
    <label for="entry_full"><?php etranslate( 'Full Description' )?>:</label></td><td>
    <textarea name="description" id="entry_full" <?php
     echo $textareasize;
    ?>><?php
     echo htmlspecialchars ( $description );
    ?></textarea></td>
<?php if ( $use_fckeditor ||  $use_htmlarea  ) { ?>
    </tr><tr><td  colspan="2" class="aligntop">
<?php } else {?>
    <td class="aligntop">
<?php }
  
if (( ! empty ( $categories ) ) || ( $DISABLE_ACCESS_FIELD != 'Y' ) || 
         ( $DISABLE_PRIORITY_FIELD != 'Y' ) ){ // new table for extra fields ?>
    <table border="0" width="90%">
<?php }
if ( $DISABLE_ACCESS_FIELD != 'Y' ) { ?>
  <tr><td class="tooltip" title="<?php etooltip( 'access-help' )?>">
    <label for="entry_access"><?php etranslate( 'Access' )?>:</label></td><td width="80%">
       <select name="access" id="entry_access">
        <option value="P"<?php if ( $access == 'P' || ! strlen ( $access ) ) 
  echo $selected;?>><?php etranslate ( 'Public' )?></option>
        <option value="R"<?php if ( $access == 'R' ) 
  echo $selected;?>><?php etranslate( 'Private' )?></option>
        <option value="C"<?php if ( $access == 'C' ) 
  echo $selected;?>><?php etranslate( 'Confidential' )?></option>        
       </select>
       </td></tr>
<?php } 
if ( $DISABLE_PRIORITY_FIELD != 'Y' ) { ?>
  <tr><td class="tooltip" title="<?php etooltip( 'priority-help' )?>">
  <label for="entry_prio"><?php etranslate( 'Priority' )?>:&nbsp;</label></td><td>
      <select name="priority" id="entry_prio">
       <option value="1"<?php if ( $priority == 1 ) 
  echo $selected;?>><?php etranslate( 'Low' )?></option>
       <option value="2"<?php if ( $priority == 2 || $priority == 0 ) 
  echo $selected;?>><?php etranslate( 'Medium' )?></option>
       <option value="3"<?php if ( $priority == 3 ) 
  echo $selected;?>><?php etranslate( 'High' )?></option>
      </select>
     </td></tr>
<?php } 
if ( ! empty ( $categories ) && $CATEGORIES_ENABLED == 'Y' ) { ?>
  <tr><td class="tooltip" title="<?php etooltip( 'category-help' )?>" valign="top">
   <label for="entry_categories"><?php etranslate( 'Category' )?>:<br /></label>
   <input type="button" value="<?php etranslate( 'Edit' ) ?>" onclick="editCats(event)" />
   </td><td valign="top">
      <input  readonly="readonly" type="text" name="catnames" id="entry_categories" 
     value="<?php echo $catNames ?>"  size="30" onclick="editCats(event)"/>
   <input  type="hidden" name="cat_id" value="<?php echo $catList ?>" />
     </td></tr>
<?php } //end if (! empty ($categories))
if (( ! empty ( $categories ) ) || ( $DISABLE_ACCESS_FIELD != 'Y' ) || 
  ( $DISABLE_PRIORITY_FIELD != 'Y' ) ){ // end the table ?>
 </table>
    
<?php } 
if ( $eType == 'task' ) { //only for tasks 
  $completed_visible = ( strlen ( $completed ) ? 'visible' : 'hidden' );
?>
  <br />
  <table border="0">
    <tr id="completed">
    <td class="tooltip" title="<?php etooltip( 'completed-help' )?>">
    <label for="task_percent"><?php etranslate( 'Date Completed' )?>:&nbsp;</label></td>
    <td><?php echo date_selection ( 'completed_', $completed ); ?>
    </td></tr>
   <tr><td class="tooltip" title="<?php etooltip( 'percent-help' )?>">
    <label for="task_percent"><?php etranslate( 'Percent Complete' )?>:&nbsp;</label></td><td>
    <select name="percent" id="task_percent" onchange="completed_handler()">
   <?php  
     for ( $i=0; $i<=100 ; $i+=10 ){ 
       echo "<option value=\"$i\" " .
         ($task_percent == $i? $selected:''). ' >' .
          $i . "</option>\n";
     }
    echo "</select></td></tr>\n";
    if ( ! empty ( $overall_percent ) ) {
      echo  "<tr><td colspan=\"2\">\n<table width=\"100%\" border=\"0\"" .
      ' cellpadding="2" cellspacing="5">'.
      "<tr>\n<td colspan=\"2\">". translate( 'All Percentages' ) . '</td></tr>';
      $others_complete = 'yes';
      for ( $i = 0, $cnt = count ( $overall_percent ); $i < $cnt; $i++ ) {
        user_load_variables ( $overall_percent[$i][0], 'percent' );
        echo  '<tr><td>' . $percentfullname . '</td><td>' .
           $overall_percent[$i][1] . "</td></tr>\n";
        if ( $overall_percent[$i][0] != $real_user && 
         $overall_percent[$i][1] < 100 ) $others_complete = 'no';
      }
      echo  '</table>';
    }

    ?>
    </td></tr> </table>
   <input type="hidden" name="others_complete" value="<?php echo $others_complete ?>" />
<?php  } //end tasks only ?> 
 
  </td></tr>
<?php if ( $DISABLE_LOCATION_FIELD != 'Y'  ){  ?>
 <tr><td class="tooltip" title="<?php etooltip( 'location-help' )?>">
    <label for="entry_location"><?php etranslate( 'Location' )?>:</label></td><td colspan="2">
    <input type="text" name="location" id="entry_location" size="55" 
   value="<?php echo htmlspecialchars ( $location ); ?>" />
  </td></tr>
<?php } 
echo '<tr><td class="tooltip" title="' . tooltip( 'date-help' ) . '"><label>';
echo  ( $eType == 'task'? translate( 'Start Date' ):translate( 'Date' ) ) . 
  ':</label></td><td colspan="2">' .  "\n";
echo date_selection ( '', $cal_date );

echo "</td></tr>\n";
if ( $eType != 'task' ) {?>
  <tr><td>&nbsp;</td><td colspan="2">
   <select name="timetype" onchange="timetype_handler()">
    <option value="U" <?php if ( $allday != 'Y' && $hour == -1 ) 
  echo $selected;?>><?php etranslate( 'Untimed event' ); ?></option>
    <option value="T" <?php if ( $allday != 'Y' && $hour >= 0 ) 
  echo $selected;?>><?php etranslate( 'Timed event' ); ?></option>
    <option value="A" <?php if ( $allday == 'Y' ) 
  echo $selected;?>><?php etranslate( 'All day event' ); ?></option>
   </select>
  </td></tr>
 <?php if ( ! empty ( $TZ_notice ) ) { ?>
   <tr id="timezonenotice"><td class="tooltip" title="<?php 
  etooltip( 'Time entered here is based on your Timezone' )?>">
   <?php etranslate ( 'Timezone Offset' )?>:</td><td colspan="2">
   <?php echo $TZ_notice ?></td></tr>
 <?php } ?>
  <tr id="timeentrystart" style="visibility:hidden;">
    <td class="tooltip" title="<?php etooltip( 'time-help' )?>">
   <?php echo translate( 'Time' ) . ':'; ?></td><td colspan="2">
<?php
 echo time_selection ( 'entry_', $cal_time);
 $dur_h = (int)( $duration / 60 );
 $dur_m = $duration - ( $dur_h * 60 );

 if ($TIMED_EVT_LEN != 'E') { ?>
   </td></tr>
  <tr id="timeentryduration" style="visibility:hidden;"><td>
  <span class="tooltip" title="<?php 
   echo tooltip( 'duration-help' ) . '">' . translate('Duration')
  ?>:&nbsp;</span></td><td colspan="2">
  <input type="text" name="duration_h" id="duration_h" size="2" maxlength="2" value="<?php 
  if ( $allday != 'Y' ) printf ( "%d", $dur_h );
  ?>" />:
  <input type="text" name="duration_m" id="duration_m" size="2" maxlength="2" value="<?php 
   if ( $allday != 'Y' ) 
    printf ( "%02d", $dur_m );
  ?>" />&nbsp;(<label for="duration_h"><?php 
   echo translate( 'hours' )
  ?></label>: <label for="duration_m"><?php 
   echo translate( 'minutes' )
  ?></label>)
 </td></tr>
<?php } else {
  $end_time = ( $id ? add_duration ($cal_time, $duration ) : $cal_time );
?>
 <span id="timeentryend" class="tooltip" title="<?php 
  etooltip( 'end-time-help' )?>">&nbsp;-&nbsp;<?php 
  echo time_selection ( 'end_', $end_time);?>
 </span>
</td></tr>
<?php } 
}else { //eType == task?>
  <tr><td class="tooltip" title="<?php etooltip( 'time-help' )?>">
   <?php echo translate( 'Start Time' ) . ':'; ?></td><td colspan="2">
<?php
 echo time_selection ( 'entry_', $cal_time);
?>
</td></tr>
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td class="tooltip" title="<?php etooltip( 'date-help' )?>">
   <?php etranslate( 'Due Date' )?>:</td><td colspan="2">
   <?php 
    echo date_selection ( 'due_', $due_date );
   ?>
  </td></tr>
  <tr><td class="tooltip" title="<?php etooltip( 'time-help' )?>">
   <?php echo translate( 'Due Time' ) . ':'; ?></td><td colspan="2">
  <?php
   echo time_selection ( 'due_', $due_time);
  ?>
</td></tr>

<?php } ?>

</table>

<?php
// site-specific extra fields (see site_extras.php)
// load any site-specific fields and display them
if ( $id > 0 )
  $extras = get_site_extra_fields ( $id );
$site_extracnt = count ( $site_extras );
if ( $site_extracnt ) 
  echo '<table>';
for ( $i = 0; $i < $site_extracnt; $i++ ) {
  $extra_name = $site_extras[$i][0];
  $extra_descr = $site_extras[$i][1];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];

  if ( $extra_type == EXTRA_MULTILINETEXT )
    echo '<tr><td class="aligntop bold"><br />' ."\n";
  else
    echo '<tr><td class="bold">';
  echo translate ( $extra_descr ) .  ":</td><td>\n";
  if ( $extra_type == EXTRA_URL ) {
    echo '<input type="text" size="50" name="' . $extra_name .
      '" value="' . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      '' : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . '" />';
  } else if ( $extra_type == EXTRA_EMAIL ) {
    echo '<input type="text" size="30" name="' . $extra_name . 
      '" value="' . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      '' : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . '" />';
  } else if ( $extra_type == EXTRA_DATE ) {
    if ( ! empty ( $extras[$extra_name]['cal_date'] ) )
      echo date_selection ( $extra_name, $extras[$extra_name]['cal_date'] );
    else
      echo date_selection ( $extra_name, $cal_date );
  } else if ( $extra_type == EXTRA_TEXT ) {
    $size = ( $extra_arg1 > 0 ? $extra_arg1 : 50 );
    echo '<input type="text" size="' . $size . '" name="' . $extra_name .
      '" value="' . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      '': htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . '" />';
  } else if ( $extra_type == EXTRA_MULTILINETEXT ) {
    $cols = ( $extra_arg1 > 0 ? $extra_arg1 : 50 );
    $rows = ( $extra_arg2 > 0 ? $extra_arg2 : 5 );
    echo '<textarea rows="' . $rows . '" cols="' . $cols . '" name="' . 
      $extra_name . '">' . ( empty ( $extras[$extra_name]['cal_data'] ) ?
      '' : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) . '</textarea>';
  } else if ( $extra_type == EXTRA_USER ) {
    // show list of calendar users...
    echo '<select name="' . $extra_name . "\">\n";
    echo '<option value="">None</option>' ."\n";
    $userlist = get_my_users ( get_my_users );
    $usercnt = count ( $userlist );
    for ( $j = 0; $j < $usercnt; $j++ ) {
      if ( access_is_enabled () &&
        ! access_user_calendar ( 'view', $userlist[$j]['cal_login'] ) )
        continue; // cannot view calendar so cannot add to their cal
      echo '<option value="' . $userlist[$j]['cal_login'] . '"';
        if ( ! empty ( $extras[$extra_name]['cal_data'] ) &&
          $userlist[$j]['cal_login'] == $extras[$extra_name]['cal_data'] )
          echo $selected;
        echo '>' . $userlist[$j]['cal_fullname'] . "</option>\n";
    }
    echo "</select>\n";
  } else if ( $extra_type == EXTRA_SELECTLIST ) {
    // show custom select list.
    echo '<select name="' . $extra_name . "\">\n";
    if ( is_array ( $extra_arg1 ) ) {
      $extra_arg1cnt = count ( $extra_arg1 ); 
      for ( $j = 0; $j < $extra_arg1cnt; $j++ ) {
        echo "<option";
        if ( ! empty ( $extras[$extra_name]['cal_data'] ) &&
          $extra_arg1[$j] == $extras[$extra_name]['cal_data'] )
          echo $selected;
        echo '>' . $extra_arg1[$j] . "</option>\n";
      }
    }
    echo "</select>\n";
  }
  echo "</td></tr>\n"; 
}
if ( $site_extracnt )
  echo "</table>\n";
// end site-specific extra fields

if ( $useTabs ) { ?>
</div>
<?php } else { ?>
</fieldset>
<?php } /* $useTabs */ ?>

<!-- PARTICIPANTS -->
<?php if ( $useTabs ) { ?>
<a name="tabparticipants"></a>
<div id="tabscontent_participants">
<?php } else { ?>
<fieldset>
 <legend><?php etranslate('Participants')?></legend>
<?php } /* $useTabs */ ?>
<table>
<?php
// Only ask for participants if we are multi-user.
$show_participants = ( $DISABLE_PARTICIPANTS_FIELD != 'Y' );
if ( $is_admin )
  $show_participants = true;
if ( $login == '__public__' && $PUBLIC_ACCESS_OTHERS != 'Y' )
  $show_participants = false;

if ( $single_user == 'N' && $show_participants ) {
  $userlist = get_my_users ( $create_by, 'invite' );
  if ($NONUSER_ENABLED == 'Y' ) {
    // include public NUCs
    $nonusers = get_my_nonusers ( $real_user, true );
    $userlist = ($NONUSER_AT_TOP == 'Y') ? 
      array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  $usercnt = count ( $userlist );
  $num_users = 0;
  $size = 0;
  $users = '';
  for ( $i = 0; $i < $usercnt; $i++ ) {
    $l = $userlist[$i]['cal_login'];
    $size++;
    $users .= '<option value="' . $l . '"';
    if ( $id > 0 ) {
      if ( ! empty ($participants[$l]) )
        $users .= $selected;
    } else {
      if ( ! empty ($defusers) && ! empty ( $participants[$l] ) ) {
        // default selection of participants was in the URL
        $users .= $selected;
      }
      if ( ($l == $login && ! $is_assistant  && 
        ! $is_nonuser_admin) || (! empty ($user) && $l == $user) ) {
        $users .= $selected;
      }
      if ( $l == '__public__' &&
        ! empty ($PUBLIC_ACCESS_DEFAULT_SELECTED) &&
         $PUBLIC_ACCESS_DEFAULT_SELECTED == 'Y' )
           $users .= $selected;
    }
    $users .= '>' . $userlist[$i]['cal_fullname'] . "</option>\n";
  }

  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  echo '<tr title="' . 
 tooltip( 'participants-help' ) . '"><td class="tooltipselect">' ."\n" .
  '<label for="entry_part">' . translate( 'Participants' ) . ":</label></td><td>\n";
  echo "<select name=\"participants[]\" id=\"entry_part\" size=\"$size\" multiple=\"multiple\">$users\n";
  echo "</select>\n";
  if ( $GROUPS_ENABLED == 'Y' ) {
    echo '<input type="button" onclick="selectUsers()" value="' .
      translate( 'Select' ) . '..." />' . "\n";
  }
  echo '<input type="button" onclick="showSchedule()" value="' .
    translate( 'Availability' ) . '..." />' ."\n";
  echo "</td></tr>\n";

  // external users
  if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == 'Y' ) {
    echo '<tr title="' .
      tooltip( 'external-participants-help' ) . '"><td class="tooltip aligntop">' . 
        "\n" . '<label for="entry_extpart">' .
      translate( 'External Participants' ) . ':</label></td><td>' . "\n";
    echo '<textarea name="externalparticipants" id="entry_extpart" rows="5" cols="40">';
    echo $external_users . "</textarea>\n</td></tr>\n";
  }
}
?>
</table>
<?php if ( $useTabs ) { ?>
</div>
<?php } else { ?>
</fieldset>
<?php } /* $useTabs */ ?>

<!-- REPEATING INFO -->
<?php if ( $DISABLE_REPEATING_FIELD != 'Y' ) { 
if ( $useTabs ) { ?>
<a name="tabpete"></a>
<div id="tabscontent_pete">
<?php } else { ?>
<fieldset>
 <legend><?php etranslate('Repeat')?></legend>
<?php } /* $useTabs */ ?>

<table border="0" cellspacing="0" cellpadding="3">
 <tr>
 <td class="tooltip" title="<?php etooltip( 'repeat-type-help' )?>">
 <label for="rpttype"><?php etranslate( 'Type' )?>:</label></td><td colspan="2">
 <select name="rpt_type" id="rpttype" onchange="rpttype_handler();rpttype_weekly()">
<?php
 echo '  <option value="none"' . 
  ( strcmp ( $rpt_type, 'none' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'None' ) . "</option>\n";
 echo '  <option value="daily"' . 
  ( strcmp ( $rpt_type, 'daily' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Daily' ) . "</option>\n";
 echo '  <option value="weekly"' . 
  ( strcmp ( $rpt_type, 'weekly' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Weekly' ) . "</option>\n";
 echo '  <option value="monthlyByDay"' . 
  ( strcmp ( $rpt_type, 'monthlyByDay' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Monthly' ) . ' (' . translate( 'by day' ) . ')' . "</option>\n";
 echo '  <option value="monthlyByDate"' . 
  ( strcmp ( $rpt_type, 'monthlyByDate' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Monthly' ) . ' (' . translate( 'by date' ) . ')' . "</option>\n";
 echo '  <option value="monthlyBySetPos"' . 
  ( strcmp ( $rpt_type, 'monthlyBySetPos' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Monthly' ) . ' (' . translate( 'by position' ) . ')' . "</option>\n";
 echo '  <option value="yearly"' . 
  ( strcmp ( $rpt_type, 'yearly' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Yearly' ) . "</option>\n";
 echo '  <option value="manual"' . 
  ( strcmp ( $rpt_type, 'manual' ) == 0 ? $selected : '' ) . '>' . 
  translate( 'Manual' ) . "</option>\n";
?>
 </select>&nbsp;&nbsp;&nbsp;
<label id ="rpt_mode"><input type="checkbox" name="rptmode"  id="rptmode" 
  value="y" onclick="rpttype_handler()" <?php echo ( ! empty ($expert_mode)?$checked:'') ?>/>
<?php etranslate( 'Expert Mode' )?></label>
</td></tr>
<tr id="rptenddate1" style="visibility:hidden;">
 <td class="tooltip" title="<?php etooltip( 'repeat-end-date-help' )?>" rowspan="3">
  <label for="rpt_day"><?php etranslate( 'Ending' )?>:</label></td>
 <td colspan="2" class="boxleft boxtop boxright"><input  type="radio" name="rpt_end_use" id="rpt_untilf" value="f" <?php 
  echo (  empty ( $rpt_end ) && empty ( $rpt_count )? $checked : '' ); 
 ?>  onclick="toggle_until()" /><label for="rpt_untilf"><?php etranslate( 'Forever' )?></label>
 </td></tr>
 <tr id="rptenddate2" style="visibility:hidden;"><td class="boxleft">
 <input  type="radio" name="rpt_end_use" id="rpt_untilu" value="u" <?php 
  echo ( ! empty ( $rpt_end ) ? $checked : '' ); 
 ?> onclick="toggle_until()" />&nbsp;<label for="rpt_untilu"><?php etranslate( 'Use end date' )?></label>
</td><td class="boxright">
 <span class="end_day_selection" id="rpt_end_day_select"><?php
  echo date_selection ( 'rpt_', $rpt_end_date ? $rpt_end_date : $cal_date )
 ?></span><br />
 <?php
  echo time_selection ( 'rpt_', $rpt_end_time);
 ?>
</td></tr>
<tr id="rptenddate3" style="visibility:hidden;"><td class="boxleft boxbottom">
  <input type="radio" name="rpt_end_use" id="rpt_untilc" value="c" <?php 
  echo ( ! empty ( $rpt_count ) ? $checked : '' ); 
 ?> onclick="toggle_until()" />&nbsp;<label for="rpt_untilc"><?php etranslate( 'Number of times' )?></label>
 </td><td class="boxright boxbottom">

 <input type="text" name="rpt_count" id="rpt_count" size="4" maxlength="4" value="<?php echo $rpt_count; ?>" />
 
</td></tr>

 <tr id="rptfreq" style="visibility:hidden;" title="<?php 
  etooltip( 'repeat-frequency-help' )?>"><td class="tooltip">
 <label for="entry_freq"><?php etranslate( 'Frequency' )?>:</label></td><td colspan="2">
 <input type="text" name="rpt_freq" id="entry_freq" size="4" maxlength="4" value="<?php echo $rpt_freq; ?>" />
 &nbsp;&nbsp;&nbsp;&nbsp;
 <label id="weekdays_only"><input  type="checkbox" name="weekdays_only" value="y" <?php echo ( ! empty ( $weekdays_only )? $checked : "" ) ?> />
 <?php etranslate( 'Weekdays Only' )?></label>
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<span id="rptwkst">
 <select   name="wkst">
    <option value="MO" <?php ( strcmp ( $wkst, 'MO' ) == 0 ? $selected : '' ) ?>>MO</option>
    <option value="SU" <?php ( strcmp ( $wkst, 'SU' ) == 0 ? $selected : '' ) ?>>SU</option>
 </select>&nbsp;&nbsp;<label for="rptwkst" ><?php etranslate( 'Week Start' )?></label></span>
 </td>
 </tr>

 <tr id="rptbydayextended" style="visibility:hidden;" title="<?php 
  etooltip( 'repeat-bydayextended-help' )?>"><td class="tooltip">
 <label><?php echo translate( 'ByDay' ) ?>:</label></td>
    <td colspan="2" style="padding-left:0px">
  <input type="hidden" name="bydayList" value="<?php echo $bydayStr ?>" />
  <input type="hidden" name="bymonthdayList" value="<?php echo $bymonthdayStr ?>" />
  <input type="hidden" name="bysetposList" value="<?php echo $bysetposStr ?>" />
 <table class="byxxx" cellpadding="2" cellspacing="0" border="1"><tr><td></td>
 <?php
  //display byday extended selection
  //We use BUTTONS  in a triple state configuration, and store the values in
  //a javascript array until form submission. We then set the hidden field
  // bydayList to the string value of the array.
  for ( $rpt_byday_label =0;$rpt_byday_label <=6; $rpt_byday_label++){
    echo '<th width="50px"><label >' . translate($weekday_names[$rpt_byday_label]) . "</label></th>\n";
  }
  echo "</tr><tr>\n<th>" . translate ( 'All' ) . '</th>';
  for ( $rpt_byday_single =0;$rpt_byday_single <=6; $rpt_byday_single++){
    echo '<td><input type="checkbox" name="bydayAll[]" id="' .
    $byday_names[$rpt_byday_single] ."\" value=\"$byday_names[$rpt_byday_single]\"" 
     . (in_array($byday_names[$rpt_byday_single],$byday)? $checked:'') . " />\n</td>\n";
  }
  echo '</tr><tr id="rptbydayln" style="visibility:hidden;">' ."\n";
  for ( $loop_ctr=1; $loop_ctr < 6; $loop_ctr++) {
    echo '<th><label>' . $loop_ctr . '/' . ($loop_ctr - 6) . 
     '</label></th>' . "\n";
    for ( $rpt_byday =0;$rpt_byday <=6; $rpt_byday++){
       $buttonvalue = (in_array($loop_ctr . $byday_names[$rpt_byday],$byday) 
      ?$loop_ctr . $byday_names[$rpt_byday]
     : (in_array(($loop_ctr -6) . $byday_names[$rpt_byday],$byday)
     ?($loop_ctr -6) . $byday_names[$rpt_byday]:'        ')); 

    echo "<td><input  type=\"button\" name=\"byday\"" .
      " id=\"_$loop_ctr$byday_names[$rpt_byday]\"" .
      " value=\"$buttonvalue\"" .
      " onclick=\"toggle_byday(this)\" /></td>\n";
    }
   echo  "</tr>\n";
    if ( $loop_ctr  < 5 ) 
      echo  "<tr id=\"rptbydayln$loop_ctr\" style=\"visibility:hidden;\">";
 }
   echo '</table>';
?></td></tr>

<tr id="rptbymonth" style="visibility:hidden;" title="<?php 
  etooltip( 'repeat-month-help' )?>"><td class="tooltip">
 <?php etranslate( 'ByMonth' )?>:&nbsp;</td>
    <td colspan="2" style="padding-left:0px">
 <?php
   //display bymonth selection
   echo '<table cellpadding="5" cellspacing="0" border="1"><tr>';
  for ( $rpt_month =1;$rpt_month <=12; $rpt_month++){
     echo "<td><label><input type=\"checkbox\" name=\"bymonth[]\" value=\"$rpt_month\"" 
      . (in_array($rpt_month,$bymonth)? $checked:'') . ' />&nbsp;' . 
   translate (date( 'M', mktime( 0,0,0,$rpt_month,1 ) ) ) . 
     "</label>\n</td>";
    if ( $rpt_month == 6 ) echo  '</tr><tr>';
  }
   echo '</tr></table>';
?></td></tr>
 
 
  <tr  id="rptbysetpos" style="visibility:hidden;" title="<?php 
  etooltip( 'repeat-bysetpos-help' )?>">
 <td class="tooltip" id="BySetPoslabel">
<?php etranslate( 'BySetPos' )?>:&nbsp;</td>
   <td colspan="2" style="padding-left:0px;padding-right:0px">
 <?php
   //display bysetpos selection
   echo '<table  class="byxxx" cellpadding="2" cellspacing="0" border="1" ><tr><td></td>';
  for ( $rpt_bysetpos_label =1;$rpt_bysetpos_label <=10; $rpt_bysetpos_label++){
    echo "<th width=\"37px\"><label >$rpt_bysetpos_label</label></th>\n";
  }
  echo "</tr><tr>\n";
  for ( $loop_ctr=1; $loop_ctr <32; $loop_ctr++) {
       $buttonvalue = (in_array($loop_ctr,$bysetpos) 
      ?($loop_ctr):(in_array(($loop_ctr -32),$bysetpos)
     ?($loop_ctr -32):"      ")); 
      if ( $loop_ctr == 1 || $loop_ctr == 11 || $loop_ctr == 21 ) 
        echo '<th><label>' . $loop_ctr . '-' . ($loop_ctr + 9) . 
          "</label></th>\n";
      if ( $loop_ctr == 31 ) 
        echo '<th><label>31</label></th>' . "\n";
    echo '<td><input  type="button" name="bysetpos"' .
      " id=\"bysetpos$loop_ctr\" value=\"$buttonvalue\"" .
     ' onclick="toggle_bysetpos(this)" /></td>' . "\n";
       if (  $loop_ctr %10 == 0 ) echo  "</tr><tr>\n";
    
 }
   echo '</tr></table>';
 ?></td></tr>

 <tr  id="rptbymonthdayextended" style="visibility:hidden;" title="<?php 
  etooltip( 'repeat-bymonthdayextended-help' )?>">
 <td class="tooltip" id="ByMonthDaylabel">
<?php etranslate( 'ByMonthDay' )?>:&nbsp;
  </td><td colspan="2" style="padding-left:0px;padding-right:0px">
 <?php
   //display bymonthday extended selection
   echo '<table class="byxxx" cellpadding="2" cellspacing="0" border="1" ><tr><td></td>';
  for ( $rpt_bymonthday_label =1;$rpt_bymonthday_label <=10; $rpt_bymonthday_label++){
    echo "<th width=\"37px\"><label >$rpt_bymonthday_label</label></th>\n";
  }
  echo "</tr><tr>\n";
  for ( $loop_ctr=1; $loop_ctr <32; $loop_ctr++) {
       $buttonvalue = (in_array($loop_ctr,$bymonthday) 
      ?($loop_ctr):(in_array(($loop_ctr -32),$bymonthday)
     ?($loop_ctr -32):'      ')); 
      if ( $loop_ctr == 1 || $loop_ctr == 11 || $loop_ctr == 21 ) 
        echo '<th><label>' . $loop_ctr . "-" . ($loop_ctr + 9) . 
          "</label></th>\n";
      if ( $loop_ctr == 31 ) 
        echo '<th><label>31</label></th>' . "\n";
    echo '<td><input  type="button" name="bymonthday" id="bymonthday' . $loop_ctr . '"' .
      " value=\"$buttonvalue\"" .
     ' onclick="toggle_bymonthday(this)" /></td>' . "\n";
       if ( $loop_ctr %10 == 0 ) echo  "</tr><tr>\n";
    
 }
   echo '</tr></table>';

 //Populate Repeat Exceptions data for later use
 $excepts = '';
 $exceptcnt = count ( $exceptions );
 for ( $i = 0; $i < $exceptcnt; $i++ ) {
   $excepts .= '<option value="-' . $exceptions[$i] . '">-' . $exceptions[$i] . "</option>\n";
 }
  //Populate Repeat Inclusions data for later use
 $includecnt = count ( $inclusions );
 for ( $i = 0; $i < $includecnt; $i++ ) {
   $excepts .= '<option value="+' . $inclusions[$i] . '">+' . $inclusions[$i] . "</option>\n";
 }
?>
 </td> 
 </tr>


 <tr id="rptbyweekno" style="visibility:hidden;" title="<?php 
   etooltip( 'repeat-byweekno-help' )?>"><td class="tooltip">
 <?php etranslate( 'ByWeekNo' )?>:</td><td colspan="2">
 <input type="text" name="byweekno" id="byweekno" size="50" maxlength="100" value="<?php echo $byweekno; ?>" />
</td></tr>

 <tr id="rptbyyearday" style="visibility:hidden;" title="<?php 
  etooltip( 'repeat-byyearday-help' )?>"><td class="tooltip">
 <?php etranslate( 'ByYearDay' )?>:</td><td colspan="2">
 <input type="text" name="byyearday" id="byyearday" size="50" maxlength="100" value="<?php echo $byyearday; ?>" />
</td></tr> 

 <tr id="rptexceptions" style="visibility:visible;"  title="<?php 
  etooltip( 'repeat-exceptions-help' )?>">
 <td class="tooltip"><label>
 <?php echo translate( 'Exclusions' ) . '/<br />' . translate( 'Inclusions' )?>:</label></td>
 <td colspan="2" class="boxleft boxtop boxright boxbottom">
 <table border="0" width="250px">
 <tr ><td colspan="2">
 <?php echo date_selection ( 'except_', $rpt_end_date ? $rpt_end_date : $cal_date )?>
 </td></tr><tr><td align="right" valign="top" width="100">
 <label id="select_exceptions_not" style="visibility:<?php echo ( empty ( $excepts )? 'visible' : 'hidden' ) ?>;"></label>
 <select id="select_exceptions"  name="exceptions[]"  multiple="multiple" style="visibility:<?php echo ( ! empty ( $excepts )? 'visible' : 'hidden' ) ?>;" size="4" >
 <?php echo $excepts ?></select></td><td valign="top">
  <input  align="left" type="button" name="addException"  value="<?php 
  etranslate( 'Add Exception' ) ?>" onclick="add_exception(0)" /><br />
   <input  align="left" type="button" name="addInclusion"  value="<?php 
  etranslate( 'Add Inclusion' ) ?>" onclick="add_exception(1)" /><br />
 <input  align="left" type="button" name="delSelected"  value="<?php 
  etranslate( 'Delete Selected' ) ?>" onclick="del_selected()" />
</td></tr></table>

</td></tr></table>
<?php if ( $useTabs ) { ?>
</div> <!-- End tabscontent_pete -->
<?php } else { ?>
</fieldset>
<?php } /* $useTabs */ 
} ?>

<!-- REMINDER INFO -->
<?php if ( $DISABLE_REMINDER_FIELD != 'Y' ) { 
if ( $useTabs ) { ?>
<a name="tabreminder"></a>
<div id="tabscontent_reminder">
<?php } else { ?>
<fieldset>
 <legend><?php etranslate('Reminders')?></legend>
<?php } /* $useTabs */ ?>

<table border="0" cellspacing="0" cellpadding="3">
   <?php 
    echo '<thead><tr><td class="tooltip"><label>' . translate( 'Send Reminder' ) . ':</label></td>';  
    $rem_status = ( count ( $reminder) || $REMINDER_DEFAULT =='Y'?true:false );
    echo '<td colspan="3">';
    echo '<input type="hidden" name="rem_action" value="' . 
      ( ! empty ( $reminder['action'] )? $reminder['action']: 'EMAIL' ) . '" />' ."\n";
    echo '<input type="hidden" name="rem_last_sent" value="' . 
      ( ! empty ( $reminder['last_sent'] )? $reminder['last_sent']: 0 ) . '" />' ."\n";
    echo '<input type="hidden" name="rem_times_sent" value="' . 
      ( ! empty ( $reminder['times_sent'] )? $reminder['times_sent']: 0 ) . '" />' ."\n";
    echo '<label><input type="radio" name="reminder" id="reminderYes" value="1"';

    if ( $rem_status )
      echo $checked;
    echo ' onclick="toggle_reminders()" />' ."\n";
    echo translate ( 'Yes' ) . '</label>&nbsp;<label>';
    echo '<input type="radio" name="reminder" id="reminderNo" value="0"';
    if ( ! $rem_status )
      echo $checked;
    echo ' onclick="toggle_reminders()" />' . translate ( 'No' ) . 
      '</label></td></tr></thead>' ."\n";
    $rem_use_date = ( ! empty ( $reminder['date'] ) || 
      ( $reminder_offset == 0 && $REMINDER_WITH_DATE == 'Y' )? true:false);
    ?> 
    <tbody id="reminder_when"><tr>
    <td class="tooltip" rowspan="6"><label><?php etranslate( 'When' ); ?>:</label></td>
    <td class="boxtop boxleft" width="20%"><label>
     <input  type="radio" name="rem_when" id="rem_when_date" value="Y" <?php 
     if ( $rem_use_date )
       echo  $checked; 
 ?>  onclick="toggle_rem_when()" /><?php etranslate ( 'Use Date/Time' ); ?>&nbsp;</label>
    </td><td class="boxtop boxright" nowrap="nowrap" colspan="2"> 
    <?php 
      echo date_selection ( 'reminder_', ( ! empty ( $reminder['date'] ) ?
          $reminder['date'] : $cal_date )  );
      ?>
     </td></tr>
     <tr><td class="boxleft">&nbsp;</td><td class="boxright"  colspan="2" nowrap="nowrap">
    <?php
      echo time_selection ( 'reminder_', ( ! empty ( $reminder['time'] ) ? 
        $reminder['time'] : $cal_time ) );
    ?>  
     </td></tr>
     <tr><td class="boxleft boxright"  height="20px" colspan="3">&nbsp;</td></tr>  
     <tr>
      <td class="boxleft"><label>
     <input  type="radio" name="rem_when" id="rem_when_offset" value="N" <?php 
     if ( ! $rem_use_date )
       echo $checked;  
 ?>  onclick="toggle_rem_when()" /><?php etranslate ( 'Use Offset' ); ?>&nbsp;</label>
    </td><td class="boxright" nowrap="nowrap" colspan="2">
    <?php
        $rem_minutes = $reminder_offset;
      // will be specified in total minutes
      $rem_days = (int) ( $rem_minutes / ( 24 * 60 ) );
      $rem_minutes -= ( $rem_days * 24 * 60 );
      $rem_hours = (int) ( $rem_minutes / 60 );
      $rem_minutes -= ( $rem_hours * 60 );
    
      echo '<label><input type="text" size="2" name="rem_days" '.
        "value=\"$rem_days\" /> " .  translate( 'days' ) . "</label>&nbsp;\n";
      echo '<label><input type="text" size="2" name="rem_hours" ' .
        "value=\"$rem_hours\" /> " .  translate( 'hours' ) . "</label>&nbsp;\n";
      echo '<label><input type="text" size="2" name="rem_minutes" ' .
        "value=\"$rem_minutes\" /> " .  translate( 'minutes' ) . "</label>";
      echo "</td></tr>\n";
      echo '<tr>';    
    $rem_before = ( empty ( $reminder['before'] ) || 
      $reminder['before'] == 'Y' ?true:false );
    echo '<td class="boxleft">&nbsp;</td>' . "\n" . '<td>';
    echo '<label><input type="radio" name="rem_before" id="rem_beforeY" value="Y"';

    if ( $rem_before )
      echo $checked;
    echo " />" . translate ( 'Before' );
    echo "</label>&nbsp;</td>\n<td class=\"boxright\">";
    echo '<label><input type="radio" name="rem_before" id="rem_beforeN" value="N"';
    if ( ! $rem_before )
      echo $checked;
    echo ' />' . translate ( 'After' ) ."</label></td></tr>\n";

      echo '<tr>'; 
    $rem_related = ( empty ( $reminder['related'] ) || 
      $reminder['related'] == 'S' ?true:false );
    echo '<td class="boxleft boxbottom">&nbsp;</td>' . "\n" . '<td class="boxbottom">';
    echo '<label><input type="radio" name="rem_related" id="rem_relatedS" value="S"';

    if ( $rem_related )
      echo $checked;
    echo ' />' . translate ( 'Start' );
    echo '</label>&nbsp;</td>' . "\n" . '<td  class="boxbottom boxright">';
    echo '<label><input type="radio" name="rem_related" id="rem_relatedE" value="E"';
    if ( ! $rem_related )
      echo $checked;
    echo ' />' . translate ( 'End/Due' ) ."</label></td></tr>\n";    
    echo '<tr><td colspan="4"></td></tr></tbody>' . "\n";
    //Reminder Repeats
      if ( isset (  $reminder['repeats'] ) )
        $rem_rep_count = $reminder['repeats'];
      else
        $rem_rep_count = 0;      
      if ( isset (  $reminder['duration'] ) )
        $rem_rep_minutes = $reminder['duration'];
      else
        $rem_rep_minutes = 0;
      // will be specified in total minutes
      $rem_rep_days = (int) ( $rem_rep_minutes / ( 24 * 60 ) );
      $rem_rep_minutes -= ( $rem_rep_days * 24 * 60 );
      $rem_rep_hours = (int) ( $rem_rep_minutes / 60 );
      $rem_rep_minutes -= ( $rem_rep_hours * 60 );
    echo '<tbody  id="reminder_repeat"><tr>';
    echo '<td class="tooltip" rowspan="2"><label>' .translate( 'Repeat' ) . ":</label></td>\n";
    echo '<td class="boxleft boxtop">';
    echo '&nbsp;&nbsp;&nbsp;<label>' . translate( 'Times' ) . '</label></td>' ."\n";
    echo '<td class="boxright boxtop" colspan="2">';
    echo '<input type="text" size="2" name="rem_rep_count" '.
      "value=\"$rem_rep_count\" onchange=\"toggle_rem_rep();\" /></td></tr>\n";
    echo '<tr id="rem_repeats"><td class="boxleft boxbottom">';
    echo '&nbsp;&nbsp;&nbsp;<label>' . translate ( 'Every' ) . '</label></td>' ."\n";
    echo '<td class="boxright boxbottom" colspan="2">';
    echo '<label><input type="text" size="2" name="rem_rep_days" '.
      "value=\"$rem_rep_days\" /> " .  translate( 'days' ) . "</label>&nbsp;\n";
    echo '<input type="text" size="2" name="rem_rep_hours" ' .
      "value=\"$rem_rep_hours\" /><label> " .  translate( 'hours' ) . "</label>&nbsp;\n";
    echo '<input type="text" size="2" name="rem_rep_minutes" ' .
      "value=\"$rem_rep_minutes\" /><label> " .  translate( 'minutes' ) .'</label>';
    echo "</td></tr></tbody>\n";
    
        
    echo "</table>\n";
      
if ( $useTabs ) { ?>
</div> <!-- End tabscontent_pete -->
<?php } else { ?>
</fieldset>
<?php } /* $useTabs */ 
} ?>
</div> <!-- End tabscontent -->
<table>
<tr><td>
 <script type="text/javascript">
<!-- <![CDATA[
  document.writeln ( '<input type="button" value="<?php etranslate( 'Save' )?>" onclick="validate_and_submit()" />' );
//]]> -->
 </script>
 <noscript>
  <input type="submit" value="<?php etranslate( 'Save' )?>" />
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

<?php if ( $id > 0 && ( $login == $create_by || $single_user == 'Y' || $is_admin ) ) { ?>
 <a href="del_entry.php?id=<?php echo $id;?>" onclick="return confirm('<?php 
  etranslate( 'Are you sure you want to delete this entry?', true)?>');"><?php 
  etranslate( 'Delete entry')?></a><br />
<?php 
 } //end if clause for delete link
} else { 
  echo translate( 'You are not authorized to edit this entry' ) . '.';
} //end if ( $can_edit )

echo print_trailer(); ?>


