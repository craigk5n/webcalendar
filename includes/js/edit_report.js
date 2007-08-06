/* $Id$  */

  //This script borrowed from phpMyAdmin with some modification
	function addMe (areaname, myValue) {
    var textarea = document.reportform.elements[areaname];
	  //IE support
	  if (document.selection) {
	    textarea.focus();
	    sel = document.selection.createRange();
	    sel.text = myValue;
	  }
	  //MOZILLA/NETSCAPE support
	  else if (textarea.selectionStart || textarea.selectionStart == '0') {
	    var startPos = textarea.selectionStart;
	    var endPos = textarea.selectionEnd;
	    textarea.value = textarea.value.substring(0, startPos)
	    + myValue
	    + textarea.value.substring(endPos, textarea.value.length);
	  } else {
	    textarea.value += myValue;
	  }
	}
