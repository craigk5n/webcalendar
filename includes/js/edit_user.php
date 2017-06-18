<?php
/* $Id: edit_user.php,v 1.12.2.2 2007/08/06 02:28:27 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$errStr = translate ( 'Error', true ) . ' ';

?>
var
  formfield = 'user',
  validform = false;

function valid_form ( form ) {
  var name = form.user.value;

  if ( ! name ) {
    alert ( "<?php echo $errStr
 . translate ( 'Username cannot be blank.', true )?>" );
    return false;
  }
  check_name ();

  return validform;

}

function valid_form2 ( form ) {
  var
    pass1 = form.upassword1.value,
    pass2 = form.upassword2.value;

  if ( ! pass1 || ! pass2 ) {
    alert ( "<?php echo $errStr
 . translate ( 'You have not entered a password.', true )?>" );
    return false;
  }
  if ( pass1 != pass2 ) {
    alert ( "<?php echo $errStr
 . translate ( 'The passwords were not identical.', true )?>" );
    return false;
  }

  return true;

}

function check_name () {
  formfield = 'user';
  var ajax = new Ajax.Request ( 'ajax.php',
    {method: 'post',
    parameters: 'page=edit_user&name=' + $F ( 'username' ),
    onComplete: showResponse});
}

function check_uemail () {
  formfield = 'uemail';
  var ajax = new Ajax.Request ( 'ajax.php',
    {method: 'post',
    parameters: 'page=email&name=' + $F ( 'uemail' ),
    onComplete: showResponse} );
}

function showResponse ( originalRequest ) {
  if ( originalRequest.responseText ) {
    text = originalRequest.responseText;
    // This causes javascript errors in Firefox, but these can be ignored.
    alert ( text );
    if ( formfield == 'user' )
      document.edituser.user.focus ();

    if ( formfield == 'uemail' )
      document.edituser.uemail.focus ();

    validform =  false;
  } else {
    validform =  true;
  }
}