<?php /* $Id$  */ 
defined( '_ISVALID' ) or die( "You can't access this file directly!" );
?>
var validform = true;
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
  if ( form.layercolor && ! valid_color ( form.layercolor.value ) )
    err += "<?php etranslate( 'Invalid color', true)?>.\n";

  if ( err.length > 0 ) {
    alert ( "<?php etranslate( 'Error', true) ?>:\n\n" + err + "\n\n<?php 
  etranslate("Color format should be '#RRGGBB'", true)?>" );
    return false;
  }
  if (  ! form.nurl.value ) {
    alert ( "<?php etranslate( 'Error', true) ?>:\n\n" + "<?php 
      etranslate( 'URL can not be blank', true)?>" );
    return false;  
  }
  check_name();
  
  return validform;

}

function selectColor ( color ) {
  url = "colors.php?color=" + color;
  var colorWindow = window.open(url,"ColorSelection","width=390,height=350,resizable=yes,scrollbars=yes");
}

function toggle_layercolor() {
 if ( document.prefform.nlayer.checked == true) {
   makeVisible ( 'nlayercolor', true );
 } else {
   makeInvisible ( 'nlayercolor' );
 }
}

function check_name() {
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
    document.prefform.nid.focus();
    validform =  false;
  } else {
    validform =  true;
  }
}