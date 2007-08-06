<?php
/* $Id$
 *
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

$cur = $error = '';
$found = $foundOld = false;
$report_id = 0;
$type = $WC->getValue ( 'type', 'H|S|T', true );
$user = WC__SYSTEM__;

if ( getPref ( 'ALLOW_USER_HEADER' ) ) {
  $user = $WC->userID();
  if ( empty ( $user ) )
    $user = WC__SYSTEM__;
}

if ( $user == WC__SYSTEM__ )
  assert ( 'access_can_access_function ( ACCESS_SYSTEM_SETTINGS )' );

// Get existing value.
$res = dbi_execute ( 'SELECT cal_template_text FROM webcal_user_template
  WHERE cal_type = ? AND cal_login_id = ?', array ( $type, $user ) );
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
  $res = dbi_execute ( 'SELECT cal_template_text FROM webcal_report_template
    WHERE cal_template_type = ? AND cal_report_id = 0', array ( $type ) );
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
  $delete = $WC->getPOST ( 'delete' );
  if ( $user != WC__SYSTEM__ && ! empty ( $delete ) ) {
    dbi_execute ( 'DELETE FROM webcal_user_template WHERE cal_type = ?
      AND cal_login_id = ?', array ( $type, $user ) );
    echo '<html><body onload="window.close();"></body></html>';
    exit;
  }

  $query_params = array ();
  $query_params[] = $WC->getPOST ( 'template' );
  $query_params[] = $type;
  $query_params[] = $user;

  if ( $found )
    $sql = 'UPDATE webcal_user_template SET cal_template_text = ?
      WHERE cal_type = ? AND cal_login_id = ?';
  else {
    $sql = 'INSERT INTO webcal_user_template ( cal_template_text, cal_type,
    cal_login_id ) VALUES ( ?, ?, ? )';

    if ( $foundOld && $user == WC__SYSTEM__ )
      // User is upgrading from WebCalendar 1.0 to 1.1.
      // Delete from the webcal_report_template table and move the info
      // to the new webcal_user_template table.
      dbi_execute ( 'DELETE FROM webcal_report_template
        WHERE cal_template_type = ? AND cal_report_id = 0 ', array ( $type ) );
  }
  if ( ! dbi_execute ( $sql, $query_params ) )
    $error = db_error ();
  else {
    // echo "SQL: $sql <br />\n";
    echo '<html>
  <head></head>
  <body onload="window.close ();">
    Done
  </body>
</html>';
    exit;
  }
}

build_header ( '', '', '', 29 );
/*
 echo 'report_id: ' . $report_id . '<br />
report_name: ' . $report_name . '<br />
report_user: ' . $report_user . '<br />
';
*/
echo '
    <h2>';
if ( $type == 'H' )
  etranslate( 'Edit Custom Header' );
elseif ( $type == 'S' )
  etranslate( 'Edit Custom Script/Stylesheet' );
else
  etranslate( 'Edit Custom Trailer' );

if ( $user != WC__SYSTEM__ ) {
  $WC->User->loadVariables ( $user, 'temp_' );
  echo '<br />[' . $WC->User->_uservar['temp_'][$user]['fullname'] . ']';
} else {
 echo '<br />[' . translate ( 'Site Wide Setting' ) . ']';
}

echo '</h2>' . ( ! empty ( $error ) ? print_error ( $error ) : '
    <form action="edit_template.php" method="post" name="reportform">
      <input type="hidden" name="type" value="' . $type . '" />'
   . ( getPref ( 'ALLOW_USER_HEADER' ) && !
    empty ( $user ) && $user != WC__SYSTEM__ ? '
      <input type="hidden" name="user" value="' . $user . '" />' : '' ) . '
      <textarea rows="15" cols="60" name="template">' . htmlspecialchars ( $cur )
   . '</textarea><br />
      <input type="button" value="' . translate ( 'Cancel' )
   . '" onclick="window.close();" />
      <input name="action" type="submit" value="' . translate ( 'Save' ) . '" />'
   . ( $WC->user() ? '
      <input name="delete" type="submit" value="' . translate ( 'Delete' )
     . '" onclick="return confirm( \''
     . str_replace ( 'XXX', translate ( 'entry' ),
      translate ( 'Are you sure you want to delete this XXX?' ) ) . '\');" />'
    : '' ) . '
    </form>' ) . "\n";

?>
