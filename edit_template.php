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

$type = getValue ( 'type', "S|H|T", true );
$cur = '';
$found = $foundOld = false;

$user = '__system__';

if ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' ) {
  $user = getValue ( 'user' );
  if ( empty ( $user ) )
    $user = '__system__';
}

if ( $user == '__system__' ) {
  assert ( '($is_admin && ! access_is_enabled () ) ||
    access_can_access_function ( ACCESS_SYSTEM_SETTINGS )' );
}

// Get existing value.
$res = dbi_execute ( 'SELECT cal_template_text ' .
  'FROM webcal_user_template ' .
  'WHERE cal_type = ? AND cal_login = ?', array( $type, $user ) );
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
  $res = dbi_execute ( 'SELECT cal_template_text ' .
    'FROM webcal_report_template ' .
    'WHERE cal_template_type = ? AND cal_report_id = 0', array( $type ) );
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
    dbi_execute ( 'DELETE FROM webcal_user_template ' .
      'WHERE cal_type = ? ' .
      'AND cal_login = ?', array( $type, $user ) );
    echo "<html><body onload=\"window.close();\"></body></html>\n";
    exit;
  }
  $template = getPostValue ( 'template' );
  //echo "Template: " .  $template  . "<br />\n"; exit;
  $query_params = array();
  if ( $found ) {
    $sql = 'UPDATE webcal_user_template ' .
      'SET cal_template_text = ? ' .
      'WHERE cal_type = ? AND cal_login = ?';
    $query_params[] = $template;
    $query_params[] = $type;
    $query_params[] = $user;
  } else if ( $foundOld && $user == '__system__' ) {
    // User is upgrading from WebCalendar 1.0 to 1.1.
    // Delete from the webcal_report_template table and move the info
    // to the new webcal_user_template table.
    dbi_execute ( 'DELETE FROM webcal_report_template ' .
      'WHERE cal_template_type = ? ' .
      'AND cal_report_id = 0 ', array( $type ) );
    $sql = 'INSERT INTO webcal_user_template ' .
      '( cal_type, cal_login, cal_template_text ) ' .
      'VALUES ( ?, ?, ? )';
    $query_params[] = $type;
    $query_params[] = '__system__';
    $query_params[] = $template;
  } else {
    $sql = 'INSERT INTO webcal_user_template ' .
      '( cal_type, cal_login, cal_template_text ) ' .
      'VALUES ( ?, ?, ? )';
  $query_params[] = $type;
    $query_params[] = $user;
    $query_params[] = $template;
  }
  if ( ! dbi_execute ( $sql, $query_params ) ) {
    $error = db_error ();
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
  etranslate( 'Edit Custom Script/Stylesheet' );
else if ( $type == 'H' )
  etranslate( 'Edit Custom Header' );
else
  etranslate( 'Edit Custom Trailer' );
if ( $user != '__system__' ) {
  user_load_variables ( $user, 'temp_' );
  echo ' [' . $temp_fullname . ']';
}
?></h2>

<?php
if ( ! empty ( $error ) ) {
  echo print_error ( $error );
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
<input type="button" value="<?php etranslate( 'Cancel' )?>" onclick="window.close();" />
<input name="action" type="submit" value="<?php etranslate( 'Save' )?>" />

<?php if ( ! empty ( $user ) ) { ?>
  <input name="delete" type="submit" value="<?php 
  etranslate( 'Delete' )?>" onclick="return confirm('<?php 
  str_replace ( 'XXX', $translations['entry'], $translations['Are you sure you want to delete this XXX?'] );?>');" />
<?php } ?>

</form>

<?php }
 echo print_trailer ( false, true, true );
?>

