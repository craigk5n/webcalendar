/* $Id$ */

initPhpVars( 'export_import' );

function toggle_import() {
  var i = document.importform.ImportType.selectedIndex;
  $('palm').showIf( i == 1 );
	$('ivcal').showIf( i != 3 );
	$('outlookcsv').showIf( i == 3 );
}

function toggel_catfilter() {
  var i = document.exportform.format.selectedIndex;
	$('catfilter').showIf( i == 0 );
}

function checkExtension () {
  var type = document.importform.ImportType.selectedIndex;
  var filename = document.importform.FileName.value;
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
    default:
      pass = false;
      break;
  } 
  if (  ! pass ) {
    alert ( fileType );
    return false;
  }
  return true;
}

