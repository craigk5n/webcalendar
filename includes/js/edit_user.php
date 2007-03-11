<?php /* $Id$  */ 
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );
?>
var validform = false;
var formfield = 'user';

function valid_form ( form ) {
  var name = form.user.value;
  if ( ! name ) {
    alert ( "<?php etranslate( 'Error', true) ?>:\n\n" + "<?php 
      etranslate( 'Username cannot be blank.', true)?>" );
    return false;  
  }  
  check_name();
  
  return validform;

}

function valid_form2 ( form ) {
  var pass1 = form.upassword1.value;
  var pass2 = form.upassword2.value;
 
  if ( ! pass1 || ! pass2 ) {
    alert ( "<?php etranslate( 'Error', true) ?>:\n\n" + "<?php 
      etranslate( 'You have not entered a password', true)?>" );
    return false;  
  }
  if (  pass1 != pass2 ) {
    alert ( "<?php etranslate( 'Error', true) ?>:\n\n" + "<?php 
      etranslate( 'The passwords were not identical', true)?>" );
    return false;  
  }

  return true;

}


function check_name() {
  formfield = 'user';
  var url = 'ajax.php';
  var params = 'page=edit_user&name=' + $F('username');
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: showResponse});
}

function check_uemail() {
  formfield = 'uemail';
  var url = 'ajax.php';
  var params = 'page=email&name=' + $F('uemail');
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
    if (   formfield == 'user' )
      document.edituser.user.focus();
    if (   formfield == 'uemail' )
      document.edituser.uemail.focus();
    validform =  false;
  } else {
    validform =  true;
  }
}