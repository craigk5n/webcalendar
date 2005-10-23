<?php
/*
 * $Id: 
 *
 * Description:
 * Loads appropriate import file parser and processes the data returned
 *    Currently supported:
 *      Palmdesktop (dba file)
 *      iCal (ics file)
 *      vCal (vcs file)
 *
 *
 * Notes:
 * User defined inport routines may be used, see example
 *    in the SWITCH statement below
 *
 * Input parameters:
 * FileName: File name specified by user on import.php
 *    calUser: user's calendar to import data into, unless
 *      single user = Y or Admin, caluser will equal logged
 *      in user.
 *    exc_private: exclude private records from Palmdesktop import
 *    overwrite: Overwrite previous import 
 *
 * Security:
 * TBD
 */
include_once 'includes/init.php';
include_once 'includes/xcal.php';
$error = '';
print_header();

$overwrite = getValue("overwrite");
$doOverwrite = ( empty ( $overwrite ) || $overwrite != 'Y' ) ? false : true;
$numDeleted = 0;

$sqlLog = '';

if ( ! empty ( $_FILES['FileName'] ) ) {
  $file = $_FILES['FileName'];
} else if ( ! empty ( $HTTP_POST_FILES['FileName'] ) ) {
  $file = $HTTP_POST_FILES['FileName'];
}

if ( empty ( $file ) ) {
  echo "No file!<br />";
}

// Handle user
$calUser = getValue ( "calUser" );
if ( ! empty ( $calUser ) ) {
  if ( $single_user == "N" && ! $is_admin ) $calUser = $login;
} else {
  $calUser = $login;
}

if ($file['size'] > 0) {
  switch ($ImportType) {

// ADD New modules here:

//    case 'MODULE':
//      include "import_module.php";
//      $data = parse_module($HTTP_POST_FILES['FileName']['tmp_name']);
//      break;
//

    case 'PALMDESKTOP':
      include "import_palmdesktop.php";
      if (delete_palm_events($login) != 1) $errormsg = "Error deleting palm events from webcalendar.";
      $data = parse_palmdesktop($file['tmp_name'], $exc_private);
      $type = 'palm';
      break;

    case 'VCAL':
      $data = parse_vcal($file['tmp_name']);
      $type = 'vcal';
      break;

    case 'ICAL':
      $data = parse_ical($file['tmp_name']);
      $type = 'ical';
      break;

    case 'OUTLOOKCSV':
      include "import_outlookcsv.php";
      $data = parse_outlookcsv($file['tmp_name']);
      $type = 'outlookcsv';
      break;

  }

  $count_con = $count_suc = $error_num = 0;
  if (! empty ($data) && empty ($errormsg) ) {
    import_data ( $data, $doOverwrite, $type );
    echo "<p>" . translate("Import Results") . "</p>\n<br /><br />\n" .
      translate("Events successfully imported") . ": $count_suc<br />\n";
    echo translate("Events from prior import marked as deleted") . ": $numDeleted<br />\n";
    if ( empty ( $ALLOW_CONFLICTS ) ) {
      echo translate("Conflicting events") . ": " . $count_con . "<br />\n";
    }
    echo translate ( "Errors" ) . ": $error_num<br><br>\n";
  } elseif (! empty ( $errormsg ) ) {
    echo "<br /><br />\n<b>" . translate("Error") . ":</b> $errormsg<br />\n";
  } else {
    echo "<br /><br />\n<b>" . translate("Error") . ":</b> " .
      translate("There was an error parsing the import file or no events were returned") .
      ".<br />\n";
  }
} else {
 echo "<br /><br />\n<b>" . translate("Error") . ":</b> " .
    translate("The import file contained no data") . ".<br />\n";
}


//echo "<hr />$sqlLog\n";

print_trailer ();
echo "</body>\n</html>";

?>
