<?php /* $Id: catsel.php,v 1.11.2.2 2007/08/06 02:28:27 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

$form = $arinc[3];
?>

function sendCats ( cats ) {
  var parentid = '';
  var parenttext = '';
  var eventid = 0;
  for ( i = 0; i < document.forms[0].elements.length; i++ ) {
 if ( document.forms[0].elements[i].name == "eventcats[]" )
      eventid = i;
  }
  for ( i = 1;  i < document.forms[0].elements[eventid].length; i++ ) {
    document.forms[0].elements[eventid].options[i].selected  = 1;
    parentid += "," + parseInt(document.forms[0].elements[eventid].options[i].value);
    parenttext += ", " + document.forms[0].elements[eventid].options[i].text;

 }
  parentid = parentid.substr (1);
 parenttext = parenttext.substr (1);
  window.opener.document.<?php echo $form ?>.cat_id.value = parentid;
  window.opener.document.<?php echo $form ?>.catnames.value = parenttext;

  window.close ();
}

function updateList( ele ) {
  document.editCategories.elements['categoryNames'].value += ele.name;
}

function selAdd(btn){
 // find id of cat selection object
  var catid = 0;
  var eventid = 0;
  for ( i = 0; i < document.forms[0].elements.length; i++ ) {
    if ( document.forms[0].elements[i].name == "cats[]" )
      catid = i;
 if ( document.forms[0].elements[i].name == "eventcats[]" )
      eventid = i;
  }
  var evlist = document.forms[0].elements[eventid];
  var isUnique = true;
   with (document.forms[0])
   {
      with (document.forms[0].elements[catid])
      {
         for (i = 0; i < length; i++) {
               if (options[i].selected) {
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
 // find id of event cat object
  var eventid = 0;
  for ( i = 0; i < document.forms[0].elements.length; i++ ) {
 if ( document.forms[0].elements[i].name == "eventcats[]" )
      eventid = i;
  }
   with (document.forms[0])
   {
      with (document.forms[0].elements[eventid])
      {
         for (i = 0; i < length; i++)
         {
           if (options[i].selected){
          options[i] = null;
        }
         } // end for loop
     }
   } // end with document
}
