/* $Id$  */ 

initPhpVars( 'edit_user' );

var validform = false;
var formfield = 'user';
var ignore = false;

function valid_form ( form ) {
  var name = form.user.value;
	var del = form.Delete.value;
  if ( ! name ) {
    alert (  Error + ":\n\n" + noName );
    return false;  
  }  
  if ( ignore = true )
	 return true;
	 
  check_name();
  
  return validform;

}

function valid_form2 ( form ) {
  var pass1 = form.upassword1.value;
  var pass2 = form.upassword2.value;
 
  if ( ! pass1 || ! pass2 ) {
    alert ( Error + ":\n\n" + noPassword );
    return false;  
  }
  if (  pass1 != pass2 ) {
    alert ( Error + ":\n\n" + diffPassword );
    return false;  
  }

  return true;

}
function setIgnore() {
	ignore = true;
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