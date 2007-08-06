function initEntries( user, caltype, date, cal_id ) {
  var url = 'ajax_entries.php';
  if ( cal_id )
    var calId = '&cal_id=' + cal_id;
  var params = 'page=initEntries&user=' + user + '&caltype=' + caltype + '&date=' + date + calId;
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setEntries});
}
function setEntries(originalRequest ) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
	EntriesText = text;
  }
}

function loadEntries() {
	if ( EntriesText == "" ) 
	  var t = setTimeout ( "loadEntries()", 100 );
	else {
      eval( EntriesText );
	}
}

var EntriesText = '';
