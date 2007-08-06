/* $Id$  */

initPhpVars( 'edit_layer' );

function valid_form ( form ) {
  var err = "";
  if ( ! valid_color ( form.layercolor.value ) )
    err += edl1;

  if ( err.length > 0 ) {
    alert ( Error + err + "\n\n<" + colorFormat );
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
  if ( confirm( ruSure ) )
    location.href = loc;
  return false; 
}
