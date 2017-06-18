<?php /* $Id: export_import.php,v 1.15.2.2 2007/08/06 02:28:27 cknudsen Exp $ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
function toggle_import () {
    var i = document.importform.ImportType.selectedIndex;
  if ( i == 1 ) { //Palm
      makeVisible ( "palm" );
   } else {
      makeInvisible ( "palm" );
   }
  if ( i == 3 ) {// Outlook CSV
      makeInvisible ( "ivcal" );
      makeVisible ( "outlookcsv" );
   } else {
      makeVisible ( "ivcal" );
      makeInvisible ( "outlookcsv" );
   }
}

function toggel_catfilter () {
  var i = document.exportform.format.selectedIndex;
  if ( i == 0 ) { //ICAL
      makeVisible ( "catfilter" );
   } else {
      makeInvisible ( "catfilter" );
   }

}

function checkExtension () {
  var type = document.importform.ImportType.selectedIndex;
  var filename = document.importform.FileName.value;
  var extension = filename.substr ( filename.length -3, 3 );
  extension = extension.toLowerCase ();
  var pass = true;
  switch  ( type ) {
    case 0:
      pass = ( extension == 'ics' );
      break;
    case 1:
      pass = ( extension == 'dat' );
      break;
    case 2:
      pass = ( extension == 'vcs' );
      break;
    case 3:
      pass = ( extension == 'csv' );
      break;
    default:
      pass = false;
      break;
  }
  if ( ! pass ) {
    alert ( "<?php etranslate ( 'File type does not match Import Format', true ) ?>");
    return false;
  }
  return true;
}

