<?php
/*
 * $Id$
 *
 * Page Description:
 *	This page will present the HTML form to add or edit a report.
 *
 * Input Parameters:
 *	report_id (optional) - the report id of the report to edit.
 *	  If blank, user is adding a new report.
 *	public (optional) - If set to '1' and user is an admin user,
 *	  then we are creating a report for the public user.
 *
 * Security:
 *	If system setting $reports_enabled is set to anything other than
 *	  'Y', then don't allow access to this page.
 *	If $allow_view_other is 'N', then do not allow selection of
 *	  participants.
 *	If not an admin user, only report creator (cal_login in webcal_report)
 *	  can edit/delete report.
 */
include_once 'includes/init.php';
load_user_categories ();

$updating_public = false;
$error = "";

if ( empty ( $reports_enabled ) || $reports_enabled != 'Y' ) {
  $error = translate ( "You are not authorized" ) . ".";
}

if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
  $report_user = "__public__";
} else {
  $report_user = '';
}

$report_id = getIntValue ( "report_id", true );

$adding_report = false;
if ( empty ( $report_id ) ) {
  $adding_report = true;
  $report_id = -1;
  $include_header = 'Y';
  $report_is_global = 'N';
  $report_allow_nav = 'Y';
}

$show_participants = true;
if ( $single_user == 'Y' || $disable_participants_field == 'Y' )
  $show_participants = false;

if ( $login == "__public__" ) {
  $error = translate ( "You are not authorized" );
}

// Set date range options
$ranges = array (
  "0" => translate ( "Tomorrow" ),
  "1" => translate ( "Today" ),
  "2" => translate ( "Yesterday" ),
  "3" => translate ( "Day before yesterday" ),
  "10" => translate ( "Next week" ),
  "11" => translate ( "This week" ),
  "12" => translate ( "Last week" ),
  "13" => translate ( "Week before last" ),
  "20" => translate ( "Next week and week after" ),
  "21" => translate ( "This week and next week" ),
  "22" => translate ( "Last week and this week" ),
  "23" => translate ( "Last two weeks" ),
  "30" => translate ( "Next month" ),
  "31" => translate ( "This month" ),
  "32" => translate ( "Last month" ),
  "33" => translate ( "Month before last" ),
  "40" => translate ( "Next year" ),
  "41" => translate ( "This year" ),
  "42" => translate ( "Last year" ),
  "43" => translate ( "Year before last" )
);

// Get list of users that the current user can see
if ( empty ( $error ) && $show_participants ) {
  $userlist = get_my_users ();
  if ($nonuser_enabled == "Y" ) {
    $nonusers = get_nonuser_cals ();
    $userlist = ($nonuser_at_top == "Y") ? array_merge($nonusers, $userlist) : array_merge($userlist, $nonusers);
  }
}

// Default values
$page_template = '<dl>${days}</dl>';
$day_template = '<dt><b>${date}</b></dt><dd><dl>${events}</dl></dd>';
$event_template = '<dt>${name}</dt><dd>' .
  translate ( "Date" ) . ': ${date}</b><br />' .
  translate ( "Time" ) . ': ${time}</b><br />' .
  '${description}</dd>';

if ( empty ( $error ) && $report_id > 0 ) {
  $sql = "SELECT cal_login, cal_report_id, cal_is_global, " .
    "cal_report_type, cal_include_header, cal_report_name, " .
    "cal_time_range, cal_user, cal_allow_nav, cal_cat_id, " .
    "cal_include_empty, cal_show_in_trailer, cal_update_date " .
    "FROM webcal_report " .
    "WHERE cal_report_id = $report_id";
  //echo "SQL: $sql <p>";
  $res = dbi_query ( $sql );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $i = 0;
      $report_login = $row[$i++];
      $report_id = $row[$i++];
      $report_is_global = $row[$i++];
      $report_type = $row[$i++];
      $report_include_header = $row[$i++];
      $report_name = $row[$i++];
      $report_time_range = $row[$i++];
      $report_user = $row[$i++];
      $report_allow_nav = $row[$i++];
      $report_cat_id = $row[$i++];
      $report_include_empty = $row[$i++];
      $report_show_in_trailer = $row[$i++];
      $report_update_date = $row[$i++];

      // Check permissions.
      if ( $show_participants && ! empty ( $report_user ) ) {
        $user_is_in_list = false;
        for ( $i = 0; $i < count ( $userlist ); $i++ ) {
          if ( $report_user == $userlist[$i]['cal_login'] )
            $user_is_in_list = true;
        }
        if ( ! $user_is_in_list && $report_login != $login && ! $is_admin ) {
          $error = translate ( "You are not authorized" );
        }
      }
      if ( ! $is_admin && $login != $report_login ) {
        // If not admin, only creator can edit/delete the event
        $error = translate ( "You are not authorized" );
      }
    } else {
      $error = translate ( "Invalid report id" ) . ": $report_id";
    }
    dbi_free_result ( $res );
  } else {
    $error = translate("Database error") . ": " . dbi_error ();
  }
  $res = dbi_query ( "SELECT cal_template_type, cal_template_text " .
    "FROM webcal_report_template " .
    "WHERE cal_report_id = $report_id" );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] == 'P' ) {
        $page_template = $row[1];
      } else if ( $row[0] == 'D' ) {
        $day_template = $row[1];
      } else if ( $row[0] == 'E' ) {
        $event_template = $row[1];
      }
    }
    dbi_free_result ( $res );
  }
}
else {
  // default values for new report
  $report_login = $login;
  $report_id = -1;
  $report_is_global = 'N';
  $report_type = 'html';
  $report_include_header = 'Y';
  $report_name = translate("Unnamed Report");
  $report_time_range = 11; // current week
  //$report_user already set
  $report_allow_nav = 'Y';
  $report_cat_id = '';
  $report_include_empty = 'N';
  $report_show_in_trailer = 'N';
  $report_update_date = '';
}

print_header();
//echo "report_id: $report_id <br />\n";
//echo "report_name: $report_name <br />\n";
//echo "report_user: $report_user <br />\n";
?>

<h2>
<?php
if ( $updating_public )
  echo translate($PUBLIC_ACCESS_FULLNAME) . " ";
if ( $adding_report )
  etranslate("Add Report");
else
  etranslate("Edit Report");
?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo $error;
  include_once "includes/trailer.php";
  exit;
}
?>


<form action="edit_report_handler.php" method="post" name="reportform">
<?php if ( $updating_public ) { ?>
  <input type="hidden" name="public" value="1" />
<?php } ?>
<?php if ( ! $adding_report ) { ?>
  <input type="hidden" name="report_id" value="<?php echo $report_id?>" />
<?php } ?>

<table style="border-width:0px;">

<tr><td><b><?php etranslate("Report name")?>:</b></td>
  <td><input name="report_name" size="40" maxlength="50" value="<?php echo htmlentities ( $report_name ); ?>" /></td></tr>

<?php
if ( $show_participants ) {
  $users = "<option value=\"\"";
  if ( empty ( $report_user ) )
    $users .= " selected=\"selected\"";
  $users .= "> " . translate ( "Current User" );
  for ( $i = 0; $i < count ( $userlist ); $i++ ) {
    $users .= "<option value=\"" . $userlist[$i]['cal_login'] . "\"";
    if ( ! empty ( $report_user ) ) {
      if ( $report_user == $userlist[$i]['cal_login'] )
        $users .= " selected=\"selected\"";
    } 
    $users .= ">" . $userlist[$i]['cal_fullname'] . "</option>\n";
  }
  print "<tr><td style=\"vertical-align:top; font-weight:bold;\">" .
    translate("User") . ":</td>";
  print "<td><select name=\"report_user\" size=\"1\">$users\n";
  print "</select>\n";
  print "</td></tr>\n";
}
?>

<?php if ( $is_admin ) { ?>
<tr><td style="font-weight:bold;"><?php etranslate("Global")?>:</td>
  <td><input type="radio" name="is_global" value="Y"
  <?php if ( $report_is_global != 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("Yes") ?>
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="is_global" value="N"
  <?php if ( $report_is_global == 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("No") ?>
  </td></tr>
<?php } ?>

<?php
// The report will always be shown in the trailer for the creator
// of the report.  For admin users who create a global report,
// allow option of adding to all users trailer.
if ( $is_admin ) {
?>
<tr><td style="font-weight:bold;"><?php etranslate("Include link in trailer")?>:</td>
  <td><input type="radio" name="show_in_trailer" value="Y"
  <?php if ( $report_show_in_trailer != 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("Yes") ?>
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="show_in_trailer" value="N"
  <?php if ( $report_show_in_trailer == 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("No") ?>
  </td></tr>
<?php } ?>

<tr><td style="font-weight:bold;"><?php etranslate("Include standard header/trailer")?>:
  &nbsp;&nbsp;&nbsp;&nbsp;
  </td>
  <td><input type="radio" name="include_header" value="Y"
  <?php if ( $include_header != 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("Yes") ?>
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="include_header" value="N"
  <?php if ( $report_include_header == 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("No") ?>
  </td></tr>

<tr><td style="font-weight:bold;"><?php etranslate("Date range")?>:</td>
  <td><select name="time_range">
  <?php
    while ( list ( $num, $descr ) = each ( $ranges ) ) {
      echo "<option value=\"$num\"";
      if ( $report_time_range == $num )
        echo " selected=\"selected\"";
      echo ">$descr</option>\n";
    }
  ?></select></td></tr>

<tr><td style="font-weight:bold;"><?php etranslate("Category")?>:</td>
  <td><select name="cat_id">
  <option value=""><?php etranslate("None") ?></option>
  <?php
    while ( list ( $cat_id, $descr ) = each ( $categories ) ) {
      echo "<option value=\"$cat_id\"";
      if ( $report_cat_id == $cat_id )
        echo " selected=\"selected\"";
      echo ">$descr</option>\n";
    }
  ?></select></td></tr>

<tr><td style="font-weight:bold;"><?php etranslate("Include previous/next links")?>:</td>
  <td><input type="radio" name="allow_nav" value="Y"
  <?php if ( $report_allow_nav != 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("Yes") ?>
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="allow_nav" value="N"
  <?php if ( $report_allow_nav == 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("No") ?>
  </td></tr>

<tr><td style="font-weight:bold;"><?php etranslate("Include empty dates")?>:</td>
  <td><input type="radio" name="include_empty" value="Y"
  <?php if ( $report_include_empty != 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("Yes") ?>
  &nbsp;&nbsp;&nbsp;
  <input type="radio" name="include_empty" value="N"
  <?php if ( $report_include_empty == 'N' ) echo " checked=\"checked\""; ?> /> <?php etranslate("No") ?>
  </td></tr>
</table>

<table style="border-width:0px;">
<tr><td></td><td></td>
  <td style="font-weight:bold;"><?php etranslate("Template variables")?></td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Page template")?>:</td>
  <td><textarea rows="12" cols="60" wrap="virtual" name="page_template"><?php echo htmlentities ( $page_template )?></textarea></td>
 <td style="vertical-align:top;">
   <tt>${days}</tt><br />
   <tt>${report_id}</tt><br />
 </td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Day template")?>:</td>
  <td><textarea rows="12" cols="60" wrap="virtual" name="day_template"><?php echo htmlentities ( $day_template )?></textarea></td>
 <td style="vertical-align:top;">
   <tt>${events}</tt><br />
   <tt>${date}</tt><br />
   <tt>${fulldate}</tt><br />
   <tt>${report_id}</tt><br />
 </td></tr>

<tr><td style="vertical-align:top; font-weight:bold;"><?php etranslate("Event template")?>:</td>
  <td><textarea rows="12" cols="60" wrap="virtual" name="event_template"><?php echo htmlentities ( $event_template )?></textarea></td>
 <td style="vertical-align:top;">
   <tt>${name}</tt><br />
   <tt>${description}</tt><br />
   <tt>${date}</tt><br />
   <tt>${fulldate}</tt><br />
   <tt>${time}</tt><br />
   <tt>${starttime}</tt><br />
   <tt>${endtime}</tt><br />
   <tt>${duration}</tt><br />
   <tt>${priority}</tt><br />
   <tt>${href}</tt><br />
   <tt>${user}</tt><br />
   <tt>${report_id}</tt> 
 </td></tr>


<tr><td colspan="2">
<input type="submit" value="<?php etranslate("Save")?>" />

<?php if ( ! $adding_report ) { ?>

&nbsp;&nbsp;
<input type="submit" name="action" value="<?php etranslate("Delete");?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this report?")?>');" />

<?php } ?>
</td></tr>
</table>

</form>

<?php print_trailer(); ?>
</body>
</html>
