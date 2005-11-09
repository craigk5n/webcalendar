<?php
/*
 * $Id: 
 *
 * Description:
 * Presents page to edit/add a task
 *
 * Notes:
 * If htmlarea is installed, users can use WYSIWYG editing.
 * SysAdmin must enable HTML for task full descriptions.
 * The htmlarea files should be installed so that the htmlarea.php
 * file is in ../includes/htmlarea/htmlarea.php
 * The htmlarea code can be downloaded from:
 *  http://www.htmlarea.com
 * TODO
 * This file will not pass XHTML validation with HTMLArea enabled
 */
include_once 'includes/init.php';

load_user_categories ();

// Default for using tabs is enabled
if ( empty ( $EVENT_EDIT_TABS ) )
  $EVENT_EDIT_TABS = 'Y'; // default
$useTabs = ( $EVENT_EDIT_TABS == 'Y' );

//TODO Implement repeats
$DISABLE_REPEATING_FIELD = true;

// make sure this is not a read-only calendar
$can_edit = false;

// Public access has no access to tasks
if ( $login == "__public__" ) {
  echo translate("You are not authorized to edit this task") . ".";
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
  // first see who has access to edit this task
  if ( $is_admin ) {
    $can_edit = true;
  } else {
    $can_edit = false;
    if ( $readonly == "N" || $is_admin ) {
      $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
        "webcal_entry_user WHERE webcal_entry.cal_id = " .
        "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
        "AND (webcal_entry.cal_create_by = '$login')";
      $res = dbi_query ( $sql );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row && $row[0] > 0 )
          $can_edit = true;
        dbi_free_result ( $res );
      }
    }
  }
  $sql = "SELECT cal_create_by, cal_date, cal_time,  " .
    "cal_due_date, cal_due_time,  cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description,  cal_completed, cal_location, " .
    "cal_url FROM webcal_entry WHERE cal_id = " . $id;
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $create_by = $row[0];
    if ( $user == $create_by  ||  $is_assistant ) $can_edit = true;
  
    if ( ! empty ( $override ) && ! empty ( $date ) ) {
      // Leave $cal_date to what was set in URL with date=YYYYMMDD
      $cal_date = $date;
    } else {
      $cal_date = $row[1];
    }
   
    $cal_time = $row[2];
    $due_date = $row[3];
    $due_time = $row[4];    

    $adjusted_start = get_datetime_add_tz ( $cal_date, $cal_time );
    $adjusted_due = get_datetime_add_tz ( $due_date, $due_time );
 
    $cal_date = date ( "Ymd",$adjusted_start );
    $cal_time = date (  "His", $adjusted_start );
    $cal_hour = floor($cal_time / 10000);
    $cal_minute = ( $cal_time / 100 ) % 100;
  
    $due_date = date ( "Ymd",$adjusted_due );
    $due_time = date (  "His", $adjusted_due );
    $due_hour = floor($due_time / 10000);
    $due_minute = ( $due_time / 100 ) % 100;
   
    $priority = $row[5];
    $type = $row[6];
    $access = $row[7];
    $name = $row[8];
    $description = $row[9];
    $completed = ( ! empty ( $row[10] )? $row[10] : date ( "Ymd"));
    $location = $row[11];
    $task_url = $row[12];    
    // check for repeating event info...
    // but not if we are overriding a single task of an already repeating
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
  }
  $sql = "SELECT cal_login,  cal_percent, cal_status " .
   " FROM webcal_entry_user WHERE cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $overall_percent[] = $row;
   if ($login == $row[0]) $task_percent = $row[1];
   if ( $is_admin && $user == $row[0]) $task_percent = $row[1];
   
   if ($login == $row[0]) $task_status = $row[2];
   if ( $is_admin && $user == $row[0]) $task_status = $row[2];
  }  
   dbi_free_result ( $res );  
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
     $cat_name[] = $row[1];    
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
      if ( $login == $user || $is_assistant  || $is_admin ) {
     $cat_id[] = $row[1];
     $cat_name[] = $row[3];    
   }
    }

  if ( ! empty ( $cat_name ) ) $catNames = implode("," , array_unique($cat_name));
    if ( ! empty ( $cat_id ) ) $catList = implode(",", array_unique($cat_id));

    dbi_free_result ( $res );
 }
 
   //get participants
  $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = $id AND " .
    " cal_status IN ('A', 'W' )";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $participants[$row[0]] = 1;
    }
    dbi_free_result ( $res );
  }
 // I don't think we should do external users. Any thoughts?
  //if ( ! empty ( $ALLOW_EXTERNAL_USERS ) && $ALLOW_EXTERNAL_USERS == "Y" ) {
  //  $external_users = event_get_external_users ( $id );
  //}
} else {
  // New task.
  $id = 0; // to avoid warnings below about use of undefined var
 //We'll use $WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR
 // As our starting and due times
 $cal_time = $WORK_DAY_START_HOUR . "0000";
         $cal_hour = $WORK_DAY_START_HOUR;
 $cal_minute = 0;
 $due_time = $WORK_DAY_END_HOUR . "0000";
 $due_hour = $WORK_DAY_END_HOUR;
 $due_minute = 0;
 $task_percent = 0;
 $completed = '';
 $overall_percent =  array();
 
  if ( ! empty ( $defusers ) ) {
    $tmp_ar = explode ( ",", $defusers );
    for ( $i = 0; $i < count ( $tmp_ar ); $i++ ) {
      $participants[$tmp_ar[$i]] = 1;
    }
  }
  if ( $readonly == "N" ) {
    $can_edit = true;
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
if ( empty ( $description ) )
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
  $cal_date =  $date;
  else
    $cal_date = date ( "Ymd" );
  if ( empty ( $due_date ) )
    $due_date = date ( "Ymd" );
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
if ( empty ( $due_date ) || ! $due_date )
  $due_date = $thisdate;

if ( $ALLOW_HTML_DESCRIPTION == "Y" ){
  // Allow HTML in description
  // If they have installed the htmlarea widget, make use of it
  $textareasize = 'rows="15" cols="50"';
  if ( $use_fckeditor ) {
    $textareasize = 'rows="20" cols="50"';
    $BodyX = '';
    $INC = array ( 'js/edit_task.php', 'js/visible.php' );
  } else if ( $use_htmlarea ) {
    $BodyX = 'onload="initEditor();"';
    $INC = array ( 'htmlarea/htmlarea.php', 'js/edit_task.php',
      'js/visible.php', 'htmlarea/core.php' );
  } else {
    // No htmlarea files found...
    $BodyX = '';
    $INC = array ( 'js/edit_task.php', 'js/visible.php' );
  }
} else {
  $textareasize = 'rows="5" cols="40"';
  $BodyX = '';
  $INC = array('js/edit_task.php','js/visible.php');
}
print_header ( $INC, '', $BodyX );
?>


<h2><?php if ( $id ) echo translate("Edit Task"); else echo translate("Add Task"); ?>&nbsp;<img src="help.gif" alt="<?php etranslate("Help")?>" class="help" onclick="window.open ( 'help_edit_task.php<?php if ( empty ( $id ) ) echo "?add=1"; ?>', 'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<?php
 if ( $can_edit ) {
?>
<form action="edit_task_handler.php" method="post" name="edittaskform">

<?php
if ( ! empty ( $id ) && ( empty ( $copy ) || $copy != '1' ) ) echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
// we need an additional hidden input field
echo "<input type=\"hidden\" name=\"task_changed\" value=\"\" />\n";

// are we overriding an task from a repeating event...
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
  <table  border="0">
   <tr><td style="width:14%;" class="tooltip" title="<?php etooltip("brief-description-help")?>">
    <label for="task_brief"><?php etranslate("Brief Description")?>:</label></td><td>
    <input type="text" name="name" id="task_brief" size="25" value="<?php 
     echo htmlspecialchars ( $name );
    ?>" /></td></tr>
   <tr><td style="vertical-align:top;" class="tooltip" title="<?php etooltip("full-description-help")?>">
    <label for="task_full"><?php etranslate("Full Description")?>:</label></td><td>
    <textarea name="description" id="task_full" <?php
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
       <label for="task_access"><?php etranslate("Access")?>:</label></td><td>
       <select name="access" id="task_access">
        <option value="P"<?php if ( $access == "P" || ! strlen ( $access ) ) echo " selected=\"selected\"";?>><?php etranslate("Public")?></option>
        <option value="R"<?php if ( $access == "R" ) echo " selected=\"selected\"";?>><?php etranslate("Confidential")?></option>
       </select>
       </td></tr>
<?php } ?>
<?php if ( $DISABLE_PRIORITY_FIELD != "Y" ) { ?>
     <tr><td class="tooltip" title="<?php etooltip("priority-help")?>">
      <label for="task_prio"><?php etranslate("Priority")?>:&nbsp;</label></td><td>
      <select name="priority" id="task_prio">
       <option value="1"<?php if ( $priority == 1 ) echo " selected=\"selected\"";?>><?php etranslate("Low")?></option>
       <option value="2"<?php if ( $priority == 2 || $priority == 0 ) echo " selected=\"selected\"";?>><?php etranslate("Medium")?></option>
       <option value="3"<?php if ( $priority == 3 ) echo " selected=\"selected\"";?>><?php etranslate("High")?></option>
      </select>
     </td></tr>
   
<?php } ?>
<?php if ( ! empty ( $categories ) ) { ?>
     <tr><td class="tooltip" title="<?php etooltip("category-help")?>" valign="top">
      <label for="task_categories"><?php etranslate("Category")?>:<br /></label>
   <input type="button" value="Edit" onclick="editCats(event)" /></td><td valign="top">
      <input  readonly=""type="text" name="catnames" 
     value="<?php echo $catNames ?>"  size="50" 
    onclick="alert('<?php etranslate("Use the Edit button to make changes.", true) ?>')"/>
   <input  type="hidden" name="cat_id" id="task_categories" value="<?php echo $catList ?>" />
     </td></tr>
<?php } //end if (! empty ($categories)) ?>
<?php if (( ! empty ( $categories ) ) || ( $DISABLE_ACCESS_FIELD != "Y" ) || 
         ( $DISABLE_PRIORITY_FIELD != "Y" ) ){ // end the table ?>
   </table>
    
<?php } ?>
  <table border="0"><tr><td class="tooltip" title="<?php etooltip("percent-help")?>">
      <label for="task_prio"><?php etranslate("Percent Complete")?>:&nbsp;</label></td><td>
      <select name="percent" id="task_percent">
   <?php  
     for ( $i=0; $i<=100 ; $i+=10 ){ 
          echo "<option value=\"$i\" " .  ($task_percent == $i? " selected=\"selected\"":""). " >" .  $i . "</option>\n";
        }
    echo "</select></td></tr>\n";
    if ( ! empty ( $overall_percent ) ) {
      echo "<tr><td colspan=\"2\">\n<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing\"5\">".
       "<tr>\n<td colspan=\"2\">All Percentages</td><tr>";
      $all_complete = true;
      for ( $i = 0; $i < count ( $overall_percent ); $i++ ) {
            user_load_variables ( $overall_percent[$i][0], "percent" );
          echo "<tr><td>" . $percentfullname . "</td><td>" . $overall_percent[$i][1] . "</td></tr>\n";
         if ( $overall_percent[$i][1] < 100 ) $all_complete = false;
       }
      echo "</table>";
    }
    echo "</td></tr>\n";
   ?>
   </td></tr></table>

  </td></tr>
<?php if ( $DISABLE_LOCATION_FIELD != "Y"  ){  ?>
 <tr><td class="tooltip" title="<?php etooltip("location-help")?>">
   <?php etranslate("Location")?>:</td><td colspan="2">
    <input type="text" name="location" size="55" 
   value="<?php echo htmlspecialchars ( $location ); ?>" />
  </td></tr>
<?php } ?>  
 <tr>
 <?php if ( ! empty ( $all_complete ) ) { ?>
     <td class="tooltip" title="<?php etooltip("date-help")?>" colspan="3" align="right">
     <?php etranslate("Completed Date")?>:&nbsp;
     <?php
       print_date_selection ( "complete_", $completed );
     ?></td>
<?php } ?> 
 </tr>
  <tr><td class="tooltip" title="<?php etooltip("date-help")?>">
   <?php etranslate("Start Date")?>:</td><td colspan="2">
   <?php
    print_date_selection ( "start_", $cal_date );
   ?>
  </td></tr>
  <tr><td class="tooltip" title="<?php etooltip("time-help")?>">
   <?php echo translate("Start Time") . ":"; ?></td><td colspan="2">
<?php
$h12 = $cal_hour;
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
if ( $cal_time < 0 ) $h12 = "";
?>
   <input type="text" name="cal_hour" size="2" value="<?php 
    if ( $cal_time >= 0 ) echo $h12;
   ?>" maxlength="2" />:<input type="text" name="cal_minute" size="2" value="<?php 
    if ( $cal_time >= 0 ) printf ( "%02d", $cal_minute );
   ?>" maxlength="2" />
<?php
if ( $TIME_FORMAT == "12" ) {
  echo "<label><input type=\"radio\" name=\"ampm\" value=\"am\" $amsel />&nbsp;" .
    translate("am") . "</label>\n";
  echo "<label><input type=\"radio\" name=\"ampm\" value=\"pm\" $pmsel />&nbsp;" .
    translate("pm") . "</label>\n";
}
?>
</td></tr>
<tr><td colspan="3">&nbsp;</td></tr>
<tr><td class="tooltip" title="<?php etooltip("date-help")?>">
   <?php etranslate("Due Date")?>:</td><td colspan="2">
   <?php 
    print_date_selection ( "due_", $due_date );
   ?>
  </td></tr>
  <tr><td class="tooltip" title="<?php etooltip("time-help")?>">
   <?php echo translate("Due Time") . ":"; ?></td><td colspan="2">
<?php
$dh12 = $due_hour;
$damsel = " checked=\"checked\""; $dpmsel = "";
if ( $TIME_FORMAT == "12" ) {
  if ( $dh12 < 12 ) {
    $damsel = " checked=\"checked\""; $dpmsel = "";
  } else {
    $damsel = ""; $dpmsel = " checked=\"checked\"";
  }
  $dh12 %= 12;
  if ( $dh12 == 0 ) $dh12 = 12;
}
if ( $due_time < 0 ) $dh12 = "";
?>
   <input type="text" name="due_hour" size="2" value="<?php 
    if ( $due_time >= 0 ) echo $dh12;
   ?>" maxlength="2" />:<input type="text" name="due_minute" size="2" value="<?php 
    if ( $due_time >= 0 ) printf ( "%02d", $due_minute );
   ?>" maxlength="2" />
<?php
if ( $TIME_FORMAT == "12" ) {
  echo "<label><input type=\"radio\" name=\"dampm\" value=\"am\" $damsel />&nbsp;" .
    translate("am") . "</label>\n";
  echo "<label><input type=\"radio\" name=\"dampm\" value=\"pm\" $dpmsel />&nbsp;" .
    translate("pm") . "</label>\n";
}
?>
</td></tr>
</table>
<table>
<?php
// site-specific extra fields (see site_extras.php)
// load any site-specific fields and display them
if ( $id > 0 )
  $extras = get_site_extra_fields ( $id );
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
        "_minutes\" value=\"$minutes\" /> " .  translate("minutes") . "&nbsp;" . translate("before task is due") . "</label>";
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
    }
    $users .= ">" . $userlist[$i]['cal_fullname'] . "</option>\n";
  }

  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  print "<tr title=\"" . 
 tooltip("participants-help") . "\"><td class=\"tooltipselect\">\n<label for=\"task_part\">" . 
 translate("Participants") . ":</label></td><td>\n";
  print "<select name=\"participants[]\" id=\"task_part\" size=\"$size\" multiple=\"multiple\">$users\n";
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
      tooltip("external-participants-help") . "\"><td style=\"vertical-align:top;\" class=\"tooltip\">\n<label for=\"task_extpart\">" .
      translate("External Participants") . ":</label></td><td>\n";
    print "<textarea name=\"externalparticipants\" id=\"task_extpart\" rows=\"5\" cols=\"40\">";
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
<!-- Repeat is on the TODO list -->
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
 <a href="del_task.php?id=<?php echo $id;?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this task?")?>');"><?php etranslate("Delete task")?></a><br />
<?php 
 } //end if clause for delete link
} else { 
  echo translate("You are not authorized to edit this task") . ".";
} //end if ( $can_edit )
?>

<?php print_trailer(); ?>
</body>
</html>
