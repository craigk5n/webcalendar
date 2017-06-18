<?php /* $Id: pref.php,v 1.31.2.5 2008/02/19 01:58:45 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

  global $ALLOW_COLOR_CUSTOMIZATION;
?>

function valid_form ( form ) {
  var err = "";
  var colorErr = false;
  <?php if ( $ALLOW_COLOR_CUSTOMIZATION ) { ?>
  if ( ! valid_color ( form.pref_BGCOLOR.value ) )
    err += "<?php etranslate ( 'Invalid color for document background.', true)?>.\n";
  if ( ! valid_color ( form.pref_H2COLOR.value ) )
    err += "<?php etranslate ( 'Invalid color for document title.', true)?>.\n";
  if ( ! valid_color ( form.pref_CELLBG.value ) )
    err += "<?php etranslate ( 'Invalid color for table cell background.', true)?>.\n";
  if ( ! valid_color ( form.pref_TODAYCELLBG.value ) )
    err += "<?php etranslate ( 'Invalid color for table cell background for today.', true)?>.\n";
  <?php } ?>
  if ( err.length > 0 )
    colorErr = true;
  if ( ! validWorkHours ( form ) ) {
    err += "<?php etranslate ( 'Invalid work hours.', true); ?>.\n";
    err += form.pref_WORK_DAY_START_HOUR.value + " > " + form.pref_WORK_DAY_END_HOUR.value + "\n";
  }
  if ( colorErr ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + err + "\n\n<?php
  etranslate ( 'Color format should be RRGGBB.', true)?>" );
    return false;
  } else if ( err.length > 0 ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + err );
    return false;
  }
  return true;
}

function validWorkHours ( form ) {
  return ( parseInt ( form.pref_WORK_DAY_START_HOUR.value ) <
    parseInt ( form.pref_WORK_DAY_END_HOUR.value ) );
}

function showPreview () {
  var theme = document.forms['prefform'].pref_THEME.value;
  if (theme == 'none' ) return false;
  url = "themes/" + theme.toLowerCase ()  + "_pref.php";
  var previewWindow = window.open (url,"Preview","resizable=yes,scrollbars=yes");
}

function setTab( tab ) {
  document.forms['prefform'].currenttab.value = tab;
  showTab(tab);
  return false;
}



<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>var tabs = new Array ();
tabs[1] = "settings";
tabs[2] = "themes";
tabs[3] = "email";
tabs[4] = "boss";
tabs[5] = "subscribe";
tabs[6] = "header";
tabs[7] = "colors";

