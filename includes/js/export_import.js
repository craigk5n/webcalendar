// $Id$

function toggle_import() {
  var i = document.importform.ImportType.selectedIndex;

  toggleVisible('ivcal', (i == 3 ? 'hidden' : 'visible'));
  toggleVisible('outlookcsv', (i == 3 ? 'visible' : 'hidden'));
  toggleVisible('palm', (i == 1 ? 'visible' : 'hidden'));
}
function toggel_catfilter() { // ICAL
  toggleVisible('catfilter',
    (document.exportform.format.selectedIndex == 0 ? 'visible' : 'hidden'));
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
