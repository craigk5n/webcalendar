<?php /* $Id$  */
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );
  global $form,$listid,$groups;
  $form = clean_word($form);
  $listid = clean_int($listid);
?>
function OkButton () {
  var parentlist = window.opener.document.<?php echo $form?>.elements[<?php echo $listid?>];
  var thislist = document.userselform.elements[0];
  // store current selections
  tmp = new Array();
  for ( i = 0; i < thislist.length; i++ ) {
    if (thislist.options[i].selected) {
      tmp[i] = thislist.options[i].value;
    }
  }

  // select/deselect users on parent form
    for ( j = 0; j < parentlist.length; j++ ) {
      if ( tmp[j] != undefined ) {
         parentlist.options[j].selected = true;
      }
    }
  window.close ();
}

function selectAll() {
  var list = document.userselform.elements[0];
  var i;
  for ( i = 0; i < list.options.length; i++ ) {
    list.options[i].selected = true;
  }
}

function selectNone() {
  var list = document.userselform.elements[0];
  var i;
  for ( i = 0; i < list.options.length; i++ ) {
    list.options[i].selected = false;
  }
}

// set the state (selected or unselected) if a single
// user in the list of users
function selectByLogin ( login, state ) {
  //alert ( "selectByLogin ( " + login + ", " + state + " )" );
  var list = document.userselform.elements[0];
  var i;
  for ( i = 0; i < list.options.length; i++ ) {
    //alert ( "text: " + list.options[i].text );
    if ( list.options[i].value == login ) {
      list.options[i].selected = state;
      return;
    }
  }
}

function toggleGroup ( state ) {
  var list = document.userselform.elements[4];
  var selNum = list.selectedIndex;
  <?php
    for ( $i = 0; $i < count ( $groups ); $i++ ) {
      echo "\n  if ( selNum == $i ) {\n";
      $res = dbi_execute ( 'SELECT cal_login from webcal_group_user
        WHERE cal_group_id = ?', array ( $groups[$i]['cal_group_id'] ) );
      if ( $res ) {
        while ( $row = dbi_fetch_row ( $res ) ) {
          echo "    selectByLogin ( \"$row[0]\", state );\n";
        }
        dbi_free_result ( $res );
        echo "  }\n";
      }
    }
  ?>
}

// Select users from a group
function selectGroupMembers () {
  toggleGroup ( true );
}

// De-select users from a group
function deselectGroupMembers () {
  toggleGroup ( false );
}

