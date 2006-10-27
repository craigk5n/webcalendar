<?php /* $Id$  */ 
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );
?>
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
  if ( ! valid_color ( form.layercolor.value ) )
    err += "<?php etranslate( 'Invalid color', true)?>.\n";

  if ( err.length > 0 ) {
    alert ( "<?php etranslate( 'Error', true) ?>:\n\n" + err + "\n\n<?php 
  etranslate( 'Color format should be &#39;#RRGGBB&#39;', true)?>" );
    return false;
  }
  return true;
}

function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}

function show_others () {
 var ismine = document.prefform.is_mine.checked;
 var dups = document.prefform.dups;
 if ( ismine ) {
   makeInvisible ( "others" );
 } else {
   makeVisible ( "others" );
   dups.checked = false;
 }

}

