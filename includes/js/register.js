// $Id$

var
  ds = document.selfreg,
  validform = false;

function valid_form() {
  if( ds.upassword1.value.length == 0 ) {
    alert( xlate['inputPassword'] ); // translate( 'You have not entered a password.' )
    return false;
  }
  if( ds.user.value.length == 0 ) {
    alert( xlate['noBlankUsername'] ); // translate( 'Username cannot be blank.' )
    return false;
  }
  if( ds.upassword1.value != ds.upassword2.value ) {
    alert( xlate['passwordsNoMatch'] ); // translate( 'The passwords were not identical.' )
    return false;
  }

  checkers( 'user', 'register' );
  checkers( 'uemail', 'email' );

  return validform;
}

function checkers( formfield, params ) {
  var ajax = new Ajax.Request( 'ajax.php',
    { method: 'post',
    parameters: 'page=' + params + '&name=' + $F( formfield ),
    onComplete: showResponse } );
}

function showResponse( originalRequest ) {
  if( originalRequest.responseText ) {
    // This causes javascript errors in Firefox, but these can be ignored.
    alert( originalRequest.responseText );

    if( formfield == 'user' )
      ds.user.focus();

    if( formfield == 'uemail' )
      ds.uemail.focus();

    validform = false;
  } else {
    validform = true;
  }
}
