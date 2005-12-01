<?php
/*
 * $Id: view_task.php
 *
 * Description:
 * Presents page to view a task with links to edit, delete
 * confirm, copy, add task
 *
 * Input Parameters:
 * id (*) - cal_id of requested event
 * date  - yyyymmdd format of requested event
 * user  - user to display
 * (*) required field
 */
include_once 'includes/init.php';

// make sure this user is allowed to look at this calendar.
$can_view = false;
$is_my_event = false;
$is_private = $is_confidential = false;
$log = getGetValue ( 'log' );
$show_log = ! empty ( $log );

if ( $is_admin || $is_assistant ) {
  $can_view = true;
} 

$error = '';

if ( empty ( $id ) || $id <= 0 || ! is_numeric ( $id ) ) {
  $error = translate ( "Invalid task id" ) . "."; 
}

//update the task percentage for this user
if ( ! empty ( $_POST ) ) {
  $upercent = getPostValue ( 'upercent' );
 if ( $upercent >= 0 && $upercent <= 100 )
    dbi_query ("UPDATE webcal_entry_user SET cal_percent = $upercent " .
     " WHERE cal_login = '$login'");
}



if ( empty ( $error ) ) {
  // is this user a participant or the creator of the event?
  $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
    "webcal_entry_user WHERE webcal_entry.cal_id = webcal_entry_user.cal_id AND " .
  "webcal_entry.cal_id = $id " .
    "AND (webcal_entry.cal_create_by = '$login' " .
    "OR webcal_entry_user.cal_login = '$login')";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] > 0 ) {
      $can_view = true;
      $is_my_event = true;
    }
    dbi_free_result ( $res );
  }
 //group checking deleted. 
}

//update the task percentage for this user
if ( ! empty ( $_POST ) && $can_view && $is_my_event ) {
  $upercent = getPostValue ( 'upercent' );
 if ( $upercent >= 0 && $upercent <= 100 )
    dbi_query ("UPDATE webcal_entry_user SET cal_percent = $upercent " .
     " WHERE cal_login = '$login'");
}
$hide_details = false;

//if sent here from an email and not logged in,
//save URI and redirect to login
if ( empty ( $error ) && ! $can_view ) {
  $em = getGetValue ( 'em' );
  if ( ! empty ( $em ) ) {
    remember_this_view ();
    do_redirect ( 'login.php' );  
  } 
  $error = translate ( "You are not authorized" );
}

if ( ! empty ( $year ) ) {
  $thisyear = $year;
}
if ( ! empty ( $month ) ) {
  $thismonth = $month;
}
$pri[1] = translate("Low");
$pri[2] = translate("Medium");
$pri[3] = translate("High");

$unapproved = FALSE;

print_header();

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}
// Try to determine the event status.
$event_status = "";

if ( ! empty ( $user ) && $login != $user ) {
  // If viewing another user's calendar, check the status of the
  // event on their calendar (to see if it's deleted).
  $sql = "SELECT cal_status FROM webcal_entry_user " .
    "WHERE cal_login = '$user' AND cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_status = $row[0];
    }
    dbi_free_result ( $res );
  }
} else {
  // We are viewing event on user's own calendar, so check the
  // status on their own calendar.
  $sql = "SELECT cal_id, cal_status FROM webcal_entry_user " .
    "WHERE cal_login = '$login' AND cal_id = $id";
  $res = dbi_query ( $sql );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    $event_status = $row[1];
    dbi_free_result ( $res );
  }
}

// At this point, if we don't have the event status, then either
// this user is not viewing an event from his own calendar and not
// viewing an event from someone else's calendar.
// They probably got here from the search results page (or possibly
// by hand typing in the URL.)
// Check to make sure that it hasn't been deleted from everyone's
// calendar.
if ( empty ( $event_status ) ) {
  $sql = "SELECT cal_status FROM webcal_entry_user " .
    "WHERE cal_status <> 'D' ORDER BY cal_status";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $event_status = $row[0];
    }
    dbi_free_result ( $res );
  }
}

// If we have no event status yet, it must have been deleted.
if ( ( empty ( $event_status ) && ! $is_admin ) || ! $can_view ) {
  echo "<h2>" . 
    translate("Error") . "</h2>" . 
    translate("You are not authorized") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}


// Load event info now.
$sql = "SELECT cal_create_by, cal_date, cal_time, cal_mod_date, " .
  "cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, " .
  "cal_name, cal_description, cal_location, cal_url, cal_due_date, " .
  "cal_due_time FROM webcal_entry WHERE webcal_entry.cal_type IN " .
  " ('T','N') AND cal_id = $id";
$res = dbi_query ( $sql );
if ( ! $res ) {
  echo translate("Invalid task id") . ": $id";
  exit;
}

$row = dbi_fetch_row ( $res );
if ( $row ) { 
  $create_by = $row[0];
  $orig_date = $row[1];
  $event_time = $row[2];
 $due_date = $row[13];
 $due_time = $row[14];
  if ( $hide_details ) {
    $name = translate ( $OVERRIDE_PUBLIC_TEXT );
    $description = translate ( $OVERRIDE_PUBLIC_TEXT );
    if ( ! empty ( $row[11] ) ) $location =  translate ( $OVERRIDE_PUBLIC_TEXT );
  } else {
    $name = $row[9];
    $description = $row[10];
    $location  = $row[11];
  }
} else {
  echo "<h2>" . 
    translate("Error") . "</h2>" . 
    translate("Invalid task id") . ".\n";
  print_trailer ();
  echo "</body>\n</html>";
  exit;
}

// Timezone Adjustments
$adjusted_start = get_datetime_add_tz ( $orig_date, $event_time );
$adjusted_due = get_datetime_add_tz ( $due_date, $due_time );
 
$adjusted_time = date ( "His",$adjusted_start );
$adjusted_date = date (  "Ymd", $adjusted_start );

$adjusted_due_time = date ( "His",$adjusted_due );
$adjusted_due_date = date (  "Ymd", $adjusted_due );

// save date so the trailer links are for the same time period
$thisyear = (int) ( $adjusted_date / 10000 );
$thismonth = ( $adjusted_date / 100 ) % 100;
$thisday = $adjusted_date % 100;
//echo "$adjusted_due $thismonth  $thisday  $thisyear";
$thistime = mktime ( 0, 0, 0, $thismonth, $thisday, $thisyear );
$thisdow = date ( "w", $thistime );

// $subject is used for mailto URLs
$subject = translate($APPLICATION_NAME) . ": " . $name;
// Remove the '"' character since it causes some mailers to barf
$subject = str_replace ( "\"", "", $subject );
$subject = htmlspecialchars ( $subject );

$event_repeats = false;
// build info string for repeating events and end date
$sql = "SELECT cal_type FROM webcal_entry_repeats WHERE cal_id = $id";

$res = dbi_query ($sql);
$rep_str = '';
if ( $res ) {
  if ( $tmprow = dbi_fetch_row ( $res ) ) {
    $event_repeats = true;
  } 
  dbi_free_result ( $res );
}

// get the email adress of the creator of the entry
user_load_variables ( $create_by, "createby_" );
$email_addr = empty ( $createby_email ) ? '' : $createby_email;

// If private and not this user's event or
// Confidential and not user's or not assistant, then
// They cannot seem name or description.
//if ( $row[8] == "R" && ! $is_my_event && ! $is_admin ) {
if ( $row[8] == "R" && ! $is_my_event ) {
  $is_private = true;
  $name = "[" . translate("Private") . "]";
  $description = "[" . translate("Private") . "]";
} else if ( $row[8] == "C" &&  ! $is_my_event && ! $is_assistant ) {
  $is_confidential = true;
  $name = "[" . translate("Confidential") . "]";
  $description = "[" . translate("Confidential") . "]";
}

if ( $event_repeats && ! empty ( $date ) )
  $event_date = $date;
else
  $event_date = $row[1];

// TODO: don't let someone view another user's private entry
// by hand editing the URL.

// Get category Info
if ( $CATEGORIES_ENABLED == "Y" ) {
  $categories = array();
  $cat_owner =  ( ( ! empty ( $user ) && strlen ( $user ) ) &&  ( $is_assistant  ||
    $is_admin ) ) ? $user : $login;  
  $sql = "SELECT cat_name FROM webcal_categories, webcal_entry_categories " .
    "WHERE ( webcal_entry_categories.cat_owner = '$cat_owner' OR " .
  "webcal_entry_categories.cat_owner IS NULL) AND webcal_entry_categories.cal_id = $id " .
    "AND webcal_entry_categories.cat_id = webcal_categories.cat_id " .
  "ORDER BY webcal_entry_categories.cat_order";
  $res2 = dbi_query ( $sql );
  if ( $res2 ) {
    while ($row2 = dbi_fetch_row ( $res2 )) { 
      $categories[] = $row2[0];
  }
    dbi_free_result ( $res2 );
  $category = implode ( ", ", $categories);
  }
}
?>
<h2><?php echo $name; ?></h2>
<table border="0" width="100%">
<tr><td style="vertical-align:top; font-weight:bold;" width="10%">
 <?php etranslate("Description")?>:</td><td>
 <?php
  if ( ! empty ( $ALLOW_HTML_DESCRIPTION ) &&
    $ALLOW_HTML_DESCRIPTION == 'Y' ) {
    $str = str_replace ( '&', '&amp;', $description );
    $str = str_replace ( '&amp;amp;', '&amp', $str );
    // If there is no html found, then go ahead and replace
    // the line breaks ("\n") with the html break.
    if ( strstr ( $str, "<" ) && strstr ( $str, ">" ) ) {
      // found some html...
      echo $str;
    } else {
      echo nl2br ( activate_urls ( $str ) );
    }
  } else {
    echo nl2br ( activate_urls ( htmlspecialchars ( $description ) ) );
  }
?></td></tr>
 <?php
  if (   ( empty ( $DISABLE_LOCATION_FIELD ) ||
    $ALLOW_HTML_DESCRIPTION != 'Y' ) && ! empty ( $location ) ) { 
    echo "<tr><td style=\"vertical-align:top; font-weight:bold;\">";
    echo translate("Location") . ":</td><td>";
    echo $location . "</td><tr>\n";
  }
  
if (  ! empty ( $event_status ) ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Status")?>:</td><td>
 <?php
     if ( $event_status == 'A' )
       etranslate("Accepted");
     if ( $event_status == 'W' )
       etranslate("Needs-Action");
     if ( $event_status == 'D' )
       etranslate("Declined");
     else if ( $event_status == 'R' )
       etranslate("Rejected");
      ?>
</td></tr>
<?php } ?>

<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Start Date")?>:</td><td>
 <?php
   echo date_to_str ( $orig_date );
  ?>
</td></tr>
<?php if ( $event_time >= 0 ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Start Time")?>:</td><td>
 <?php
   echo display_time ( $orig_date . $event_time, 2 );
  ?>
</td></tr>
<?php } ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Due Date")?>:</td><td>
 <?php
   echo date_to_str ( $due_date );
  ?>
</td></tr>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Due Time")?>:</td><td>
 <?php
   echo display_time (  $due_date . $due_time, 2 );
  ?>
</td></tr>
<?php if ( $event_repeats ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Repeat Type")?>:</td><td>
 <?php echo date_to_str ( $row[1], "", true, false, $event_time ) . $rep_str; ?>
</td></tr>
<?php } ?>
<?php if ( $DISABLE_PRIORITY_FIELD != "Y" ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Priority")?>:</td><td>
 <?php echo $pri[$row[6]]; ?>
</td></tr>
<?php } ?>
<?php if ( $DISABLE_ACCESS_FIELD != "Y" ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Access")?>:</td><td>
 <?php echo ( $row[8] == "P" ) ? translate("Public") : ( $row[8] == "C" ? translate("Confidential") : translate("Private")  ); ?>
</td></tr>
<?php } ?>
<?php if ( $CATEGORIES_ENABLED == "Y" && ! empty ( $category ) ) { ?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Category")?>:</td><td>
 <?php echo $category; ?>
</td></tr>
<?php } ?>
<?php
// Display who originally created event
// useful if assistant or Admin
$proxy_fullname = '';  
if ( !empty ( $DISPLAY_CREATED_BYPROXY ) && $DISPLAY_CREATED_BYPROXY == "Y" ) {
  $res = dbi_query ( "SELECT wu.cal_firstname, wu.cal_lastname " .
    "FROM webcal_user wu INNER JOIN webcal_entry_log wel ON wu.cal_login = wel.cal_login " .
    "WHERE wel.cal_entry_id = $id " .
    "AND wel.cal_type = 'C'" );
  if ( $res ) {
    $row3 = dbi_fetch_row ( $res ) ;
   $proxy_fullname = $row3[0] . " " . $row3[1];
   $proxy_fullname = ($createby_fullname == $proxy_fullname ? ""  :
      " ( by " . $proxy_fullname . " )");
  }
}

if ( $single_user == "N" && ! empty ( $createby_fullname )  ) {
  echo "<tr><td style=\"vertical-align:top; font-weight:bold;\">\n" . 
 translate("Created by") . ":</td><td>\n";
  if ( $is_private ) {
    echo "[" . translate("Private") . "]\n</td></tr>";
  } else   if ( $is_confidential ) {
    echo "[" . translate("Confidential") . "]\n</td></tr>";
  } else {
    if ( strlen ( $email_addr ) ) {
      echo "<a href=\"mailto:$email_addr?subject=$subject\">" .
        ( $row[0] == "__public__" ? translate( "Public Access" ): $createby_fullname ) .
        "</a>$proxy_fullname\n</td></tr>";
    } else {
      echo ( $row[0] == "__public__" ? translate( "Public Access" ) : $createby_fullname ) .
        "$proxy_fullname\n</td></tr>";
    }
  }
}
?>
<tr><td style="vertical-align:top; font-weight:bold;">
 <?php etranslate("Updated")?>:</td><td>
 <?php
    echo date_to_str ( $row[3] );
    echo " ";
    echo display_time ( $row[4], 3 );
   ?>
</td></tr>
<?php
// load any site-specific fields and display them
$extras = get_site_extra_fields ( $id );
for ( $i = 0; $i < count ( $site_extras ); $i++ ) {
  $extra_name = $site_extras[$i][0];
  $extra_type = $site_extras[$i][2];
  $extra_arg1 = $site_extras[$i][3];
  $extra_arg2 = $site_extras[$i][4];
  if ( ! empty ( $extras[$extra_name]['cal_name'] ) ) {
    echo "<tr><td style=\"vertical-align:top; font-weight:bold;\">\n" .
      translate ( $site_extras[$i][1] ) .
      ":</td><td>\n";
    if ( $extra_type == EXTRA_URL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) ) {
        echo "<a href=\"" . $extras[$extra_name]['cal_data'] . "\">" .
          $extras[$extra_name]['cal_data'] . "</a>\n";
      }
    } else if ( $extra_type == EXTRA_EMAIL ) {
      if ( strlen ( $extras[$extra_name]['cal_data'] ) ) {
        echo "<a href=\"mailto:" . $extras[$extra_name]['cal_data'] .
          "?subject=$subject\">" .
          $extras[$extra_name]['cal_data'] . "</a>\n";
      }
    } else if ( $extra_type == EXTRA_DATE ) {
      if ( $extras[$extra_name]['cal_date'] > 0 ) {
        echo date_to_str ( $extras[$extra_name]['cal_date'] );
      }
    } else if ( $extra_type == EXTRA_TEXT ||
      $extra_type == EXTRA_MULTILINETEXT ) {
      echo nl2br ( $extras[$extra_name]['cal_data'] );
    } else if ( $extra_type == EXTRA_USER ) {
      echo $extras[$extra_name]['cal_data'];
    } else if ( $extra_type == EXTRA_REMINDER ) {
      if ( $extras[$extra_name]['cal_remind'] <= 0 ) {
        etranslate ( "No" );
      } else {
        etranslate ( "Yes" );
        if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_DATE ) > 0 ) {
          echo "&nbsp;&nbsp;-&nbsp;&nbsp;";
          echo date_to_str ( $extras[$extra_name]['cal_date'] );
        } else if ( ( $extra_arg2 & EXTRA_REMINDER_WITH_OFFSET ) > 0 ) {
          echo "&nbsp;&nbsp;-&nbsp;&nbsp;";
          $minutes = $extras[$extra_name]['cal_data'];
          $d = (int) ( $minutes / ( 24 * 60 ) );
          $minutes -= ( $d * 24 * 60 );
          $h = (int) ( $minutes / 60 );
          $minutes -= ( $h * 60 );
          if ( $d > 1 ) {
            echo $d . " " . translate("days") . " ";
          } else if ( $d == 1 ) {
            echo $d . " " . translate("day") . " ";
          }
          if ( $h > 1 ) {
            echo $h . " " . translate("hours") . " ";
          } else if ( $h == 1 ) {
            echo $h . " " . translate("hour") . " ";
          }
          if ( $minutes > 1 ) {
            echo $minutes . " " . translate("minutes");
          } else if ( $minutes == 1 ) {
            echo $minutes . " " . translate("minute");
          }
          echo " " . translate("before due time" );
        }
      }
    } else if ( $extra_type == EXTRA_SELECTLIST ) {
      echo $extras[$extra_name]['cal_data'];
    }
    echo "\n</td></tr>\n";
  }
}
?>

<?php // participants
// Always show Participants
$allmails = array ();
$show_participants = true;

if ( $show_participants ) { ?>
  <tr><td style="vertical-align:top; font-weight:bold;" colspan="2">
  <?php

  if ( $is_private ) {
    echo "[" . translate("Private") . "]";
  } else   if ( $is_confidential ) {
    echo "[" . translate("Confidential") . "]";
  } else {
    $sql = "SELECT cal_login, cal_status, cal_percent FROM webcal_entry_user " .
      "WHERE cal_id = $id AND cal_status IN ( 'A', 'W' )";
    //echo "$sql\n";
    $res = dbi_query ( $sql );
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        $participants[] = $row;
        if ( ( $login == $row[0] || 
          ( $is_nonuser_admin && ! empty ( $user ) && $user == $row[0] ) ) && 
          $row[1] == 'W' ) {
          $unapproved = TRUE;
        }        
      }
      dbi_free_result ( $res );
    } else {
      echo translate ("Database error") . ": " . dbi_error() . "<br />\n";
    }
  }
 echo "<table  border=\"1\"  width=\"80%\" cellspacing=\"0\" cellpadding=\"0\">\n";
 echo "<th align=\"center\">" .translate( "Participants" ) . "</th>";
 echo "<th align=\"center\" colspan=\"2\">" . translate( "Percentage Complete" ) . "</th>";
  for ( $i = 0; $i < count ( $participants ); $i++ ) {
    user_load_variables ( $participants[$i][0], "temp" );
  $spacer = 100 - $participants[$i][2];
  $percentage = $participants[$i][2];
 if ( $participants[$i][0] == $login ) $login_percentage = $participants[$i][2];
  echo "<tr><td width=\"30%\">";
    if ( strlen ( $tempemail ) ) {  
      echo "<a href=\"mailto:" . $tempemail . "?subject=$subject\">" .
     "&nbsp;" . $tempfullname . "</a>";
    $allmails[] = $tempemail;
  } else {
    echo "&nbsp;" . $tempfullname;
  }  
    echo "</td>\n";
  echo "<td width=\"5%\" align=\"center\">$percentage%</td>\n<td width=\"65%\">";
  echo "<img src=\"pix.gif\" width=\"$percentage%\" height=\"10\">";
  echo "<img src=\"spacer.gif\" width=\"$spacer\" height=\"10\">";
  echo "</td></tr>\n";
  }
  echo "</table>";
?>
</td></tr>
<?php
 } // end participants
?>

</table>
<?php
$can_edit = ( $is_admin || $is_nonuser_admin && ($user == $create_by) || 
  ( $is_assistant && ! $is_private && ($user == $create_by) ) ||
  ( $readonly != "Y" && ( $login == $create_by || $single_user == "Y" ) ) );
if ( $PUBLIC_ACCESS == "Y" && $login == "__public__" ) {
  $can_edit = false;
}
if ( $readonly == 'Y' ) {
  $can_edit = false;
}
if ( $is_nonuser )
  $can_edit = false;

//allow user to update their task completion percentage
if ( empty ( $user ) && $readonly != "Y" && $is_my_event && 
  $login != "__public__" && ! $is_nonuser && 
 $event_status != "D" && ! $can_edit )  {
  echo "<form action=\"view_task.php?id=$id\" method=\"post\" name=\"setpercentage\">\n";
  echo  translate ("Update Task Percentage") . "&nbsp;<select name=\"upercent\" id=\"task_percent\">\n";
  for ( $i=0; $i<=100 ; $i+=10 ){ 
    echo "<option value=\"$i\" " .  ($login_percentage == $i? " selected=\"selected\"":""). " >" .  $i . "</option>\n";
  }
  echo "</select>\n";
  echo "&nbsp;<input type=\"submit\" value=\"" . translate("Update") . "\" />\n";
  echo "</form>\n"; 
}

$rdate = "";
if ( $event_repeats ) {
  $rdate = "&amp;date=$event_date";
}

// Show a printer-friendly link
if ( empty ( $friendly ) ) {
  echo "<a title=\"" . 
    translate("Generate printer-friendly version") . "\" class=\"printer\" " .
    "href=\"view_task.php?id=$id&amp;friendly=1$rdate\" " .
    "target=\"cal_printer_friendly\">" .
    translate("Printer Friendly") . "</a><br />\n";
}

if ( empty ( $event_status ) ) {
  // this only happens when an admin views a deleted event that he is
  // not a participant for.  Set to $event_status to "D" just to get
  // rid of all the edit/delete links below.
  $event_status = "D";
}

if ( ! empty ( $user ) && $login != $user ) {
  $u_url = "&amp;user=$user";
} else {
  $u_url = "";
}

$can_edit = ( $is_admin || $is_nonuser_admin && ($user == $create_by) || 
  ( $is_assistant && ! $is_private && ($user == $create_by) ) ||
  ( $readonly != "Y" && ( $login == $create_by || $single_user == "Y" ) ) );
  
if ( ( $is_my_event || $is_nonuser_admin ) && $unapproved && $readonly == 'N' ) {
  echo "<a title=\"" . 
    translate("Approve/Confirm entry") .
    "\" class=\"nav\" href=\"approve_entry.php?id=$id$u_url&amp;type=E\" " .
    "onclick=\"return confirm('" . 
    translate("Approve this entry?", true) . "');\">" . 
    translate("Approve/Confirm entry") . "</a><br />\n";
  echo "<a title=\"" . 
    translate("Reject entry") . 
    "\" class=\"nav\" href=\"reject_entry.php?id=$id$u_url&amp;type=E\" " .
    "onclick=\"return confirm('" .
    translate("Reject this entry?", true) . "');\">" . 
    translate("Reject entry") . "</a><br />\n";
}

if ( $PUBLIC_ACCESS == "Y" && $login == "__public__" ) {
  $can_edit = false;
}
if ( $readonly == 'Y' ) {
  $can_edit = false;
}
if ( $is_nonuser )
  $can_edit = false;

// If approved, but event category not set (and user does not have permission
// to edit where they could also set the category), then allow them to
// set it through set_cat.php.
if ( empty ( $user ) && $CATEGORIES_ENABLED == "Y" &&
  $readonly != "Y" && $is_my_event && $login != "__public__" &&
  ! $is_nonuser && $event_status != "D" && ! $can_edit )  {
  echo "<a title=\"" . 
    translate("Set category") . "\" class=\"nav\" " .
    "href=\"set_entry_cat.php?id=$id$rdate&amp;type=T\">" .
    translate("Set category") . "</a><br />\n";
}

if ( $can_edit && $event_status != "D" && ! $is_nonuser ) {
  if ( $event_repeats ) {
    echo "<a title=\"" .
      translate("Edit repeating entry for all dates") . 
      "\" class=\"nav\" href=\"edit_task.php?id=$id$u_url\">" . 
      translate("Edit repeating entry for all dates") . "</a><br />\n";
    // Don't allow override of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      echo "<a title=\"" .
        translate("Edit entry for this date") . "\" class=\"nav\" " . 
        "href=\"edit_task.php?id=$id$u_url$rdate&amp;override=1\">" .
        translate("Edit entry for this date") . "</a><br />\n";
    }
    echo "<a title=\"" . 
      translate("Delete repeating event for all dates") . 
      "\" class=\"nav\" href=\"del_task.php?id=$id$u_url&amp;override=1\" " .
      "onclick=\"return confirm('" . 
      translate("Are you sure you want to delete this entry?", true) . "\\n\\n" . 
      translate("This will delete this entry for all users.", true) . "');\">" . 
      translate("Delete repeating event for all dates") . "</a><br />\n";
    // Don't allow deletion of first event
    if ( ! empty ( $date ) && $date != $orig_date ) {
      echo "<a title=\"" . 
        translate("Delete entry only for this date") . 
        "\" class=\"nav\" href=\"del_entry.php?id=$id$u_url$rdate&amp;override=1\" " .
        "onclick=\"return confirm('" .
        translate("Are you sure you want to delete this entry?", true) . "\\n\\n" . 
        translate("This will delete this entry for all users.", true) . "');\">" . 
        translate("Delete entry only for this date") . "</a><br />\n";
    }
  } else {
    echo "<a title=\"" .
      translate("Edit task") . "\" class=\"nav\" " .
      "href=\"edit_task.php?id=$id$u_url\">" .
      translate("Edit task") . "</a><br />\n";
    echo "<a title=\"" . 
      translate("Delete task") . "\" class=\"nav\" " .
      "href=\"del_entry.php?id=$id$u_url$rdate\" onclick=\"return confirm('" . 
       translate("Are you sure you want to delete this task?", true) . "\\n\\n" . 
       translate("This will delete this task for all users.", true ) . "');\">" . 
       translate("Delete task") . "</a><br />\n";
  }
  echo "<a title=\"" . 
    translate("Copy task") . "\" class=\"nav\" " .
    "href=\"edit_task.php?id=$id$u_url&amp;copy=1\">" . 
    translate("Copy task") . "</a><br />\n";  
} elseif ( $readonly != "Y" && $is_my_event && $login != "__public__" &&
  ! $is_nonuser && $event_status != "D" )  {
  echo "<a title=\"" . 
    translate("Delete task") . "\" class=\"nav\" " .
    "href=\"del_entry.php?id=$id$u_url$rdate\" onclick=\"return confirm('" . 
    translate("Are you sure you want to delete this task?", true) . "\\n\\n" . 
    translate("This will delete the task from your calendar.", true) . "');\">" . 
    translate("Delete task") . "</a><br />\n";
  echo "<a title=\"" . 
    translate("Copy task") . "\" class=\"nav\" " .
    "href=\"edit_task.php?id=$id&amp;copy=1\">" . 
    translate("Copy task") . "</a><br />\n";
}


if ( count ( $allmails ) > 0 ) {
  echo "<a title=\"" . 
    translate("Email all participants") . "\" class=\"nav\" " .
    "href=\"mailto:" . implode ( ",", $allmails ) .
    "?subject=" . rawurlencode($subject) . "\">" . 
    translate("Email all participants") . "</a><br />\n";
}

$can_show_log = $is_admin; // default if access control is not enabled
if ( access_is_enabled () ) {
  $can_show_log = access_can_access_function ( ACCESS_ACTIVITY_LOG );
}

if ( $can_show_log ) {
  if ( ! $show_log ) {
    echo "<a title=\"" . 
      translate("Show activity log") . "\" class=\"nav\" " .
      "href=\"view_task.php?id=$id&amp;log=1\">" . 
      translate("Show activity log") . "</a><br />\n";
  } else {
    echo "<a title=\"" . 
      translate("Hide activity log") . "\" class=\"nav\" " .
      "href=\"view_task.php?id=$id\">" . 
       translate("Hide activity log") . "</a><br />\n";
    $show_log = true;
  }
}

if ( $can_show_log && $show_log ) {
  echo "<h3>" . translate("Activity Log") . "</h3>\n";
  echo "<table class=\"embactlog\">\n";
  echo "<tr><th class=\"usr\">\n";
  echo translate("User") . "</th><th class=\"cal\">\n";
  echo translate("Calendar") . "</th><th class=\"date\">\n";
  echo translate("Date") . "/" . 
   translate("Time") . "</th><th class=\"action\">\n";
  echo translate("Action") . "\n</th></tr>\n";

  $res = dbi_query ( "SELECT cal_login, cal_user_cal, cal_type, " .
    "cal_date, cal_time " .
    "FROM webcal_entry_log WHERE cal_entry_id = $id " .
    "ORDER BY cal_log_id DESC" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      echo "<tr><td>\n";
      echo $row[0] . "</td><td>\n";
      echo $row[1] . "</td><td>\n" . 
        date_to_str ( $row[3] ) . "&nbsp;" .
        display_time ( $row[4], 3 ) . "</td><td>\n";
      if ( $row[2] == LOG_CREATE_T ) {
        etranslate("Task created");
      } else if ( $row[2] == LOG_APPROVE_T ) {
        etranslate("Task approved");
      } else if ( $row[2] == LOG_REJECT_T ) {
        etranslate("Task rejected");
      } else if ( $row[2] == LOG_UPDATE_T ) {
        etranslate("Task updated");
      } else if ( $row[2] == LOG_DELETE_T ) {
        etranslate("Task deleted");
      } else if ( $row[2] == LOG_NOTIFICATION ) {
        etranslate("Notification sent");
      } else if ( $row[2] == LOG_REMINDER ) {
        etranslate("Reminder sent");
      }
      echo "</td></tr>\n";
    }
    dbi_free_result ( $res );
  }
  echo "</table>\n";
}

if (! $is_private && ! $hide_details ) {
  echo "<br /><form method=\"post\" name=\"exportform\" " .
    "action=\"export_handler.php\">\n";
  echo "<label for=\"exformat\">" . 
    translate("Export this task to") . ":&nbsp;</label>\n";
  echo "<select name=\"format\" id=\"exformat\">\n";
  echo " <option value=\"ical\">iCalendar</option>\n";
  echo " <option value=\"vcal\">vCalendar</option>\n";
  echo " <option value=\"pilot-csv\">Pilot-datebook CSV (" . 
    translate("Palm Pilot") . ")</option>\n";
  echo " <option value=\"pilot-text\">Install-datebook (" . 
    translate("Palm Pilot") . ")</option>\n";
  echo "</select>\n";
  echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
  echo "<input type=\"submit\" value=\"" . 
    translate("Export") . "\" />\n";
  echo "</form>\n";
}
?>

<?php
 print_trailer ( empty ($friendly) );
?>
</body>
</html>
