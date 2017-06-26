// $Id: catsel.js,v 1.3 2009/11/22 16:47:46 bbannon Exp $

function sendCats( cats ) {
  var
    dfe      = document.forms[0].elements,
    eventid  = 0,
    parentid = parenttext = '',
    woda     = window.opener.document.arinctri;

  for ( i = 0; i < dfe.length; i++ ) {
    if ( dfe[i].name == "eventcats[]" )
      eventid = i;
  }
  for ( i = 1; i < dfe[eventid].length; i++ ) {
    dfe[eventid].options[i].selected = 1;
    parentid   += ',' + parseInt(dfe[eventid].options[i].value);
    parenttext += ', ' + dfe[eventid].options[i].text;

 }
  parentid   = parentid.substr( 1 );
  parenttext = parenttext.substr( 1 );

  woda.cat_id.value   = parentid;
  woda.catnames.value = parenttext;

  window.close();
}

function updateList( ele ) {
  document.editCategories.elements['categoryNames'].value += ele.name;
}

function selAdd( btn ) {
 // find id of cat selection object
  var
    catid = eventid = 0,
    dfe   = document.forms[0].elements;

  for ( i = 0; i < dfe.length; i++ ) {
    if ( dfe[i].name == "cats[]" ) {
      catid = i;
    }
    if ( dfe[i].name == "eventcats[]" ) {
      eventid = i;
    }
  }
  var
    evlist   = dfe[eventid],
    isUnique = true;

  with ( document.forms[0] ) {
    with ( dfe[catid] ) {
      for ( i = 0; i < length; i++ ) {
        if ( options[i].selected ) {
          with ( options[i] ) {
            for ( j = 0; j < evlist.length; j++ ) {
              if ( evlist.options[j].value == value ) {
                isUnique = false;
              }
            }
            if ( isUnique ) {
              evlist.options[evlist.length] = new Option( text, value );
            }
            options[i].selected = false;
          } //end with options
        }
      } // end for loop
    } // end with islist1
  } // end with document
}

function selRemove( btn ) {
 // find id of event cat object
  var
    dfe     = document.forms[0].elements,
    eventid = 0;

  for ( i = 0; i < dfe.length; i++ ) {
    if ( dfe[i].name == "eventcats[]" ) {
      eventid = i;
    }
  }
  with ( document.forms[0] ) {
    with ( dfe[eventid] ) {
      for ( i = 0; i < length; i++ ) {
        if ( options[i].selected ) {
          options[i] = null;
        }
      } // end for loop
    }
  } // end with document
}
