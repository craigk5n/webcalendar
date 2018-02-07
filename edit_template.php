<?php
/**
 * Page Description:
 * This page will present the HTML form to edit an entry in the cal_report table,
 * and this page will also process the form.
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
require_valid_referring_url ();

$cur = $error = '';
$found = $foundOld = false;
$report_id = 0;
$type = getValue ( 'type', 'H|S|T', true );
$user = '__system__';

if ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' ) {
  $user = getValue ( 'user' );
  if ( empty ( $user ) )
    $user = '__system__';
}

if ( $user == '__system__' )
  assert ( '($is_admin && ! access_is_enabled() ) ||
    access_can_access_function ( ACCESS_SYSTEM_SETTINGS )' );

// Get existing value.
$res = dbi_execute ( 'SELECT cal_template_text FROM webcal_user_template
  WHERE cal_type = ?
    AND cal_login = ?', [$type, $user] );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $cur = $row[0];
    $found = true;
  }
  dbi_free_result ( $res );
}

// Check the cal_template_text table
// since that is where we stored it in 1.0 and before.
if ( ! $found ) {
  $res = dbi_execute ( 'SELECT cal_template_text FROM webcal_report_template
  WHERE cal_template_type = ?
    AND cal_report_id = 0', [$type] );
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

// Handle form submission.
if ( $REQUEST_METHOD == 'POST' ) {
  // Was this a delete request?
  $delete = getPostValue ( 'delete' );
  if ( $user != '__system__' && ! empty ( $delete ) ) {
    dbi_execute ( 'DELETE FROM webcal_user_template WHERE cal_type = ?
    AND cal_login = ?', [$type, $user] );
    echo '<html><body onload="window.close();"></body></html>';
    exit;
  }

  $query_params = [getPostValue ( 'template' ), $type, $user];

  if ( $found )
    $sql = 'UPDATE webcal_user_template SET cal_template_text = ?
      WHERE cal_type = ? AND cal_login = ?';
  else {
    $sql = 'INSERT INTO webcal_user_template ( cal_template_text, cal_type,
      cal_login ) VALUES ( ?, ?, ? )';

    if ( $foundOld && $user == '__system__' )
      // User is upgrading from WebCalendar 1.0 to 1.1.
      // Delete from the webcal_report_template table and move the info
      // to the new webcal_user_template table.
      dbi_execute ( 'DELETE FROM webcal_report_template
  WHERE cal_template_type = ?
    AND cal_report_id = 0 ', [$type] );
  }
  if ( ! dbi_execute ( $sql, $query_params ) )
    $error = db_error();
  else {
    echo '<html>
  <head></head>
  <body onload="window.close();">
    Done
  </body>
</html>';
    exit;
  }
}

print_header ( '', '', '', true );
/*
echo 'report_id: ' . $report_id . '<br />
report_name: ' . $report_name . '<br />
report_user: ' . $report_user . '<br />
';
*/
echo '
    <h2>';
if ( $type == 'H' )
  etranslate ( 'Edit Custom Header' );
elseif ( $type == 'S' )
  etranslate ( 'Edit Custom Script/Stylesheet' );
else
  etranslate ( 'Edit Custom Trailer' );

if ( $user != '__system__' ) {
  user_load_variables ( $user, 'temp_' );
  echo ' [' . $temp_fullname . ']';
}

echo '</h2>' . ( ! empty ( $error ) ? print_error ( $error ) : '
    <form action="edit_template.php" method="post" name="reportform">
      <input type="hidden" name="type" value="' . $type . '" />'
   . ( ! empty ( $ALLOW_USER_HEADER ) && $ALLOW_USER_HEADER == 'Y' && !
    empty ( $user ) && $user != '__system__' ? '
      <input type="hidden" name="user" value="' . $user . '" />' : '' ) . '
      <textarea rows="15" cols="60" name="template">' . htmlspecialchars ( $cur )
   . '</textarea><br />
      <input type="button" value="' . translate ( 'Cancel' )
   . '" onclick="window.close();" />
      <input name="action" type="submit" value="' . translate ( 'Save' ) . '" />'
   . ( ! empty ( $user ) ? '
      <input name="delete" type="submit" value="' . translate ( 'Delete' )
     . '" onclick="return confirm( \''
     . translate( 'Are you sure you want to delete this entry?' ) . '\');" />'
    : '' ) . '
    </form>' ) . "\n" . print_trailer ( false, true, true );

?>
