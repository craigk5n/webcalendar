<?php
/*
 * $Id$
 *
 * Page Description:
 *	This page will handle the form submission from edit_report.php
 *	and either add, update or delete a report.
 *
 * Input Parameters:
 *	report_id (optional) - the report id of the report to edit.
 *	  If blank, user is adding a new report.
 *	public (optional) - If set to '1' and user is an admin user,
 *	  then we are creating a report for the public user.
 *	report_name
 *	report_user
 *	is_global (Y or N)
 *	include_header (Y or N)
 *	time_range
 *	cat_id
 *	allow_nav
 *	include_empty
 *	show_in_trailer
 *	action (if 'delete' button pressed)
 *	page_template
 *	day_template
 *	event_template
 *
 * Security:
 *	Same as in edit_report.php...
 *	If system setting $reports_enabled is set to anything other than
 *	  'Y', then don't allow access to this page.
 *	If $allow_view_other is 'N', then do not allow selection of
 *	  participants.
 *	Can only delete/edit an event if you are the creator of the event
 *	  or you are an admin user.
 */
include_once 'includes/init.php';
load_user_categories ();

$error = "";

if ( empty ( $reports_enabled ) || $reports_enabled != 'Y' ) {
  $error = translate ( "You are not authorized" ) . ".";
}

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $public_access == "Y" ) {
  $updating_public = true;
}

if ( $single_user == 'Y' || $disable_participants_field == 'Y' ) {
  $report_user = '';
}

$adding_report = ( empty ( $report_id ) || $report_id <= 0 );

// Check permissions
// Can only edit/delete if you created the event or your are an admin.
if ( empty ( $error ) && $single_user != 'N' && ! empty ( $report_id ) &&
  $report_id > 0 && ! $is_admin ) {
  $res = dbi_query ( "SELECT cal_login FROM webcal_report " .
     "WHERE report_id = $report_id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] != $login ) {
        $error = translate("You are not authorized");
      }
    } else {
      $error = "No such report id";
    }
    dbi_free_result ( $res );
  } else {
    $error = translate("Database error" ) . ": " . dbi_error ();
  }
}

// Validate templates to make sure the required variables are found.
// Page template must include ${days}
if ( empty ( $error ) ) {
  if ( ! strstr ( $page_template, '${days}' ) ) {
    $error = "<p>" . translate ( "Error" ) . " [" .
      translate ( "Page template" ) . "]: " .
      str_replace ( " N ", ' <tt>${days}</tt> ',
        translate ( "Variable N not found" ) ) .
      ".";
  }
  // Day template must include ${events}
  if ( ! strstr ( $day_template, '${events}' ) ) {
    if ( ! empty ( $error ) )
      $error .= "</p><p>";
    $error .= "<p>" . translate ( "Error" ) . " [" .
      translate ( "Day template" ) . "]: " .
      str_replace ( " N ", ' <tt>${events}</tt> ',
        translate ( "Variable N not found" ) ) .
      ".";
  }
  // Event template must include ${name}
  if ( ! strstr ( $event_template, '${name}' ) ) {
    if ( ! empty ( $error ) )
      $error .= "</p><p>";
    $error .= "<p>" . translate ( "Error" ) . " [" .
      translate ( "Event template" ) . "]: " .
      str_replace ( " N ", ' <tt>${name}</tt> ',
        translate ( "Variable N not found" ) ) .
      ".";
  }
}

if ( empty ( $error ) && ! empty ( $report_id ) &&
  ( $action == "Delete" || $action == translate ( "Delete" ) ) ) {
  if ( ! dbi_query ( "DELETE FROM webcal_report_template " .
    "WHERE cal_report_id = $report_id" ) )
    $error = translate("Database error") . ": " . dbi_error ();
  if ( empty ( $error ) &
    ! dbi_query ( "DELETE FROM webcal_report " .
    "WHERE cal_report_id = $report_id" ) )
    $error = translate("Database error") . ": " . dbi_error ();
  // send back to main report listing page
  if ( empty ( $error ) )
    do_redirect ( "report.php" );
}

if ( empty ( $error ) ) {
  $names = array ();
  $values = array ();

  $names[] = "cal_login";
  $values[] = ( $updating_public ? "'__public__'" : "'$login'" );

  $names[] .= "cal_update_date";
  $values[] = date ( "Ymd" );

  $names[] = "cal_report_name";
  if ( empty ( $report_name ) )
    $report_name = translate ( "Unnamed Report" );
  $values[] = "'$report_name'";

  $names[] = "cal_user";
  if ( ! $is_admin || empty ( $report_user ) ) {
    $values[] = "NULL";
  } else {
    $values[] = "'$report_user'";
  }

  $names[] = "cal_include_header";
  if ( empty ( $include_header ) || $include_header != 'Y' ) {
    $values[] = "'N'";
  } else {
    $values[] = "'Y'";
  }

  $names[] = "cal_time_range";
  $values[] = ( empty ( $time_range ) ? 11 : $time_range );

  $names[] = "cal_cat_id";
  $values[] = ( empty ( $cat_id ) ? "NULL" : $cat_id );

  $names[] = "cal_allow_nav";
  $values[] = ( empty ( $allow_nav ) || $allow_nav != 'Y' ) ? "'N'" : "'Y'";

  $names[] = "cal_include_empty";
  $values[] = ( empty ( $include_empty ) || $include_empty != 'Y' ) ? "'N'" : "'Y'";

  $names[] = "cal_show_in_trailer";
  $values[] = ( empty ( $show_in_trailer ) || $show_in_trailer != 'Y' ) ? "'N'" : "'Y'";

  if ( $adding_report ) {
    $res = dbi_query ( "SELECT MAX(cal_report_id) FROM webcal_report" );
    $newid = 1;
    if ( $res ) {
      if ( $row = dbi_fetch_row ( $res ) ) {
        $newid = $row[0] + 1;
      }
      dbi_free_result ( $res );
    }
    $names[] = "cal_report_id";
    $values[] = $newid;
    $sql = "INSERT INTO webcal_report ( ";
    for ( $i = 0; $i < count ( $names ); $i++ ) {
      if ( $i > 0 )
        $sql .= ", ";
      $sql .= $names[$i];
    }
    $sql .= " ) VALUES ( ";
    for ( $i = 0; $i < count ( $values ); $i++ ) {
      if ( $i > 0 )
        $sql .= ", ";
      $sql .= $values[$i];
    }
    $sql .= " )";
    $report_id = $newid;
  } else {
    $sql = "UPDATE webcal_report SET ";
    for ( $i = 0; $i < count ( $names ); $i++ ) {
      if ( $i > 0 )
        $sql .= ", ";
      $sql .= "$names[$i] = $values[$i]";
    }
    $sql .= " WHERE cal_report_id = $report_id";
  }
  //echo "SQL: $sql"; exit;
}


if ( empty ( $error ) ) {
  if ( ! dbi_query ( $sql ) ) {
    $error = translate ( "Database error" ) . ": " . dbi_error ();
  }
}

if ( empty ( $error ) ) {
  if ( ! $adding_report ) {
    if ( ! dbi_query ( "DELETE FROM webcal_report_template " .
      "WHERE cal_report_id = $report_id" ) )
      $error = translate("Database error") . ": " . dbi_error ();
  }
  if ( empty ( $error ) &&
    ! dbi_query ( "INSERT INTO webcal_report_template " .
    "( cal_report_id, cal_template_type, cal_template_text ) VALUES ( " .
    "$report_id, 'P', '$page_template' )" ) )
    $error = translate("Database error") . ": " . dbi_error ();
  if ( empty ( $error ) &&
    ! dbi_query ( "INSERT INTO webcal_report_template " .
    "( cal_report_id, cal_template_type, cal_template_text ) VALUES ( " .
    "$report_id, 'D', '$day_template' )" ) )
    $error = translate("Database error") . ": " . dbi_error ();
  if ( empty ( $error ) &&
    ! dbi_query ( "INSERT INTO webcal_report_template " .
    "( cal_report_id, cal_template_type, cal_template_text ) VALUES ( " .
    "$report_id, 'E', '$event_template' )" ) )
    $error = translate("Database error") . ": " . dbi_error ();
}

if ( empty ( $error ) ) {
  if ( $updating_public )
    do_redirect ( "report.php?public=1" );
  else
    do_redirect ( "report.php" );
  exit;
}

print_header();
?>

<h2><font color="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></h2></font>
<blockquote>
<?php echo htmlentities ( $error ); ?>
</blockquote>

<?php include_once "includes/trailer.php"; ?>

</body>
</html>
