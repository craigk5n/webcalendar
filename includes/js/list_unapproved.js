/* $Id: list_unapproved.php */

initPhpVars( 'list_unapproved' );

function check_all( user) {
  var theForm = document.forms['listunapproved'];
  var z = 0;
  for(z=0; z < theForm.length;z++){
    if(theForm[z].type == 'checkbox' && theForm[z].value == user){
    theForm[z].checked = true;
    }
  }
}
function uncheck_all(user) {
  var theForm = document.forms['listunapproved'];
  var z = 0;
  for(z=0; z < theForm.length;z++){
    if(theForm[z].type == 'checkbox' && theForm[z].value == user){
    theForm[z].checked = false;
    }
  }
}
function do_confirm( phrase, user, id ) {
  
  form = document.listunapproved;
  switch ( phrase ) {
    case "approve":
      str = appEntry;
      action = 'A';
      break;
    case "reject":
      str = rejEntry;
      action = 'R';
      break;
    case "delete":
      str = confirmDel;
      action = 'D';
      break;
    case "approveSelected":
      str = appSelected;
      action = 'A';
      break;
    case "rejectSelected":
      str = rejSelected;
      action = 'R';
      break;
    default:
      str = action = '';
  }
  form.process_action.value = action;
  form.process_user.value = user;
  conf = confirm(str);
  //We need this if only single operation
  if ( id  && conf )
    form.elements[id].checked = true;
  return conf;
}


