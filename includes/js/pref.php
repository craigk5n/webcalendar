<?php
  global $ALLOW_COLOR_CUSTOMIZATION;
?>
<script type="text/javascript">
<!-- <![CDATA[
// error check the colors
function valid_color ( str ) {
 var validColor = /^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/;

 return validColor.test ( str );
}

function valid_form ( form ) {
  var err = "";
  var colorErr = false;
  <?php if ( $ALLOW_COLOR_CUSTOMIZATION ) { ?>
  if ( ! valid_color ( form.pref_BGCOLOR.value ) )
    err += "<?php etranslate("Invalid color for document background", true)?>.\n";
  if ( ! valid_color ( form.pref_H2COLOR.value ) )
    err += "<?php etranslate("Invalid color for document title", true)?>.\n";
  if ( ! valid_color ( form.pref_CELLBG.value ) )
    err += "<?php etranslate("Invalid color for table cell background", true)?>.\n";
  if ( ! valid_color ( form.pref_TODAYCELLBG.value ) )
    err += "<?php etranslate("Invalid color for table cell background for today", true)?>.\n";
  <?php } ?>
  if ( err.length > 0 )
    colorErr = true;
  if ( ! validWorkHours ( form ) ) {
    err += "<?php etranslate("Invalid work hours", true); ?>.\n";
    err += form.pref_WORK_DAY_START_HOUR.value + " > " + form.pref_WORK_DAY_END_HOUR.value + "\n";
  }
  if ( colorErr ) {
    alert ( "<?php etranslate("Error", true) ?>:\n\n" + err + "\n\n<?php etranslate("Color format should be '#RRGGBB'", true)?>" );
    return false;
  } else if ( err.length > 0 ) {
    alert ( "<?php etranslate("Error", true) ?>:\n\n" + err );
    return false;
  }
  return true;
}

function validWorkHours ( form ) {
  return ( parseInt ( form.pref_WORK_DAY_START_HOUR.value ) <
    parseInt ( form.pref_WORK_DAY_END_HOUR.value ) );
}

function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}

// Updates the background-color of a table cell
// Parameters:
//    input - <input> element containing the new color value
// Note: this function relies on the following structure:
//   <td><input onkeyup="updateColor(this);" /></td>
//   <td>(this is the cell to be updated)</td>
function updateColor ( input ) {
 // The cell to be updated
 var colorCell = input.parentNode.nextSibling;
 // The new color
 var color = input.value;

 if (!valid_color ( color ) ) {
   // Color specified is invalid; use black instead
  colorCell.style.backgroundColor = "#000000";
 } else {
  colorCell.style.backgroundColor = color;
 }
}

function showPreview() {
  var theme = document.forms['prefform'].pref_THEME.value;
  if (theme == 'none' ) return false;
  url = "themes/" + theme.toLowerCase()  + "_pref.php";
  var previewWindow = window.open(url,"Preview","resizable=yes,scrollbars=yes");
}

<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
 //using the tabbed GUI.
?>var tabs = new Array();
tabs[1] = "settings";
tabs[2] = "themes";
tabs[3] = "email";
tabs[4] = "boss";
tabs[5] = "subscribe";
tabs[6] = "header";
tabs[7] = "colors";

//]]> -->
</script>
