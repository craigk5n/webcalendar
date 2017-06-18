<?php /* $Id: edit_remotes.php,v 1.16.2.2 2007/08/06 02:28:27 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
var validform = true;

function valid_form ( form ) {
  var err = "";
  if ( form.layercolor && ! valid_color ( form.layercolor.value ) )
    err += "<?php etranslate ( 'Invalid color', true)?>.\n";

  if ( err.length > 0 ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + err + "\n\n<?php
  etranslate ( 'Color format should be RRGGBB.', true)?>" );
    return false;
  }
  if ( ! form.nurl.value ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + "<?php
      etranslate ( 'URL cannot be blank.', true)?>" );
    return false;
  }
  check_name ();

  return validform;

}

function toggle_layercolor () {
 if ( document.prefform.nlayer.checked == true) {
   makeVisible ( 'nlayercolor', true );
 } else {
   makeInvisible ( 'nlayercolor' );
 }
}

function check_name () {
  var url = 'ajax.php';
  var params = 'page=edit_remotes&name=' + $F('nid');
  var ajax = new Ajax.Request(url,
    {method: 'post',
    parameters: params,
    onComplete: showResponse});
}

function showResponse(originalRequest) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    //this causes jacascript errors in Firefox, but these can be ignored
    alert (text);
    document.prefform.nid.focus ();
    validform =  false;
  } else {
    validform =  true;
  }
}