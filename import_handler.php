<?php
/* $Id: import_handler.php,v 1.40.2.2 2012/02/28 02:07:45 cknudsen Exp $
 *
 * Description:
 * Loads appropriate import file parser and processes the data returned.
 *    Currently supported:
 *      Palmdesktop (dba file)
 *      iCal (ics file)
 *      vCal (vcs file)
 *
 *
 * Notes:
 * User defined import routines may be used, see example
 * in the SWITCH statement below.
 *
 * Input parameters:
 *   FileName:    File name specified by user on import.php user's calendar to
 *                import data into, unless single user = Y or Admin,
 *                caluser will equal logged in user.
 *   exc_private: Exclude private records from Palmdesktop import.
 *   overwrite:   Overwrite previous import.
 *
 * Security:
 * TBD
 */
include_once 'includes/init.php';
include_once 'includes/xcal.php';
require_valide_referring_url ();
$error = $sqlLog = '';
print_header ();

$overwrite = getValue ( 'overwrite' );
$doOverwrite = ( empty ( $overwrite ) || $overwrite != 'Y' ) ? false : true;
$numDeleted = 0;

if ( ! empty ( $_FILES['FileName'] ) )
  $file = $_FILES['FileName'];

if ( empty ( $file ) )
  echo translate ( 'No file' ) . '!<br />';

// Handle user
$calUser = getValue ( 'calUser' );
if ( ! empty ( $calUser ) ) {
  if ( $single_user == 'N' && ! $is_admin )
    $calUser = $login;
} else
  $calUser = $login;

$ImportType = getValue ( 'ImportType' );
$exc_private = getValue ( 'exc_private' );
$overwrite = getValue ( 'overwrite' );
if ( $file['size'] > 0 ) {
  switch ( $ImportType ) {

    // ADD New modules here:
/*
    case 'MODULE':
      include "import_module.php";
      $data = parse_module ( $_FILES['FileName']['tmp_name'] );
      break;
*/

    case 'PALMDESKTOP':
      include 'import_palmdesktop.php';
      if ( delete_palm_events ( $login ) != 1 )
        $errormsg = translate ( 'Error deleting palm events from webcalendar.' );
      $data = parse_palmdesktop ( $file['tmp_name'], $exc_private );
      $type = 'palm';
      break;

    case 'VCAL':
      $data = parse_vcal ( $file['tmp_name'] );
      $type = 'vcal';
      break;

    case 'ICAL':
      $data = parse_ical ( $file['tmp_name'] );
      $type = 'ical';
      break;

    case 'OUTLOOKCSV':
      include 'import_outlookcsv.php';
      $data = parse_outlookcsv ( $file['tmp_name'] );
      $type = 'outlookcsv';
      break;
  }
  $count_con = $count_suc = $error_num = 0;
  if ( ! empty ( $data ) && empty ( $errormsg ) ) {
    import_data ( $data, $doOverwrite, $type );
    echo '
    <p>' . translate ( 'Import Results' ) . '</p><br /><br />
    ' . translate ( 'Events successfully imported' ) . ': ' . $count_suc
     . '<br />
    ' . translate ( 'Events from prior import marked as deleted' ) . ': '
     . $numDeleted . '<br />
    ' . ( empty ( $ALLOW_CONFLICTS )
      ? translate ( 'Conflicting events' ) . ': ' . $count_con . '<br />
    ' : '' ) . translate ( 'Errors' ) . ': ' . $error_num . '<br /><br />';
  } elseif ( ! empty ( $errormsg ) )
    echo '
    <br /><br />
    <b>' . translate ( 'Error' ) . ':</b> ' . $errormsg . '<br />';
  else
    echo '
    <br /><br />
    <b>' . translate ( 'Error' ) . ':</b> '
     . translate ( 'There was an error parsing the import file or no events were returned' )
     . '.<br />';
} else
  echo '
    <br /><br />
    <b>' . translate ( 'Error' ) . ':</b> '
   . translate ( 'The import file contained no data' ) . '.<br />';
// echo "<hr />$sqlLog\n";
echo print_trailer ();

?>
