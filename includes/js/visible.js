
// detect browser
NS4 = ( document.layers ? 1 : 0 );
IE4 = ( document.all ? 1 : 0 );
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = ( document.getElementById ? 1 : 0 );

function makeVisible( name, hide ) {
  var ele;

  if ( W3C ) {
    ele = document.getElementById( name );
  }
  else if ( NS4 ) {
    ele = document.layers[name];
  }
  else { // IE4
    ele = document.all[name];
  }

  if ( NS4 ) {
    ele.visibility = 'show';
  }
  else { // IE4 & W3C & Mozilla
    ele.style.visibility = 'visible';
    if ( hide ) {
      ele.style.display = '';
    }
  }
}

function makeInvisible( name, hide ) {
  var ele;

  if ( W3C ) {
    ele = document.getElementById( name ).style;
    ele.visibility = 'hidden';
    if ( hide ) {
      ele.display = 'none';
    }
  }
  else if ( NS4 ) {
    document.layers[name].visibility = 'hide';
  }
  else {
    ele = document.all[name].style;
    ele.visibility = 'hidden';
    if ( hide ) {
      ele.display = 'none';
    }
  }
}

function showTab( name ) {
  if ( !document.getElementById ) {
    return true;
  }

  var div, i, tab, tname;

  for ( i = 0; i < tabs.length; i++ ) {
    tname = tabs[i];
    tab = document.getElementById( 'tab_' + tname );
    // We might call without parameter, if so display tabfor div.
    if ( tab && !name ) {
      if ( tab.className == 'tabfor' ) {
        name = tname;
      }
    }
    else if ( tab ) {
      tab.className = ( tname == name ? 'tabfor' : 'tabbak' );
    }
    div = document.getElementById( 'tabscontent_' + tname );
    if ( div ) {
      div.style.display = ( tname == name ? 'block' : 'none' );
    }
  }

  return false;
}

function visByClass( classname, state ) {
  var
    inc = 0,
    alltags = ( document.all ? document.all : document.getElementsByTagName( '*' ) );

  for ( i = 0; i < alltags.length; i++ ) {
    var str = alltags[i].className;

    if ( str && str.match( classname ) ) {
      alltags[i].style.display = ( state == 'hide' ? 'none' : '' );
    }
  }
}

function getScrollingPosition() {
  var position = [0, 0];

  if ( typeof window.pageYOffset != 'undefined' ) {
    position = [
      window.pageXOffset,
      window.pageYOffset
    ];
  }
  else if ( typeof document.documentElement.scrollTop != 'undefined'
    && document.documentElement.scrollTop > 0 ) {
    position = [
      document.documentElement.scrollLeft,
      document.documentElement.scrollTop
    ];
  }
  else if ( typeof document.body.scrollTop != 'undefined' ) {
    position = [
      document.body.scrollLeft,
      document.body.scrollTop
    ];
  }

  return position;
}


function valid_color( str ) {
  return /^#[0-9a-fA-F]{3}$|^#[0-9a-fA-F]{6}$/.test( str );
}

// Updates the background-color of a table cell
// Parameters:
//   input - element containing the new color value
//   target - id of sample
function updateColor( input, target ) {
  var // The cell to be updated.
    colorCell = document.getElementById( target ).style.backgroundColor,
    color = input.value;  // The new color.

  if ( !valid_color( color ) ) {
    // Color specified is invalid; use black instead.
    colorCell = '#000000';
    input.select();
    input.focus();
    alert( xlate['invalidColor'] );
  }
  else {
    colorCell = color;
  }
}

function toggle_datefields( name, ele ) {
  if ( document.getElementById( ele.id ).checked ) {
    makeInvisible( name );
  }
  else {
    makeVisible( name );
  }
}

function callEdit() {
  editwin = window.open( 'edit_entry.php', 'edit_entry',
    'width=600,height=500,resizable=yes,scrollbars=no' );
}
