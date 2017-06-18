<?php
/* $Id: usersel.php,v 1.26.2.2 2007/08/06 02:28:27 cknudsen Exp $  */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
global $form, $groups, $listid;

$form = clean_word ( $form );
$listid = clean_int ( $listid );

?>
function OkButton () {
  var
    i,
    parentlist = window.opener.document.<?php echo $form . '.elements[' . $listid?>],
    thislist = document.userselform.elements[0],
    // Store current selections.
    tmp = new Array ();

  for ( i = 0; i < thislist.length; i++ ) {
    if (thislist.options[i].selected)
      tmp[i] = thislist.options[i].value;
  }

  // Select/deselect users on parent form.
    for ( i = 0; i < parentlist.length; i++ ) {
      parentlist.options[i].selected = ( tmp[i] != undefined );
    }
  window.close ();
}

function selectAll ( state ) {
  var list = document.userselform.elements[0];

  for ( var i = 0; i < list.options.length; i++ ) {
    list.options[i].selected = state;
  }
}

// Set the state (selected or unselected) if a single user in the list of users.
function selectByLogin ( login, state ) {
  // alert ( "selectByLogin ( " + login + ", " + state + " )" );
  var list = document.userselform.elements[0];

  for ( var i = 0; i < list.options.length; i++ ) {
    // alert ( "text: " + list.options[i].text );
    if ( list.options[i].value == login ) {
      list.options[i].selected = state;
      return;
    }
  }
}

function toggleGroup ( state ) {
  var
    list = document.userselform.elements[4],
    selNum = list.selectedIndex;
<?php
for ( $i = 0; $i < count ( $groups ); $i++ ) {
  echo "\n    if ( selNum == $i ) {\n";
  $res = dbi_execute ( 'SELECT cal_login from webcal_group_user
    WHERE cal_group_id = ?', array ( $groups[$i]['cal_group_id'] ) );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      echo "      selectByLogin ( \"$row[0]\", state );\n";
    }
    dbi_free_result ( $res );
    echo "  }\n";
  }
}

?>
}
