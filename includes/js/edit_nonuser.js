/* $Id$  */ 

initPhpVars( 'edit_nonuser' );

var validform = true;


function valid_form ( form ) {
  var name = form.nid.value;
  var fname = form.nfirstname.value;
  var lname = form.nlastname.value;
  if ( ! name ) {
    alert ( Error + ":\n\n" + blankID );
    document.editnonuser.nid.focus();
    return false;  
  }  
  if ( ! fname && ! lname ) {
    alert ( Error + ":\n\n" + blankNames );
    document.editnonuser.nfirstname.focus();  
    return false;  
  }

  check_name();
  
  return validform;

}


function check_name() {
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
    document.editnonuser.nid.focus();
    validform =  false;
  } else {
    validform =  true;
  }
}