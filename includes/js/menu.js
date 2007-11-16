function initMENU( ) {
  var url = 'ajax.php';
  var params = 'page=initMENU';
  var ajax = new Ajax.Request(url,
    {method: 'post', 
    parameters: params, 
    onComplete: setMENU});
}
function setMENU(originalRequest ) {
  if (originalRequest.responseText) {
    text = originalRequest.responseText;
	MENUContainerText = text;
  }
}

function loadMENU() {
	if ( MENUContainerText == "" ) 
	  var t = setTimeout ( "loadMENU()", 100 );
	else {
	  MENUContainer = document.getElementById('dateselector');
	  MENUContainer.innerHTML = MENUContainerText;
	}
}

var MENUContainerText = '';
initMENU();
