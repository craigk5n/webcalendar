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
load_user_layers ();

include "includes/translate.inc";

// copied from edit_entry_handler (functions.inc?)
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

if ( $year ) $thisyear = $year;
if ( $month ) $thismonth = $month;
$pri[1] = translate("Low");
$pri[2] = translate("Medium");
$pri[3] = translate("High");

$unapproved = FALSE;

?>
<HTML>
<HEAD>
<TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<?php

if ( $id < 1 ) {
  echo translate("Invalid entry id") . ".";
  exit;
}

// first see who has access to view this entry
$is_my_event = false;
$sql = "SELECT cal_id FROM webcal_entry_user " .
  "WHERE cal_login = '$login' AND cal_id = $id";
$res = dbi_query ( $sql );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  if ( $row[0] == $id )
    $is_my_event = true;
  dbi_free_result ( $res );
}

$sql = "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
  "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
  "cal_name, cal_description FROM webcal_entry WHERE cal_id = " . $id;
$res = dbi_query ( $sql );
if ( ! $res ) {
  echo translate("Invalid entry id") . ": $id";
  exit;
}
$row = dbi_fetch_row ( $res );
$create_by = $row[0];
$name = $row[9];
$description = $row[10];

// build info string for repeating events and end date
$sql = "SELECT cal_type, cal_end, cal_frequency, cal_days FROM webcal_entry_repeats " .
   "WHERE cal_id = $id";
$res = dbi_query ($sql);
if ( $res ) {
  $tmprow = dbi_fetch_row ( $res );
  if ( count ( $tmprow ) > 0 ) {
    $cal_type = $tmprow[0];
    $cal_end = $tmprow[1];
    $cal_frequency = $tmprow[2];
    $cal_days = $tmprow[3];
    dbi_free_result ( $res );

    if ( $cal_end ) {
      $rep_str .= "&nbsp; - &nbsp;";
      $rep_str .= date_to_str ( $cal_end );
    }
    $rep_str .= "&nbsp; (" . translate("every") . " ";

    if ( $cal_frequency > 1 ) {
      switch ( $cal_frequency ) {
        case 1: $rep_str .= translate("1st"); break;
        case 2: $rep_str .= translate("2nd"); break;
        case 3: $rep_str .= translate("3rd"); break;
        case 4: $rep_str .= translate("4th"); break;
        case 5: $rep_str .= translate("5th"); break;
        default: $rep_str .= $cal_frequency; break;
      }
    }
    switch ($cal_type) {
      case "daily": $rep_str .= translate("Day"); break;
      case "weekly": $rep_str .= translate("Week");
        for ($i=0; $i<=7; $i++) {
          if (substr($cal_days, $i, 1) == "y") {
            $rep_str .= ", " . weekday_short_name($i);
          }
        }
        break;
      case "monthlyByDay":
        $rep_str .= translate("Month") . "/" . translate("by day"); break;
      case "monthlyByDate":
        $rep_str .= translate("Month") . "/" . translate("by date"); break;
      case "yearly":
        $rep_str .= translate("Year"); break;
    }
    $rep_str .= ")";
  }
}
/* calculate end time */
if ( $row[2] > 0 && $row[5] > 0 ) { 
  $end_str = "-" . display_time ( add_duration ( $row[2], $row[5] ) );
}

// get the email adress of the creator of the entry
user_load_variables ( $create_by, "createby_" );
$email_addr = $createby_email;

// If confidential and not this user's event, then
// They cannot seem name or description.
//if ( $row[8] == "R" && ! $is_my_event && ! $is_admin ) {
if ( $row[8] == "R" && ! $is_my_event ) {
  $is_private = true;
  $name = "[" . translate("Confidential") . "]";
  $description = "[" . translate("Confidential") . "]";
} else {
  $is_private = false;
}

// TO DO: don't let someone view another user's private entry
// by hand editing the URL.

?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php echo htmlspecialchars ( $name ); ?></FONT></H2>

<TABLE BORDER=0>
<TR><TD VALIGN="top"><B><?php etranslate("Description")?>:</B></TD>
  <TD><?php echo nl2br ( activate_urls ( htmlspecialchars ( $description ) ) ); ?></TD></TR>
<TR><TD VALIGN="top"><B><?php etranslate("Date")?>:</B></TD>
  <TD><?php echo date_to_str ( $row[1] ) . $rep_str; ?></TD></TR>
<?php
// save date so the trailer links are for the same time period
$list = split ( "-", $row[1] );
$thisyear = (int) ( $row[1] / 10000 );
$thismonth = ( $row[1] / 100 ) % 100;
$thisday = $row[1] % 100;
?>
<?php if ( $row[2] >= 0 ) { ?>
<TR><TD VALIGN="top"><B><?php etranslate("Time")?>:</B></TD>
  <TD><?php echo display_time ( $row[2] ) . $end_str; ?></TD></TR>
<?php } ?>
<?php if ( $row[5] > 0 ) { ?>
<TR><TD VALIGN="top"><B><?php etranslate("Duration")?>:</B></TD>
  <TD><?php echo $row[5]; ?> <?php etranslate("minutes")?></TD></TR>
<?php } ?>
<?php if ( ! $disable_priority_field ) { ?>
<TR><TD VALIGN="top"><B><?php etranslate("Priority")?>:</B></TD>
  <TD><?php echo $pri[$row[6]]; ?></TD></TR>
<?php } ?>
<?php if ( ! $disable_access_field ) { ?>
<TR><TD VALIGN="top"><B><?php etranslate("Access")?>:</B></TD>
  <TD><?php echo ( $row[8] == "P" ) ? translate("Public") : translate("Confidential"); ?></TD></TR>
<?php } ?>
<?php
if ( ! strlen ( $single_user_login ) ) {
  echo "<TR><TD VALIGN=\"top\"><B>" . translate("Created by") . ":</B></TD>\n";
  if ( $is_private )
    echo "<TD>[" . translate("Confidential") . "]</TD></TR>\n";
  else {
    if ( strlen ( $email_addr ) )
      echo "<TD><A HREF=\"mailto:$email_addr\">$row[0]</A></TD></TR>\n";
    else
      echo "<TD>$row[0]</TD></TR>\n";
  }
}
?>
<TR><TD VALIGN="top"><B><?php etranslate("Updated")?>:</B></TD>
  <TD><?php
    echo date_to_str ( $row[3] );
    echo " ";
    echo display_time ( $row[4] );
   ?></TD></TR>
<?php
// load any site-specific fields and display them
$extras = get_site_extra_fields ( $id );
for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
  $extra_name = $site_extras[$i][0];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];
  if ( $extras[$extra_name]['cal_name'] != "" ) {
    echo "<TR><TD VALIGN=\"top\"><B>" .
      translate ( $site_extras[$i][1] ) .
      ":</B></TD><TD>";
    if ( $extra_type == $EXTRA_URL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) )
        echo "<A HREF=\"" . $extras[$extra_name]['cal_data'] . "\">" .
          $extras[$extra_name]['cal_data'] . "</A>";
    } else if ( $extra_type == $EXTRA_EMAIL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) )
        echo "<A HREF=\"mailto:" . $extras[$extra_name]['cal_data'] . "\">" .
          $extras[$extra_name]['cal_data'] . "</A>";
    } else if ( $extra_type == $EXTRA_DATE ) {
      if ( $extras[$extra_name]['cal_date'] > 0 )
        echo date_to_str ( $extras[$extra_name]['cal_date'] );
    } else if ( $extra_type == $EXTRA_TEXT ||
      $extra_type == $EXTRA_MULTILINETEXT ) {
      echo nl2br ( $extras[$extra_name]['cal_data'] );
    } else if ( $extra_type == $EXTRA_USER ) {
      echo $extras[$extra_name]['cal_data'];
    } else if ( $extra_type == $EXTRA_REMINDER ) {
      if ( $extras[$extra_name]['cal_remind'] <= 0 )
        etranslate ( "No" );
      else {
        etranslate ( "Yes" );
        if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_DATE ) > 0 ) {
          echo "&nbsp;&nbsp;-&nbsp;&nbsp;";
          echo date_to_str ( $extras[$extra_name]['cal_date'] );
        } else if ( ( $extra_arg2 & $EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
          echo "&nbsp;&nbsp;-&nbsp;&nbsp;";
          $minutes = $extras[$extra_name]['cal_data'];
          $d = (int) ( $minutes / ( 24 * 60 ) );
          $minutes -= ( $d * 24 * 60 );
          $h = (int) ( $minutes / 60 );
          $minutes -= ( $h * 60 );
          if ( $d > 0 )
            echo $d . " " . translate("days") . " ";
          if ( $h > 0 )
            echo $h . " " . translate("hours") . " ";
          if ( $minutes > 0 )
            echo $minutes . " " . translate("minutes");
          echo " " . translate("before event" );
        }
      }
    
    }
    echo "</TD></TR>\n";
  }
}
?>

<?php // participants
// Only ask for participants if we are multi-user.
$show_participants = ! $disable_participants_field;
if ( $is_admin )
  $show_participants = true;
if ( ! strlen ( $single_user_login ) && $show_participants ) {
?>
<TR><TD VALIGN="top"><B><?php etranslate("Participants")?>:</B></TD>
  <TD><?php
  if ( $is_private ) {
    echo "[" . translate("Confidential") . "]";
  } else {
    $sql = "SELECT cal_login, cal_status FROM webcal_entry_user " .
      "WHERE cal_id = $id";
    //echo "$sql<P>\n";
    $res = dbi_query ( $sql );
    $first = 1;
    $num_app = $num_wait = $num_rej = 0;
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $pname = $row[0];
        if ( $login == $row[0] && $row[1] == 'W' )
          $unapproved = TRUE;
        if ( $row[1] == 'A' )
          $approved[$num_app++] = $pname;
        else if ( $row[1] == 'W' )
          $waiting[$num_wait++] = $pname;
        else if ( $row[1] == 'R' )
          $rejected[$num_rej++] = $pname;
      }
      dbi_free_result ( $res );
    } else {
      echo translate ("Database error") . ": " . dbi_error() . "<BR>";
    }
  }
  for ( $i = 0; $i < $num_app; $i++ ) {
    user_load_variables ( $approved[$i], "temp" );
    echo $tempfullname . "<BR>\n";
  }
  for ( $i = 0; $i < $num_wait; $i++ ) {
    user_load_variables ( $waiting[$i], "temp" );
    echo "<BR>" . $tempfullname . " (?)\n";
  }
  for ( $i = 0; $i < $num_rej; $i++ ) {
    user_load_variables ( $rejected[$i], "temp" );
    echo "<BR><STRIKE>" . $tempfullname . "</STRIKE> (" . translate("Rejected") . ")\n";
  }
?></TD></TR>
<?php
} // end participants
?>

</TABLE>

<P>
<?php
if ( $unapproved ) {
  echo "<A HREF=\"approve_entry.php?id=$id\" onClick=\"return confirm('" .
    translate("Approve this entry?") .
    "');\">" . translate("Approve/Confirm entry") . "</A><BR>\n";
  echo "<A HREF=\"reject_entry.php?id=$id\" onClick=\"return confirm('" .
    translate("Reject this entry?") .
    "');\">" . translate("Reject entry") . "</A><BR>\n";
}

if ( $login != $user && strlen ( $user ) )
  $u_url = "&user=$user";

if ( $is_admin ||
  ( ! $readonly && ( $login == $create_by || strlen ( $single_user_login ) ) ) ) {
  echo "<A HREF=\"edit_entry.php?id=$id\">" .
    translate("Edit entry") . "</A><BR>\n";
  echo "<A HREF=\"del_entry.php?id=$id$u_url\" onClick=\"return confirm('" .
    translate("Are you sure you want to delete this entry?") .
    "\\n\\n" . translate("This will delete this entry for all users.") .
    "');\">" . translate("Delete entry") . "</A><BR>\n";
} elseif ( ! $readonly && $is_my_event )  {
  echo "<A HREF=\"del_entry.php?id=$id$u_url\" onClick=\"return confirm('" .
    translate("Are you sure you want to delete this entry?") .
    "\\n\\n" . translate("This will delete the entry from your calendar.") .
    "');\">" . translate("Delete entry") . "</A><BR>\n";
}
if ( ! $readonly && ! $is_my_event && ! $is_private )  {
  echo "<A HREF=\"add_entry.php?id=$id\" onClick=\"return confirm('" .
    translate("Do you want to add this entry to your calendar?") .
    "\\n\\n" . translate("This will add the entry to your calendar.") .
    "');\">" . translate("Add to My Calendar") . "</A><BR>\n";
}

?>

<?php include "includes/trailer.inc"; ?>
</BODY>
</HTML>
