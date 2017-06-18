<?php /* $Id: edit_nonuser.php,v 1.10.2.2 2007/08/06 02:28:27 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
?>
var validform = true;

function valid_form ( form ) {
  var name = form.nid.value;
  var fname = form.nfirstname.value;
  var lname = form.nlastname.value;
  if ( ! name ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + "<?php
      etranslate ( 'Calendar ID cannot be blank.', true)?>" );
    document.editnonuser.nid.focus ();
    return false;
  }
  if ( ! fname && ! lname ) {
    alert ( "<?php etranslate ( 'Error', true) ?>:\n\n" + "<?php
      etranslate ( 'First and last names cannot both be blank.', true)?>" );
    document.editnonuser.nfirstname.focus ();
    return false;
  }

  check_name ();

  return validform;

}

function check_name () {
  var url = 'ajax.php';
  var params = 'page=edit_nonuser&name=' + $F('calid');
  var ajax = new Ajax.Request(url,
    {method: 'post',
    parameters: params,
    onComplete: showResponse});
}

function showResponse(originalRequest) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
    //this causes javascript errors in Firefox, but these can be ignored
    alert (text);
    document.editnonuser.nid.focus ();
    validform =  false;
  } else {
    validform =  true;
  }
}