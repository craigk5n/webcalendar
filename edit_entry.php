<?php
include_once 'includes/init.php';
include_once 'includes/site_extras.php';
load_user_categories ();

// make sure this is not a read-only calendar
$can_edit = false;

// Public access can only add events, not edit.
if ( $login == "__public__" && $id > 0 ) {
  $id = 0;
}

$external_users = "";
$participants = array ();

if ( ! empty ( $id ) && $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin || $is_assistant || $is_nonuser_admin) {
    $can_edit = true;
  } else {
    $can_edit = false;
    if ( $readonly == "N" || $is_admin ) {
      $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
        "webcal_entry_user WHERE webcal_entry.cal_id = " .
        "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
        "AND (webcal_entry.cal_create_by = '$login' " .
        "OR webcal_entry_user.cal_login = '$login')";
      $res = dbi_query ( $sql );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row && $row[0] > 0 )
          $can_edit = true;
        dbi_free_result ( $res );
      }
    }
  }
  $sql = "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
    "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
    "cal_name, cal_description FROM webcal_entry WHERE cal_id = " . $id;
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( ! empty ( $override ) ) {
      // Leave $cal_date to what was set in URL with date=YYYYMMDD
    } else {
      $cal_date = $row[1];
    }
    $create_by = $row[0];
    $year = (int) ( $cal_date / 10000 );
    $month = ( $cal_date / 100 ) % 100;
    $day = $cal_date % 100;
    $time = $row[2];
    if ( $time >= 0 ) { /* -1 = no time specified */
      $time += $TZ_OFFSET * 10000;
      if ( $time > 240000 ) {
        $time -= 240000;
        $gmt = mktime ( 3, 0, 0, $month, $day, $year );
        $gmt += $ONE_DAY;
        $month = date ( "m", $gmt );
        $day = date ( "d", $gmt );
        $year = date ( "Y", $gmt );
      } else if ( $time < 0 ) {
        $time += 240000;
        $gmt = mktime ( 3, 0, 0, $month, $day, $year );
	$gmt -= $ONE_DAY;
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
    }
    $priority = $row[6];
    $type = $row[7];
    $access = $row[8];
    $name = $row[9];
    $description = $row[10];
    // check for repeating event info...
    // but not if we are overriding a single entry of an already repeating
    // event... confusing, eh?
    if ( ! empty ( $override ) ) {
      $rpt_type = "none";
      $rpt_end = 0;
      $rpt_end_date = $cal_date;
      $rpt_freq = 1;
      $rpt_days = "nnnnnnn";
      $rpt_sun = $rpt_mon = $rpt_tue = $rpt_wed =
        $rpt_thu = $rpt_fri = $rpt_sat = false;
    } else {
      $res = dbi_query ( "SELECT cal_id, cal_type, cal_end, " .
        "cal_frequency, cal_days FROM webcal_entry_repeats " .
        "WHERE cal_id = $id" );
      if ( $res ) {
        if ( $row = dbi_fetch_row ( $res ) ) {
          $rpt_type = $row[1];
          if ( $row[2] > 0 )
            $rpt_end = date_to_epoch ( $row[2] );
          else
            $rpt_end = 0;
          $rpt_end_date = $row[2];
          $rpt_freq = $row[3];
          $rpt_days = $row[4];
          $rpt_sun  = ( substr ( $rpt_days, 0, 1 ) == 'y' );
          $rpt_mon  = ( substr ( $rpt_days, 1, 1 ) == 'y' );
          $rpt_tue  = ( substr ( $rpt_days, 2, 1 ) == 'y' );
          $rpt_wed  = ( substr ( $rpt_days, 3, 1 ) == 'y' );
          $rpt_thu  = ( substr ( $rpt_days, 4, 1 ) == 'y' );
          $rpt_fri  = ( substr ( $rpt_days, 5, 1 ) == 'y' );
          $rpt_sat  = ( substr ( $rpt_days, 6, 1 ) == 'y' );
        }
      }
    }
    
  }
  $sql = "SELECT cal_login, cal_category FROM webcal_entry_user WHERE cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( ! $is_secretary || $login != $row[0] ) $participants[$row[0]] = 1;
      if ($login == $row[0]) $cat_id = $row[1];
    }
  }
  if ( ! empty ( $allow_external_users ) && $allow_external_users == "Y" ) {
    $external_users = event_get_external_users ( $id );
  }
} else {
  $id = 0; // to avoid warnings below about use of undefined var
  if ( empty ( $hour ) )
    $time = -1;
  else
    $time = $hour * 100;
  if ( $readonly == "N" || $is_admin )
    $can_edit = true;
  if ( ! empty ( $defusers ) ) {
    $tmp_ar = explode ( ",", $defusers );
    for ( $i = 0; $i < count ( $tmp_ar ); $i++ ) {
      $participants[$tmp_ar[$i]] = 1;
    }
  }
}
if ( ! empty ( $year ) && $year )
  $thisyear = $year;
if ( ! empty ( $month ) && $month )
  $thismonth = $month;
if ( ! empty ( $day ) && $day )
  $thisday = $day;
if ( empty ( $rpt_type ) || ! $rpt_type )
  $rpt_type = "none";

// avoid error for using undefined vars
if ( empty ( $hour ) )
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
if ( empty ( $priority ) )
  $priority = 0;
if ( empty ( $access ) )
  $access = "";
if ( empty ( $rpt_freq ) )
  $rpt_freq = 0;
if ( empty ( $rpt_end_date ) )
  $rpt_end_date = 0;

if ( ( empty ( $year ) || ! $year ) &&
  ( empty ( $month ) || ! $month ) &&
  ( ! empty ( $date ) && strlen ( $date ) ) ) {
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
  $cal_date = $date;
} else {
  if ( empty ( $cal_date ) )
    $cal_date = date ( "Ymd" );
}
$thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );
if ( empty ( $cal_date ) || ! $cal_date )
  $cal_date = $thisdate;

$BodyX = 'xonload="timetype_handler()"';
$INC = array('js/popups.php','js/edit_entry.php');
print_header($INC,'',$BodyX);
?>

<h2 style="color:<?php echo $H2COLOR;?>;"><?php if ( $id ) echo translate("Edit Entry"); else echo translate("Add Entry"); ?></h2>

<?php
if ( $can_edit ) {
?>
<form action="edit_entry_handler.php" method="post" name="editentryform">

<?php
if ( ! empty ( $id ) ) echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
// we need an additional hidden input field
echo "<input type=\"hidden\" name=\"entry_changed\" value=\"\" />\n";

// are we overriding an entry from a repeating event...
if ( $override ) {
  echo "<input type=\"hidden\" name=\"override\" value=\"1\" />\n";
  echo "<input type=\"hidden\" name=\"override_date\" value=\"$cal_date\" />\n";
}
// if assistant, need to remember boss = user
if ( $is_assistant || $is_nonuser_admin )
   echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";

?>

<table style="border-width:0px;">
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("brief-description-help")?>"><?php etranslate("Brief Description")?>:</td>
  <td><input name="name" size="25" value="<?php echo htmlspecialchars ( $name ); ?>" /></td></tr>

<tr><td style="font-weight:bold; vertical-align:top;" class="tooltip" title="<?php etooltip("full-description-help")?>"><?php etranslate("Full Description")?>:</td>
  <td><textarea name="description" rows="5" cols="40" wrap="virtual"><?php echo htmlspecialchars ( $description ); ?></textarea></td></tr>

<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("date-help")?>"><?php etranslate("Date")?>:</td>
  <td>
  <?php
  print_date_selection ( "", $cal_date )
  ?>
</td></tr>

<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("time-help")?>"><?php etranslate("Time")?>:</td>
<?php

$h12 = $hour;
$amsel = "CHECKED"; $pmsel = "";
if ( $TIME_FORMAT == "12" ) {
  if ( $h12 < 12 ) {
    $amsel = "CHECKED"; $pmsel = "";
  } else {
    $amsel = ""; $pmsel = "CHECKED";
  }
  $h12 %= 12;
  if ( $h12 == 0 ) $h12 = 12;
}
if ( $time < 0 )
  $h12 = "";
?>
  <td>
<span id="timeentry">
<input name="hour" size="2" value="<?php if ( $allday != "Y" ) echo $h12;?>" maxlength="2" />:<input name="minute" size="2" value="<?php if ( $time >= 0 && $allday != "Y" ) printf ( "%02d", $minute );?>" maxlength="2" />
<?php
if ( $TIME_FORMAT == "12" ) {
  echo "<input type=\"radio\" name=\"ampm\" value=\"am\" $amsel>" .
    translate("am") . "\n";
  echo "<input type=\"radio\" name=\"ampm\" value=\"pm\" $pmsel>" .
    translate("pm") . "\n";
}
?>
</span>
&nbsp;&nbsp;&nbsp;&nbsp;
<select name="timetype" onchange="timetype_handler()">
<option value="U" <?php if ( $allday != "Y" && $hour == -1 ) echo " selected=\"selected\""?>><?php etranslate("Untimed event"); ?></option>
<option value="T" <?php if ( $allday != "Y" && $hour >= 0 ) echo " selected=\"selected\""?>><?php etranslate("Timed event"); ?></option>
<option value="A" <?php if ( $allday == "Y" ) echo " selected=\"selected\""?>><?php etranslate("All day event"); ?></option>
</select>
</td></tr>

<?php
  $dur_h = (int)( $duration / 60 );
  $dur_m = $duration - ( $dur_h * 60 );
?>
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("duration-help")?>"><?php etranslate("Duration")?>:</td>
  <td><input name="duration_h" size="2" maxlength="2" value="<?php if ( $allday != "Y" ) printf ( "%d", $dur_h );?>" />:<input name="duration_m" size="2" maxlength="2" value="<?php if ( $allday != "Y" ) printf ( "%02d", $dur_m );?>" /> (<?php echo translate("hours") . ":" . translate("minutes")?>)</td></tr>

<?php if ( $disable_priority_field != "Y" ) { ?>
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("priority-help")?>"><?php etranslate("Priority")?>:</td>
  <td><select name="priority">
    <option value="1"<?php if ( $priority == 1 ) echo " selected=\"selected\"";?>><?php etranslate("Low")?></option>
    <option value="2"<?php if ( $priority == 2 || $priority == 0 ) echo " selected=\"selected\"";?>><?php etranslate("Medium")?></option>
    <option value="3"<?php if ( $priority == 3 ) echo " selected=\"selected\"";?>><?php etranslate("High")?></option>
  </select></td></tr>
<?php } ?>

<?php if ( $disable_access_field != "Y" ) { ?>
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("access-help")?>"><?php etranslate("Access")?>:</td>
  <td><select name="access">
    <option value="P"<?php if ( $access == "P" || ! strlen ( $access ) ) echo " selected=\"selected\"";?>><?php etranslate("Public")?></option>
    <option value="R"<?php if ( $access == "R" ) echo " selected=\"selected\"";?>><?php etranslate("Confidential")?></option>
  </select></td></tr>
<?php } ?>

<?php if ( ! empty ( $categories ) ) { ?>
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("category-help")?>"><?php etranslate("Category")?>:</td>
  <td><select name="cat_id">
  <option value=""> <?php etranslate("None")?>
<?php
  foreach( $categories as $K => $V ){
    echo "<option value=\"$K\"";
    if ( $cat_id == $K ) echo " selected=\"selected\"";
    echo ">$V</option>";
  }
?>
  </select></td></tr>
<?php } ?>

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
  if ( $extra_type == $EXTRA_MULTILINETEXT )
    echo "<tr><td VALIGN=\"top\"><br />";
  else
    echo "<tr><td>";
  echo "<B>" .  translate ( $extra_descr ) .  ":</B></td><td>";
  if ( $extra_type == $EXTRA_URL ) {
    echo '<input size="50" name="' . $extra_name .
      '" value="' .
      ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) .
      '">';
  } else if ( $extra_type == $EXTRA_EMAIL ) {
    echo '<input size="30" name="' . $extra_name .
      '" value="' .
      ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) .
      '">';
  } else if ( $extra_type == $EXTRA_DATE ) {
    if ( ! empty ( $extras[$extra_name]['cal_date'] ) )
      print_date_selection ( $extra_name, $extras[$extra_name]['cal_date'] );
    else
      print_date_selection ( $extra_name, $cal_date );
  } else if ( $extra_type == $EXTRA_TEXT ) {
    $size = ( $extra_arg1 > 0 ? $extra_arg1 : 50 );
    echo '<input size="' . $size . '" name="' . $extra_name .
      '" value="' .
      ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) .
      '">';
  } else if ( $extra_type == $EXTRA_MULTILINETEXT ) {
    $cols = ( $extra_arg1 > 0 ? $extra_arg1 : 50 );
    $rows = ( $extra_arg2 > 0 ? $extra_arg2 : 5 );
    echo '<textarea rows="' . $rows . '" cols="' . $cols .
      '" name="' . $extra_name .  '">' .
      ( empty ( $extras[$extra_name]['cal_data'] ) ?
      "" : htmlspecialchars ( $extras[$extra_name]['cal_data'] ) ) .
      '</textarea>';
  } else if ( $extra_type == $EXTRA_USER ) {
    // show list of calendar users...
    echo "<select name=\"" . $extra_name . "\">";
    echo "<option value=\"\">None</option>";
    $userlist = get_my_users ();
    for ( $j = 0; $j < count ( $userlist ); $j++ ) {
      echo "<option value=\"" . $userlist[$j]['cal_login'] . "\"";
        if ( ! empty ( $extras[$extra_name]['cal_data'] ) &&
          $userlist[$j]['cal_login'] == $extras[$extra_name]['cal_data'] )
          echo " selected=\"selected\"";
        echo ">" . $userlist[$j]['cal_fullname'];
    }
    echo "</select>";
  } else if ( $extra_type == $EXTRA_REMINDER ) {
    $rem_status = 0; // don't send
    echo "<input type=\"radio\" name=\"" . $extra_name . "\" value=\"1\"";
    if ( empty ( $id ) ) {
      // adding event... check default
      if ( ( $extra_arg2 & $EXTRA_REMINDER_DEFAULT_YES ) > 0 )
        $rem_status = 1;
    } else {
      // editing event... check status
      if ( ! empty ( $extras[$extra_name]['cal_remind'] ) )
        $rem_status = 1;
    }
    if ( $rem_status )
      echo " checked";
    echo "> ";
    etranslate ( "Yes" );
    echo "&nbsp;<input type=\"radio\" name=\"" . $extra_name . "\" value=\"0\"";
    if ( ! $rem_status )
      echo " checked";
    echo "> ";
    etranslate ( "No" );
    echo "&nbsp;&nbsp;";
    if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_DATE ) > 0 ) {
      if ( ! empty ( $extras[$extra_name]['cal_date'] ) &&
        $extras[$extra_name]['cal_date'] > 0 )
        print_date_selection ( $extra_name, $extras[$extra_name]['cal_date'] );
      else
        print_date_selection ( $extra_name, $cal_date );
    } else if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
      if ( ! empty ( $extras[$extra_name]['cal_data'] ) )
        $minutes = $extras[$extra_name]['cal_data'];
      else
        $minutes = $extra_arg1;
      // will be specified in total minutes
      $d = (int) ( $minutes / ( 24 * 60 ) );
      $minutes -= ( $d * 24 * 60 );
      $h = (int) ( $minutes / 60 );
      $minutes -= ( $h * 60 );
      echo "<input size=\"2\" name=\"" . $extra_name .
        "_days\" value=\"$d\"> " .  translate("days") . "&nbsp;&nbsp;";
      echo "<input size=\"2\" name=\"" . $extra_name .
        "_hours\" value=\"$h\"> " .  translate("hours") . "&nbsp;&nbsp;";
      echo "<input size=\"2\" name=\"" . $extra_name .
        "_minutes\" value=\"$minutes\"> " .  translate("minutes") .
        "&nbsp;&nbsp;";
      etranslate("before event");
    }
  } else if ( $extra_type == $EXTRA_SELECTLIST ) {
    // show custom select list.
    echo "<select name=\"" . $extra_name . "\">";
    if ( is_array ( $extra_arg1 ) ) {
      for ( $j = 0; $j < count ( $extra_arg1 ); $j++ ) {
        echo "<option";
        if ( ! empty ( $extras[$extra_name]['cal_data'] ) &&
          $extra_arg1[$j] == $extras[$extra_name]['cal_data'] )
          echo " selected=\"selected\"";
        echo ">" . $extra_arg1[$j] . "</option>\n";
      }
    }
    echo "</select>";
  }
  echo "</td></tr>\n";
}
// end site-specific extra fields
?>

<?php
// Only ask for participants if we are multi-user.
$show_participants = ( $disable_participants_field != "Y" );
if ( $is_admin )
  $show_participants = true;
if ( $login == "__public__" && $public_access_others != "Y" )
  $show_participants = false;

if ( $single_user == "N" && $show_participants ) {
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
  $num_users = 0;
  $size = 0;
  $users = "";
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    $l = $userlist[$i]['cal_login'];
    $size++;
    $users .= "<option value=\"" . $l . "\"";
    if ( $id > 0 ) {
      if ( ! empty ( $participants[$l] ) )
        $users .= " selected=\"selected\"";
    } else {
      if ( ! empty ( $defusers ) ) {
        // default selection of participants was in the URL
        if ( ! empty ( $participants[$l] ) )
          $users .= " selected=\"selected\"";
      } else {
        if ( ( $l == $login && ! $is_assistant  && ! $is_nonuser_admin ) || ( ! empty ( $user ) && $l == $user ) )
          $users .= " selected=\"selected\"";
      }
    }
    $users .= "> " . $userlist[$i]['cal_fullname'];
  }

  if ( $size > 50 )
    $size = 15;
  else if ( $size > 5 )
    $size = 5;
  print "<tr><td VALIGN=\"top\"><B class=\"tooltip\" title=\"" .
    tooltip("participants-help") . "\">" .
    translate("Participants") . ":</B></td>";
  print "<td><select name=\"participants[]\" size=\"$size\" multiple=\"multiple\">$users\n";
  print "</select>";
  if ( $groups_enabled == "Y" ) {
    echo "<input type=\"button\" onclick=\"selectUsers()\" value=\"" .
      translate("Select") . "...\" />";
  }
  print "</td></tr>\n";

  // external users
  if ( ! empty ( $allow_external_users ) && $allow_external_users == "Y" ) {
    print "<tr><td VALIGN=\"top\"><B class=\"tooltip\" title=\"" .
      tooltip("external-participants-help") . "\">" .
      translate("External Participants") . ":</B></td>";
    print "<td><textarea name=\"externalparticipants\" rows=\"5\" cols=\"40\">";
    print $external_users . "</textarea></td></tr>\n";
    print "</td></tr>\n";
  }
}
?>

<?php if ( $disable_repeating_field != "Y" ) { ?>
<tr><td VALIGN="top"><B class="tooltip" title="<?php etooltip("repeat-type-help")?>"><?php etranslate("Repeat Type")?>:</B></td>
<td VALIGN="top"><?php
echo "<input type=\"radio\" name=\"rpt_type\" value=\"none\" " .
  ( strcmp ( $rpt_type, 'none' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("None");
echo "<input type=\"radio\" name=\"rpt_type\" value=\"daily\" " .
  ( strcmp ( $rpt_type, 'daily' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("Daily");
echo "<input type=\"radio\" name=\"rpt_type\" value=\"weekly\" " .
  ( strcmp ( $rpt_type, 'weekly' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("Weekly");
echo "<input type=\"radio\" name=\"rpt_type\" value=\"monthlyByDay\" " .
  ( strcmp ( $rpt_type, 'monthlyByDay' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("Monthly") . " (" . translate("by day") . ")";
echo "<input type=\"radio\" name=\"rpt_type\" value=\"monthlyByDayR\" " .
  ( strcmp ( $rpt_type, 'monthlyByDayR' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("Monthly") . " (" . translate("by day (from end)") . ")";
echo "<input type=\"radio\" name=\"rpt_type\" value=\"monthlyByDate\" " .
  ( strcmp ( $rpt_type, 'monthlyByDate' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("Monthly") . " (" . translate("by date") . ")";
echo "<input type=\"radio\" name=\"rpt_type\" value=\"yearly\" " .
  ( strcmp ( $rpt_type, 'yearly' ) == 0 ? "CHECKED" : "" ) . ">" .
  translate("Yearly");
?>
</td></tr>
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("repeat-end-date-help")?>"><?php etranslate("Repeat End Date")?>:</td>
<td><input type="checkbox" name="rpt_end_use" value="y" <?php
  echo ( ! empty ( $rpt_end ) ? "CHECKED" : "" ); ?>> <?php etranslate("Use end date")?>
&nbsp;&nbsp;&nbsp;
<span class="end_day_selection">
  <?php
    print_date_selection ( "rpt_", $rpt_end_date ? $rpt_end_date : $cal_date )
  ?>
</td></tr>
<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("repeat-day-help")?>"><?php etranslate("Repeat Day")?>: (<?php etranslate("for weekly")?>)</td>
  <td><?php
  echo "<input type=\"checkbox\" name=\"rpt_sun\" value=\"y\" "
     . (!empty($rpt_sun)?"CHECKED":"") . ">" . translate("Sunday");
  echo "<input type=\"checkbox\" name=\"rpt_mon\" value=\"y\" "
     . (!empty($rpt_mon)?"CHECKED":"") . ">" . translate("Monday");
  echo "<input type=\"checkbox\" name=\"rpt_tue\" value=y "
     . (!empty($rpt_tue)?"CHECKED":"") . ">" . translate("Tuesday");
  echo "<input type=\"checkbox\" name=\"rpt_wed\" value=\"y\" "
     . (!empty($rpt_wed)?"CHECKED":"") . ">" . translate("Wednesday");
  echo "<input type=\"checkbox\" name=\"rpt_thu\" value=\"y\" "
     . (!empty($rpt_thu)?"CHECKED":"") . ">" . translate("Thursday");
  echo "<input type=\"checkbox\" name=\"rpt_fri\" value=\"y\" "
     . (!empty($rpt_fri)?"CHECKED":"") . ">" . translate("Friday");
  echo "<input type=\"checkbox\" name=\"rpt_sat\" value=\"y\" "
     . (!empty($rpt_sat)?"CHECKED":"") . ">" . translate("Saturday");
  ?></td></tr>

<tr><td style="font-weight:bold;" class="tooltip" title="<?php etooltip("repeat-frequency-help")?>"><?php etranslate("Frequency")?>:</td>
<td>
  <input name="rpt_freq" size="4" maxlength="4" value="<?php echo $rpt_freq; ?>" />
 </td>
</tr>
<?php } ?>

</table>

<table style="border-width:0px;"><tr><td>
<SCRIPT LANGUAGE="JavaScript">
  document.writeln ( '<input type="button" value="<?php etranslate("Save")?>" onclick="validate_and_submit()" />' );
  document.writeln ( '<input type="button" value="<?php etranslate("Help")?>..." onclick="window.open ( \'help_edit_entry.php<?php if ( empty ( $id ) ) echo "?add=1"; ?>\', \'cal_help\', \'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420\');">' );
</SCRIPT>

<NOSCRIPT>
<input type="submit" value="<?php etranslate("Save")?>" />
</NOSCRIPT>
</td></tr></table>
<input type="hidden" name="participant_list" value="" />
</FORM>

<?php if ( $id > 0 && ( $login == $create_by || $single_user == "Y" || $is_admin ) ) { ?>
<a href="del_entry.php?id=<?php echo $id;?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?")?>');"><?php etranslate("Delete entry")?></a><br />
<?php } ?>
<?php
} else {
  echo translate("You are not authorized to edit this entry") . ".";
}
?>

<?php print_trailer(); ?>
</body>
</html>
