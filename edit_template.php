<?php
/*
 * $Id$
 *
 * Page Description:
 * This page will present the HTML form to edit an entry
 * in the cal_report table, and this page will also process the
 * form.
 * This is only used for editing the custom header/trailer.
 * The report_id is always 0.
 *
 * Input Parameters:
 * type - "header" or "trailer"
 *
 * Security:
 * Admin permissions are checked by the WebCalendar class.
 */
include_once 'includes/init.php';

$report_id = 0;
$error = '';

$type = getValue ( "type", "S|H|T", true );
$cur = '';
$found = $foundOld = false;

$user = '__system__';

if ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' ) {
  $user = getValue ( 'user' );
  if ( empty ( $user ) )
    $user = '__system__';
}

if ( $user == '__system__' ) {
  assert ( ( $is_admin && ! access_is_enabled () ) ||
    access_can_access_function ( ACCESS_SYSTEM_SETTINGS ) );
}

// Get existing value.
$res = dbi_query ( "SELECT cal_template_text " .
  "FROM webcal_user_template " .
  "WHERE cal_type = '$type' AND cal_login = '$user'" );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $cur = $row[0];
    $found = true;
  }
  dbi_free_result ( $res );
}

// Check the cal_template_text table since that is where we stored it
// in 1.0 and before.
if ( ! $found ) {
  $res = dbi_query ( "SELECT cal_template_text " .
    "FROM webcal_report_template " .
    "WHERE cal_template_type = '$type' AND cal_report_id = 0" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      $cur = $row[0];
      $foundOld = true;
    }
    dbi_free_result ( $res );
  }
}

if ( empty ( $REQUEST_METHOD ) )
  $REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

// Handle form submission
if ( $REQUEST_METHOD == 'POST' ) {
  // Was this a delete request?
  $delete = getPostValue ( 'delete' );
  if ( $user != '__system__' && ! empty ( $delete ) ) {
    dbi_query ( "DELETE FROM webcal_user_template " .
      "WHERE cal_type = '$type' " .
      "AND cal_login = '$user'" );
    echo "<html><body onload=\"window.close()\"></body></html>\n";
    exit;
  }
  //$template = getPostValue ( "template" );
  $template = $_POST['template'];
  //echo "Template: " .  $template  . "<br />\n"; exit;
  if ( $found ) {
    $sql = "UPDATE webcal_user_template " .
      "SET cal_template_text = '$template' " .
      "WHERE cal_type = '$type' AND cal_login = '__system__'";
  } else if ( $foundOld && $user == '__system__' ) {
    // User is upgrading from WebCalendar 1.0 to 1.1.
    // Delete from the webcal_report_template table and move the info
    // to the new webcal_user_template table.
    dbi_query ( "DELETE FROM webcal_report_template " .
      "WHERE cal_template_type = '$type' " .
      "AND cal_report_id = 0 " );
    $sql = "INSERT INTO webcal_user_template " .
      "( cal_type, cal_login, cal_template_text ) " .
      "VALUES ( '$type', '__system__', '$template' )";
  } else {
    $sql = "INSERT INTO webcal_user_template " .
      "( cal_type, cal_login, cal_template_text ) " .
      "VALUES ( '$type', '$user', '$template' )";
  }
  if ( ! dbi_query ( $sql ) ) {
    $error = translate("Database error") . ": " . dbi_error ();
  } else {
    //echo "SQL: $sql <br />\n";
    echo "<html>\n<head>\n</head>\n<body onload=\"window.close();\">\nDone</body>\n</html>";
    exit;
  }
}

print_header( '', '', '', true );
//echo "report_id: $report_id <br />\n";
//echo "report_name: $report_name <br />\n";
//echo "report_user: $report_user <br />\n";
?>

<h2><?php
if ( $type == 'S' )
  etranslate("Edit Custom Script/Stylesheet");
else if ( $type == 'H' )
  etranslate("Edit Custom Header");
else
  etranslate("Edit Custom Trailer");
if ( $user != '__system__' ) {
  user_load_variables ( $user, 'temp_' );
  echo ' [' . $temp_fullname . ']';
}
?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo "<h2>" . translate("Error") . "</h2>\n" .
    $error . "\n";
} else {
?>
<form action="edit_template.php" method="post" name="reportform">

<input type="hidden" name="type" value="<?php echo $type;?>" />
<?php
 if ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' &&
   ! empty ( $user ) && $user != '__system__' ) {
   echo '<input type="hidden" name="user" value="' . $user . "\">\n";
 }
?>
<textarea rows="15" cols="60" name="template"><?php echo htmlspecialchars ( $cur )?></textarea>

<br />
<input type="button" value="<?php etranslate("Cancel")?>" onclick="window.close();" />
<input name="action" type="submit" value="<?php etranslate("Save")?>" />

<?php if ( ! empty ( $user ) ) { ?>
  <input name="delete" type="submit" value="<?php etranslate("Delete")?>"
  onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?", true);?>');" />
<?php } ?>

</form>

<?php }
 print_trailer ( false, true, true );
?>
</body>
</html>
