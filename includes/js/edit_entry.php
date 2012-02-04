<?php // $Id$
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// I'm slowly moving this into edit_entry.js

 global $GROUPS_ENABLED,$WORK_DAY_START_HOUR,$WORK_DAY_END_HOUR;

if ( $GROUPS_ENABLED == 'Y' ) {
  echo '
function addGroup() {
  var list = document.editentryform.groups,
  selNum = list.selectedIndex;';

  $groups = get_groups( $arinc[3] ); // $user
  for ( $i = 0, $j = count( $groups ); $i < $j; $i++ ) {
    echo '

  if (selNum == ' . $i . ') {';
    $res = dbi_execute ( 'SELECT cal_login from webcal_group_user
      WHERE cal_group_id = ?', array ( $groups[$i]['cal_group_id'] ) );
    if ( $res ) {
      while ( $row = dbi_fetch_row( $res ) ) {
        echo '
    selectByLogin(\'' . $row[0] . '\');';
      } // end while
      dbi_free_result( $res );
      } // end if res
      echo '
  }'; // end js if selnum
  } // end for loop
echo '
}'; // end function
} // end if GROUPS_ENABLED
?>
function catOkHandler () {
  // Get selected categories
  var catIds = '', catNames = '';
<?php
  foreach ( $categories as $catid => $cat ) {
    if ( $catid == 0 || $catid == -1 )
      continue; // Ignore these special cases (0=All, -1=None)
    ?>
  var checkboxId = 'cat_<?php echo $catid;?>';
  var nameId = 'cat_<?php echo $catid;?>_text';

  obj = document.getElementById ( checkboxId );
  if ( obj ) {
    if ( obj.checked ) {
      if ( catIds.length > 0 ) {
        catIds += ',';
        catNames += ', ';
      }
      catIds += '<?php echo $catid;?>';
      catNames += '<?php echo $cat['cat_name'];?>';
    }
  } else { // translate( 'Could not find XXX.' )
    alert ( xlate['notFind'].replace(/XXX/, ( obj ? nameId : checkboxId)));
  }
<?php
  }
?>
  $('entry_categories').innerHTML = catNames;
  $('cat_id').value = catIds;
  Modalbox.hide ();
  return true;
}
function editCats ( evt ) {
  var obj;

// Craig or Ray, Is this function supposed to be empty?
  function catWindowClosed () {
  }
  // translate( 'Categories') translate( 'Cancel' )
  Modalbox.show($('editCatsDiv'), {title: 'xlate[\'Categories\']', width: 350, onHide: catWindowClosed, closeString: 'xlate[\'cancel\']' });

  var cat_ids = elements['cat_id'].value;
  var selected_ids = cat_ids.split ( ',' );

<?php
  load_user_categories();
  foreach ( $categories as $catid => $cat ) {
    if ( $catid == 0 || $catid == -1 )
      continue; // Ignore these special cases (0=All, -1=None)
    ?>

    var checkboxId = 'cat_<?php echo $catid;?>';
    obj = document.getElementById( checkboxId );

    if (obj) {
      // Is this selected?
      var sel = false;

      for (var i = selected_ids.length - 1; i >= 0; i--) {
        if (selected_ids[i] == <?php echo $catid;?>)
          sel = true;
      }
      obj.checked = sel;
    } else { // translate( 'Could not find XXX in DOM.' )
      alert(xlate['noXXXInDom'].replace(/XXX/, checkboxId);
    }
<?php
  }
?>
}
