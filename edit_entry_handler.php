<?php php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/user.inc";
include "includes/site_extras.inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
//load_user_layers ();

include "includes/translate.inc";


// Input time format "235900", duration is minutes
function add_duration ( $time, $duration ) {
  $hour = (int) ( $time / 10000 );
  $min = ( $time / 100 ) % 100;
  $minutes = $hour * 60 + $min + $duration;
  $h = $minutes / 60;
  $m = $minutes % 60;
  $ret = sprintf ( "%d%02d00", $h, $m );
  //echo "add_duration ( $time, $duration ) = $ret <BR>";
  return $ret;
}

// check to see if two events overlap
// time1 and time2 should be an integer like 235900
// duration1 and duration2 are integers in minutes
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {
  //echo "times_overlap ( $time1, $duration1, $time2, $duration2 )<BR>";
  $hour1 = (int) ( $time1 / 10000 );
  $min1 = ( $time1 / 100 ) % 100;
  $hour2 = (int) ( $time2 / 10000 );
  $min2 = ( $time2 / 100 ) % 100;
  // convert to minutes since midnight
  // remove 1 minute from duration so 9AM-10AM will not conflict with 10AM-11AM
  if ( $duration1 > 0 )
    $duration1 -= 1;
  if ( $duration2 > 0 )
    $duration2 -= 1;
  $tmins1start = $hour1 * 60 + $min1;
  $tmins1end = $tmins1start + $duration1;
  $tmins2start = $hour2 * 60 + $min2;
  $tmins2end = $tmins2start + $duration2;
  //echo "tmins1start=$tmins1start, tmins1end=$tmins1end, tmins2start=$tmins2start, tmins2end=$tmins2end<BR>";
  if ( $tmins1start >= $tmins2start && $tmins1start <= $tmins2end )
    return true;
  if ( $tmins1end >= $tmins2start && $tmins1end <= $tmins2end )
    return true;
  if ( $tmins2start >= $tmins1start && $tmins2start <= $tmins1end )
    return true;
  if ( $tmins2end >= $tmins1start && $tmins2end <= $tmins1end )
    return true;
  return false;
}

// TODO: make sure this user is really allowed to edit this event.
// Otherwise, someone could hand type in the URL to edit someone else's
// event.


$duration = ( $duration_h * 60 ) + $duration_m;
if ( strlen ( $hour ) > 0 ) {
  if ( $TIME_FORMAT == '12' ) {
    $ampmt = $ampm;
    //This way, a user can pick am and still
    //enter a 24 hour clock time.
    if ($hour > 12 && $ampm == 'am') {
      $ampmt = 'pm';
    }
    $hour %= 12;
    if ( $ampmt == 'pm' )
      $hour += 12;
  }
}

// first check for any schedule conflicts
if ( $allow_conflicts == 0 && !$confirm_conflicts && strlen ( $hour ) > 0 ) {
  $date = mktime ( 0, 0, 0, $month, $day, $year );
  if ( $rpt_end_use )
    $endt = mktime (0,0,0, $rpt_month, $rpt_day,$rpt_year );
  else
    $endt = 'NULL';

  if ($rpt_type == 'weekly') {
    $dayst = ( $rpt_sun ? 'y' : 'n' )
      . ( $rpt_mon ? 'y' : 'n' )
      . ( $rpt_tue ? 'y' : 'n' )
      . ( $rpt_wed ? 'y' : 'n' )
      . ( $rpt_thu ? 'y' : 'n' )
      . ( $rpt_fri ? 'y' : 'n' )
      . ( $rpt_sat ? 'y' : 'n' );
  } else {
    $dayst = "nnnnnnn";
  }
  
  $dates = get_all_dates($date, $rpt_type, $endt, $dayst, $rpt_freq);
  //echo $id . "<BR>";
  $overlap = overlap($dates,$duration,$hour,$minute,$participants,$login,$id);

}
if ( strlen ( $overlap ) ) {
  $error = translate("The following conflicts with the suggested time") .
    ":<UL>$overlap</UL>";
}


if ( strlen ( $error ) == 0 ) {

  // now add the entries
  if ( $id == 0 ) {
    $res = dbi_query ( "SELECT MAX(cal_id) FROM webcal_entry" );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0] + 1;
      dbi_free_result ( $res );
    } else {
      $id = 1;
    }
    $newevent = true;
  } else {
    // save old status values of participants
    $sql = "SELECT cal_login, cal_status FROM webcal_entry_user " .
      "WHERE cal_id = $id ";
    $res = dbi_query ( $sql );
    for ( $i = 0; $tmprow = dbi_fetch_row ( $res ); $i++ ) {
      $old_status[$tmprow[0]] = $tmprow[1]; 
    }
    dbi_free_result ( $res );
    dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
    $newevent = false;
  }

  $sql = "INSERT INTO webcal_entry ( cal_id, cal_create_by, cal_date, " .
    "cal_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, " .
    "cal_access, cal_type, cal_name, cal_description ) " .
    "VALUES ( $id, '$login', ";

  $date = mktime ( 0, 0, 0, $month, $day, $year );
  $sql .= date ( "Ymd", $date ) . ", ";
  if ( strlen ( $hour ) > 0 ) {
    $sql .= sprintf ( "%02d%02d00, ", $hour, $minute );
  } else
    $sql .= "-1, ";
  $sql .= date ( "Ymd" ) . ", " . date ( "Gis" ) . ", ";
  $sql .= sprintf ( "%d, ", $duration );
  $sql .= sprintf ( "%d, ", $priority );
  $sql .= "'$access', ";
  if ( $rpt_type != 'none' )
    $sql .= "'M', ";
  else
    $sql .= "'E', ";

  if ( strlen ( $name ) == 0 )
    $name = translate("Unnamed Event");
  $sql .= "'" . $name .  "', ";
  if ( strlen ( $description ) == 0 )
    $description = $name;
  $sql .= "'" . $description . "' )";
  
  $error = "";
  if ( ! dbi_query ( $sql ) )
    $error = "Unable to add entry: " . dbi_error () . "<P><B>SQL:</B> $sql";
  $msg .= "<B>SQL:</B> $sql<P>";
  
  if ( strlen ( $single_user_login ) ) {
    $participants[0] = $single_user_login;
  }

  // check if participants have been removed and send out emails
  if ( ! $newevent ) {  // nur bei Update!!!
    while ( list ( $old_participant, $dummy ) = each ( $old_status ) ) {
      $found_flag = false;
      for ( $i = 0; $i < count ( $participants ); $i++ ) {
        if ( $participants[$i] == $old_participant ) {
          $found_flag = true;
          break;
        }
      }
      if ( !$found_flag ) {
        // only send mail if their email address is filled in
        $do_send = get_pref_setting ( $old_participants, "EMAIL_EVENT_DELETED" );
        user_load_variables ( $old_participant, "temp" );
        if ( $old_participant != $login && strlen ( $tempemail ) &&
          $do_send == "Y" ) {
          $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
            translate("An appointment has been canceled for you by") .
            " " . $login_fullname .  ". " .
            translate("The subject was") . " \"" . $name . "\"\n\n";
          if ( strlen ( $login_email ) )
            $extra_hdrs = "From: $login_email\nX-Mailer: " . translate("Title");
          else
            $extra_hdrs = "From: $email_fallback_from\nX-Mailer: " . translate("Title");
          mail ( $tempemail,
            translate("Title") . " " . translate("Notification") . ": " . $name,
            $msg, $extra_hdrs );
        }
      }
    }
  }

  // now add participants and send out notifications
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    // keep the old status if no email will be sent
    $send_mail = ( $old_status[$participants[$i]] == '' || $entry_changed ) ?
      true : false;
    $tmp_status = ( $old_status[$participants[$i]] && ! $send_mail ) ?
      $old_status[$participants[$i]] : "W";
    $status = ( $participants[$i] != $login && $require_approvals ) ?
      $tmp_status : "A";
    $sql = "INSERT INTO webcal_entry_user " .
      "( cal_id, cal_login, cal_status ) VALUES ( $id, '" .
      $participants[$i] . "', '$status' )";
    if ( ! dbi_query ( $sql ) ) {
      $error = "Unable to add to webcal_entry_user: " . dbi_error () .
        "<P><B>SQL:</B> $sql";
      break;
    } else {
      $from = $user_email;
      if ( strlen ( $from ) == 0 && strlen ( $email_fallback_from ) )
        $from = $email_fallback_from;
      // only send mail if their email address is filled in
      $do_send = get_pref_setting ( $participants[$i],
         newevent ? "EMAIL_EVENT_ADDED" : "EMAIL_EVENT_UPDATED" );
      user_load_variables ( $participants[$i], "temp" );
      if ( $participants[$i] != $login && strlen ( $tempemail ) &&
        $do_send == "Y" && $send_mail ) {
        $msg = translate("Hello") . ", " . $tempfullname . ".\n\n";
        if ( $newevent || $old_status[$participants[$i]] == '' )
          $msg .= translate("A new appointment has been made for you by");
        else
          $msg .= translate("An appointment has been updated by");
        $msg .= " " . $login_fullname .  ". " .
          translate("The subject is") . " \"" . $name . "\"\n\n" .
          translate("Please look on") . " " . translate("Title") . " " .
          ( $require_approvals ?
          translate("to accept or reject this appointment") :
          translate("to view this appointment") ) . ".";
        if ( strlen ( $from ) )
          $extra_hdrs = "From: $from\nX-Mailer: " . translate("Title");
        else
          $extra_hdrs = "X-Mailer: " . translate("Title");
        mail ( $tempemail,
          translate("Title") . " " . translate("Notification") . ": " . $name,
          $msg, $extra_hdrs );
      }
    }
  }

  // add site extras
  for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
    $sql = "";
    $extra_name = $site_extras[$i][0];
    $extra_type = $site_extras[$i][2];
    $extra_arg1 = $site_extras[$i][3];
    $extra_arg2 = $site_extras[$i][4];
    $value = $$extra_name;
    //echo "Looking for $extra_name... value = " . $value . " ... type = " .
    // $extra_type . "<BR>\n";
    if ( strlen ( $$extra_name ) || $extra_type == $EXTRA_DATE ) {
      if ( $extra_type == $EXTRA_URL || $extra_type == $EXTRA_EMAIL ||
        $extra_type == $EXTRA_TEXT || $extra_type == $EXTRA_USER ||
        $extra_type == $EXTRA_MULTILINETEXT  ) {
        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_data ) VALUES ( " .
          "$id, '$extra_name', $extra_type, '$value' )";
      } else if ( $extra_type == $EXTRA_REMINDER ) {
        $remind = ( $value == "1" ? 1 : 0 );
        if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_DATE ) > 0 ) {
          $yname = $extra_name . "year";
          $mname = $extra_name . "month";
          $dname = $extra_name . "day";
          $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
          $sql = "INSERT INTO webcal_site_extras " .
            "( cal_id, cal_name, cal_type, cal_remind, cal_date ) VALUES ( " .
            "$id, '$extra_name', $extra_type, $remind, $edate )";
        } else if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
          $dname = $extra_name . "_days";
          $hname = $extra_name . "_hours";
          $mname = $extra_name . "_minutes";
          $minutes = ( $$dname * 24 * 60 ) + ( $$hname * 60 ) + $$mname;
          $sql = "INSERT INTO webcal_site_extras " .
            "( cal_id, cal_name, cal_type, cal_remind, cal_data ) VALUES ( " .
            "$id, '$extra_name', $extra_type, $remind, '" . $minutes . "' )";
        } else {
          $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_remind ) VALUES ( " .
          "$id, '$extra_name', $extra_type, $remind )";
        }
      } else if ( $extra_type == $EXTRA_DATE )  {
        $yname = $extra_name . "year";
        $mname = $extra_name . "month";
        $dname = $extra_name . "day";
        $edate = sprintf ( "%04d%02d%02d", $$yname, $$mname, $$dname );
        $sql = "INSERT INTO webcal_site_extras " .
          "( cal_id, cal_name, cal_type, cal_date ) VALUES ( " .
          "$id, '$extra_name', $extra_type, $edate )";
      }
    }
    if ( strlen ( $sql ) ) {
      //echo "SQL: $sql<BR>\n";
      $res = dbi_query ( $sql );
    }
  }

  // clearly, we want to delete the old repeats, before inserting new...
  dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id");
  // add repeating info
  if ( strlen ( $rpt_type ) && $rpt_type != 'none' ) {
    $freq = ( $rpt_freq ? $rpt_freq : 1 );
    if ( $rpt_end_use )
      $end = sprintf ( "%04d%02d%02d", $rpt_year, $rpt_month, $rpt_day );
    else
      $end = 'NULL';
    if ($rpt_type == 'weekly') {
      $days = ( $rpt_sun ? 'y' : 'n' )
        . ( $rpt_mon ? 'y' : 'n' )
        . ( $rpt_tue ? 'y' : 'n' )
        . ( $rpt_wed ? 'y' : 'n' )
        . ( $rpt_thu ? 'y' : 'n' )
        . ( $rpt_fri ? 'y' : 'n' )
        . ( $rpt_sat ? 'y' : 'n' );
    } else {
      $days = "nnnnnnn";
    }

    $sql = "INSERT INTO webcal_entry_repeats ( cal_id, " .
      "cal_type, cal_end, cal_days, cal_frequency ) VALUES " .
      "( $id, '$rpt_type', $end, '$days', $freq )";
    dbi_query ( $sql );
    $msg .= "<B>SQL:</B> $sql<P>";
  }
}

#print $msg; exit;

if ( strlen ( $error ) == 0 ) {
  $date = sprintf ( "%04d%02d%02d", $year, $month, $day );
  do_redirect ( "$STARTVIEW.php?date=$date" );
}

?>
<HTML>
<HEAD><TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<?php if ( strlen ( $overlap ) ) { ?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Scheduling Conflict")?></H2></FONT>

<?php etranslate("Your suggested time of")?> <B>
<?php
  $time = sprintf ( "%d%02d00", $hour, $minute );
  echo display_time ( $time );
  if ( $duration > 0 )
    echo "-" . display_time ( add_duration ( $time, $duration ) );
?>
</B> <?php etranslate("conflicts with the following existing calendar entries")?>:
<UL>
<?php echo $overlap; ?>
</UL>

<?php
// user can confirm conflicts
  echo "<form name=\"confirm\" method=\"post\">\n";
  while (list($xkey, $xval)=each($HTTP_POST_VARS)) {
    if (is_array($xval)) {
      $xkey.="[]";
      while (list($ykey, $yval)=each($xval)) {
        echo "<input type=\"hidden\" name=\"$xkey\" value=\"$yval\">\n";
      }
    } else {
      echo "<input type=\"hidden\" name=\"$xkey\" value=\"$xval\">\n";
    }
  }
?>
<table>
 <tr>
   <td><input type="submit" name="confirm_conflicts" value="&nbsp;<?php etranslate("Save")?>&nbsp;"></td>
   <td><input type="button" value="<?php etranslate("Cancel")?>" onClick="history.back()"><td>
 </tr>
</table>
</form>

<?php } else { ?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php } ?>


<?php include "includes/trailer.inc"; ?>

</BODY>
</HTML>
