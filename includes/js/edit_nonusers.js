// $Id$

var validform;

function valid_form(form) {
  var name = form.nid.value,
  fname = form.nfirstname.value,
  lname = form.nlastname.value;

  if (!name) {
    alert(xlate['error'] + xlate['noBlankCalId']); // translate( 'Calendar ID cannot be blank.' )
    document.editnonuser.nid.focus();
    return false;
  }
  if (!fname && !lname) {
    alert(xlate['error'] + xlate['noBlankNames']); // translate( 'First and last names cannot both be blank.')
    document.editnonuser.nfirstname.focus();
    return false;
  }
  check_name();

  return validform;
}

function check_name() {
  var ajax = new Ajax.Request('ajax.php', {
        method: 'post',
        parameters: 'page=edit_nonuser&name=' + $F('calid'),
        onComplete: showResponse
      });
}

function showResponse(originalRequest) {
  validform = true;

  if (originalRequest.responseText) {
    // This causes javascript errors in Firefox, but these can be ignored.
    alert(originalRequest.responseText);
    document.editnonuser.nid.focus();
    validform = false;
  }
}
