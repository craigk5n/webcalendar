/* $Id$  */

var form = window.opener.document.body.id + 'form';


function sendCats ( cats ) {
  var parentid = '';
  var parenttext = '';
  for ( i = 1;  i < document.forms[0].elements['eventcats'].length; i++ ) {
    document.forms[0].elements['eventcats'].options[i].selected  = 1;
    parentid += "," + parseInt(document.forms[0].elements['eventcats'].options[i].value);
    parenttext += ", " + document.forms[0].elements['eventcats'].options[i].text;

 }
  parentid = parentid.substr (1);
 parenttext = parenttext.substr (1);
  window.opener.document.forms[form].cat_id.value = parentid;
  window.opener.document.forms[form].catnames.value = parenttext;
   
  window.close ();
}

function updateList( ele ) {
  document.editCategories.elements['categoryNames'].value += ele.name;
}

function selAdd(btn){
  var evlist = document.forms[0].elements['eventcats']; 
  var isUnique = true;
   with (document.forms[0])
   {
      with (document.forms[0].elements['cats'])
      {
         for (i = 0; i < length; i++) {
           if(options[i].selected) {
             with (options[i]) {
                                  
               for ( j=0; j < evlist.length;j++ ) {
                 if (evlist.options[j].value == value )
                   isUnique = false;
                 }
                 if ( isUnique)
                   evlist.options[evlist.length]  = new Option( text, value );    
                  options[i].selected = false;
                 } //end with options
               }
         } // end for loop
      } // end with islist1
   } // end with document
}

function selRemove(btn){
   with (document.forms[0])
   { 
      with (document.forms[0].elements['eventcats'])
      {
		     for (i = length -1; i >= 0; i--)
         {
           if(options[i].selected){
          options[i] = null;
        } 
         } // end for loop
     }
   } // end with document
}

function moveUp() {
   if ($('eventcats').length > 0) { 
      var selected = $('eventcats').selectedIndex;
		if ( selected > 1 ) {
		   // Get the text/value of the one directly above the hightlighted entry as
		   // well as the highlighted entry; then flip them
		   var moveText1 = $('eventcats')[selected-1].text;
		   var moveText2 = $('eventcats')[selected].text;
		   var moveValue1 = $('eventcats')[selected-1].value;
		   var moveValue2 = $('eventcats')[selected].value;
		   $('eventcats')[selected].text = moveText1;
		   $('eventcats')[selected].value = moveValue1;
		   $('eventcats')[selected-1].text = moveText2;
		   $('eventcats')[selected-1].value = moveValue2;
		   $('eventcats').selectedIndex = selected-1; 
		} 
   }  
}


function moveDown() {
   if ($('eventcats').length > 0) {  
      var selected = $('eventcats').selectedIndex;
      if (selected > -1 && selected < $('eventcats').length-1 ) {
	   // Get the text/value of the one directly below the hightlighted entry as
	   // well as the highlighted entry; then flip them
	   var moveText1 = $('eventcats')[selected+1].text;
	   var moveText2 = $('eventcats')[selected].text;
	   var moveValue1 = $('eventcats')[selected+1].value;
	   var moveValue2 = $('eventcats')[selected].value;
	   $('eventcats')[selected].text = moveText1;
	   $('eventcats')[selected].value = moveValue1;
	   $('eventcats')[selected+1].text = moveText2;
	   $('eventcats')[selected+1].value = moveValue2;
	   $('eventcats').selectedIndex = selected+1;
      } 
   }  
}


