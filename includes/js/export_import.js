// $Id$

function toggle_import() {
  var i = document.importform.ImportType.selectedIndex;

  if (i == 1) { // Palm
    makeVisible('palm');
  } else {
    makeInvisible('palm');
  }
  if (i == 3) { // Outlook CSV
    makeInvisible('ivcal');
    makeVisible('outlookcsv');
  } else {
    makeVisible('ivcal');
    makeInvisible('outlookcsv');
  }
}
function toggel_catfilter() {
  if (document.exportform.format.selectedIndex == 0) { // ICAL
    makeVisible('catfilter');
  } else {
    makeInvisible('catfilter');
  }
}
function checkExtension() {
  var filename = document.importform.FileName.value;
  var extension = filename.substr(filename.length - 3, 3).toLowerCase();
  var pass = true;

  switch (document.importform.ImportType.selectedIndex) {
  case 0:
    pass = (extension == 'ics');
    break;
  case 1:
    pass = (extension == 'dat');
    break;
  case 2:
    pass = (extension == 'vcs');
    break;
  case 3:
    pass = (extension == 'csv');
    break;
  default:
    pass = false;
    break;
  }
  if (!pass) {
    alert(xlate['noMatchImport']); // translate( 'Import Format type mismatch' )
    return false;
  }
  return true;
}
