<?php
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
function toggle_import() {
  var i = $('#importtype')[0].selectedIndex;
  if ( i == 1 ) { //Palm
    $('#palm').show();
  } else {
    $('#palm').hide();
  }
  if ( i == 3 ) {// Outlook CSV
    $('#ivcal').hide();
    $('#outlookcsv').show();
  } else {
    $('#ivcal').show();
    $('#outlookcsv').hide();
  }
}

function checkExtension() {
  var type = $('#importtype')[0].selectedIndex;
  var filename = $('input[type=file]').val()
  if ( filename == '' ) {
    alert('<?php etranslate('You must select a file to import');?>.');
    return false;
  }
  var extension = filename.substr ( filename.length -3, 3 );
  extension = extension.toLowerCase();
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
    case 4: // git log
      pass = true;
      break;
    default:
      pass = false;
      break;
  }
  if ( ! pass ) {
    alert ( "<?php etranslate ( 'File type does not match Import Format', true ) ?>" +
      ". (" + extension + ")");
    return false;
  }
  return true;
}

