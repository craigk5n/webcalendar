<?php
  global $allow_color_customization;
?>

<script language="JavaScript">
// error check the colors
function valid_color ( str ) {
  var ch, j;
  var valid = "0123456789abcdefABCDEF";

  if ( str.length == 0 )
    return true;

  if ( str.charAt ( 0 ) != '#' || str.length != 7 )
    return false;

  for ( j = 1; j < str.length; j++ ) {
   ch = str.charAt ( j );
   if ( valid.indexOf ( ch ) < 0 )
     return false;
  }
  return true;
}

function valid_form ( form ) {
  var err = "";
  var colorErr = false;
  <?php if ( $allow_color_customization ) { ?>
  if ( ! valid_color ( form.pref_BGCOLOR.value ) )
    err += "<?php etranslate("Invalid color for document background")?>.\n";
  if ( ! valid_color ( form.pref_H2COLOR.value ) )
    err += "<?php etranslate("Invalid color for document title")?>.\n";
  if ( ! valid_color ( form.pref_CELLBG.value ) )
    err += "<?php etranslate("Invalid color for table cell background")?>.\n";
  if ( ! valid_color ( form.pref_TODAYCELLBG.value ) )
    err += "<?php etranslate("Invalid color for table cell background for today")?>.\n";
  <?php } ?>
  if ( err.length > 0 )
    colorErr = true;
  if ( ! validWorkHours ( form ) ) {
    err += "<?php etranslate("Invalid work hours"); ?>.\n";
    err += form.pref_WORK_DAY_START_HOUR.value + " > " + form.pref_WORK_DAY_END_HOUR.value + "\n";
  }
  if ( colorErr ) {
    alert ( "Error:\n\n" + err + "\n\n<?php etranslate("Color format should be '#RRGGBB'")?>" );
    return false;
  } else if ( err.length > 0 ) {
    alert ( "Error:\n\n" + err );
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
</script>
