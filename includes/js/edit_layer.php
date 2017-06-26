<?php /* $Id: edit_layer.php,v 1.21.2.2 2007/08/06 02:28:27 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
function valid_form ( form ) {
  var err = "";
  if ( ! valid_color ( form.layercolor.value ) )
    err += "<?php etranslate ( 'Invalid color', true)?>.\n";

  if ( err.length > 0 ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + err + "\n\n<?php
  etranslate ( 'Color format should be RRGGBB.', true)?>" );
    return false;
  }
  return true;
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

function deleteLayer( loc ) {
  if ( confirm('<?php
     echo str_replace ( 'XXX', translate ( 'layer', true ),
      translate ( 'Are you sure you want to delete this XXX?', true ) )?>' ) )
    location.href = loc;
  return false;
}
