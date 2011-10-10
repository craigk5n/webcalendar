<?php // $Id$
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

  global $ALLOW_COLOR_CUSTOMIZATION;
?>

function valid_form ( form ) {
  var err = '';
  var colorErr = false;
  <?php if ( $ALLOW_COLOR_CUSTOMIZATION ) { ?>
  if ( ! valid_color ( form.pref_BGCOLOR.value ) )
    err += xlate['formatColorRGB']; // translate ( 'Invalid doc BG color' )
  if ( ! valid_color ( form.pref_H2COLOR.value ) )
    err += xlate['invalidTitleFG']; // translate ( 'Invalid doc title color' )
  if ( ! valid_color ( form.pref_CELLBG.value ) )
    err += xlate['invalidCellBG'];  // translate ( 'Invalid table cell BG color' )
  if ( ! valid_color ( form.pref_TODAYCELLBG.value ) )
    err += xlate['invalidTodayBG']; // translate ( 'Invalid table cell today BG' )
  <?php } ?>
  if ( err.length > 0 )
    colorErr = true;
  if ( ! validWorkHours ( form ) ) {
    err += xlate['invalidHours'] // translate ( 'Invalid work hours.' )
      + form.pref_WORK_DAY_START_HOUR.value + ' > '
      + form.pref_WORK_DAY_END_HOUR.value + "\n";
  }
  if ( colorErr ) {
    alert ( xlate['errorXXX'].replace(/XXX/, err) // translate ( 'Error XXX' )
     + "\n\n" + xlate['formatColorRGB']; // translate ( 'Color format should be RGB' )
    return false;
  } else if ( err.length > 0 ) {
    alert ( xlate['errorXXX'].replace(/XXX/, err);
    return false;
  }
  return true;
}

function validWorkHours ( form ) {
  return ( parseInt ( form.pref_WORK_DAY_START_HOUR.value ) <
    parseInt ( form.pref_WORK_DAY_END_HOUR.value ) );
}

function showPreview() {
  var theme = document.forms['prefform'].pref_THEME.value;
  if (theme == 'none' ) return false;
  url = 'themes/' + theme.toLowerCase()  + '_pref.php';
  var previewWindow = window.open (url,'Preview','resizable=yes,scrollbars=yes');
}

function setTab( tab ) {
  document.forms['prefform'].currenttab.value = tab;
  showTab(tab);
  return false;
}



<?php //see the showTab function in includes/js/visible.js for common code shared by all pages
 //using the tabbed GUI.
?>var tabs = ['',
  'settings',
  'themes',
  'email',
  'boss',
  'subscribe',
  'header',
  'colors',
];
