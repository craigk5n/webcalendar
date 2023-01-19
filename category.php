<?php
include_once 'includes/init.php';
// Load user and global cats.
load_user_categories();

if ( $CATEGORIES_ENABLED == 'N' ) {
  send_to_preferred_view();
  exit;
}

// Verify that permissions allow writing to the "wc-icons" directory.
$canWrite = false;
$permError = false;
if ( $ENABLE_ICON_UPLOADS == 'Y' || $is_admin ) {
  $testFile = "wc-icons/testWrite.txt";
  $testFd = @fopen ( $testFile, "w+b", false );
  @fclose ( $testFd );
  $canWrite = file_exists ( $testFile );
  if ( ! $canWrite ) {
    $permError = true;
  } else {
    @unlink ( $testFile );
  }
}

$catIcon = $catname = $error = $idStr = '';
$catIconStr = translate ( 'Category Icon' );
$globalStr = translate ( 'Global' );
$icon_path = 'wc-icons/';
// If editing, make sure they are editing their own (or they are an admin user).
if ( ! empty ( $id ) ) {
  if ( empty ( $categories[$id] ) )
    $error =
    str_replace ( 'XXX', $id, translate ( 'Invalid entry id XXX.' ) );

  $catcolor = $categories[$id]['cat_color'];
  $catname = $categories[$id]['cat_name'];
  $catowner = $categories[$id]['cat_owner'];
  $catIcon = $icon_path . 'cat-' . $id . '.gif';
  // Try PNG if GIF not found
  if ( ! file_exists ( $catIcon ) )
    $catIcon = $icon_path . 'cat-' . $id . '.png';
  $idStr = '<input name="id" type="hidden" value="' . $id . '" />';
} else
  $catcolor = '#000000';

$showIconStyle = ( ! empty ( $catIcon ) && file_exists ( $catIcon )
  ? '' : 'display: none;' );

print_header ( array ( 'js/visible.php' ) );
echo '
    <h2>' . translate ( 'Categories' ) . '</h2>
    ' . display_admin_link( false );

// Display permission error if found above.
if ( $permError && $is_admin ) {
  print_error_box (
    translate('The permissions for the icons directory are set to read-only') );
}

$add = getGetValue ( 'add' );
if ( empty ( $add ) )
  $add = 0;
// Adding/Editing category.
if ( ( ( $add == '1' ) || ( ! empty ( $id ) ) ) && empty ( $error ) ) {
  echo '
    <form action="category_handler.php" method="post" name="catform" '
    . 'enctype="multipart/form-data">' . csrf_form_key() . $idStr . '
    <div class="form-inline">
    <label class="col-sm-3 col-form-label" for="catname">' . translate ('Category Name') . '</label>
    <input class="form-control" type="text" name="catname" size="20" value="'
    . htmlspecialchars ($catname) . '" /></div>' .
    ($is_admin && empty ($id) ? '

    <div class="form-inline"><label class="col-sm-3 col-form-label" for="isglobal">'
    . $globalStr . ":</label>"
    . print_radio ( 'isglobal', '', '', (empty ($catowner) && ! empty ($id)) ? 'Y' : 'N', '') .
    '</div>' : '' ) .

    '<div class="form-inline">
    <label class="col-sm-3 col-form-label" for="catname">' . translate ('Color') . ':</label>'
    . print_color_input_html ('catcolor', translate ('Color'), $catcolor) .
    '</div>';

    // Category icon
    echo '
    <div class="form-inline" id="cat_icon" style="' . $showIconStyle . '">
    <label class="col-sm-3 col-form-label" for="catname">' . translate ('Category Icon') . ':</label>
    <img src="' . $catIcon . '" name="urlpic" id="urlpic" alt="' . $catIconStr . '" /></div>
    <div id="remove_icon" class="form-inline" style="' . $showIconStyle . '">
    <label class="col-sm-3 col-form-label" for="delIcon">' . translate ('Remove Icon') . '</label>
    <input type="checkbox" name="delIcon" value="Y" /></div>
    <div class="form-inline">
    <label class="col-sm-3 col-form-label" for="FileName">' 
    . ( is_dir ( $icon_path ) &&
    ( ( $ENABLE_ICON_UPLOADS == 'Y' || $is_admin ) && $canWrite )
    ? translate ( 'Add Icon to Category' ) . ':</label>
      <input class="form-control" type="file" name="FileName" id="fileupload" size="45" '
     . 'maxlength="50" value=""/>
     <small class="ml-2">('
     . translate ('GIF or PNG 6kb max') . ')</small>
    </div>

    <div class="form-inline p-1">
    <input type="hidden" id="urlname" name="urlname" size="50" />&nbsp;&nbsp;&nbsp;
    <input class="btn btn-secondary openBtn" type="button" value="'
     . translate ( 'Search for existing icons...' )
     . '" />
     </div>' : '' ) // end test of ENABLE_ICON_UPLOADS
  . '<div class="form-inline">
  <input class="form-control btn btn-primary" type="submit" name="action" value="'
   . ( $add == '1' ? translate ('Add') : translate ('Save') ) . '" />'
   . '<a href="category.php" class="form-control btn btn-secondary ml-1">Cancel</a> '
   . ( ! empty ( $id ) ? '
      <input class="form-control btn btn-danger ml-1" type="submit" name="delete" value="'
     . translate ('Delete') . '" onclick="return confirm( '
     . translate( 'Are you sure you want to delete this entry?', true )
     . '\' )" />' : '' ) . '
          </div>
    </form>';
} else
if ( empty ( $error ) ) {
  // Displaying Categories.
  $global_found = false;
  //echo "<pre>"; print_r($categories); echo "</pre>";
  if ( ! empty ( $categories ) ) {
    echo '
    <ul>';
    foreach ( $categories as $K => $V ) {
      if ( $K < 1 )
        continue;
      $catIcon = $icon_path . 'cat-' . $K . '.gif';
      if ( ! file_exists ( $catIcon ) )
        $catIcon = $icon_path . 'cat-' . $K . '.png';
      $catStr = '<span style="color: '
       . ( ! empty ( $V['cat_color'] ) ? $V['cat_color'] : '#000000' )
       . ';">' . htmlentities ( $V['cat_name'] ) . '</span>';
      echo '
      <li>' . ( $V['cat_owner'] == $login || $is_admin
        ? '<a href="category.php?id=' . $K . '">' . $catStr . '</a>' : $catStr );

      if ( empty ( $V['cat_owner'] ) ) {
        echo '<sup>*</sup>';
        $global_found = true;
      }

      echo ( file_exists ( $catIcon ) ? '<img src="' . $catIcon . '" alt="'
         . $catIconStr . '" title="' . $catIconStr . '" />' : '' ) . '</li>';
    }
    echo '
    </ul>';
  }
  echo ( $global_found ? '<sup>*</sup> ' . $globalStr : '' ) . '
    <br><div class="p-2"><a class="btn btn-primary" href="category.php?add=1">' . translate ( 'Make New Category' )
   . '</a></div><br />';
}
?>

<!-- Icon selectoin modal -->
<div class="modal fade" id="iconmodal" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php etranslate('Current Icons');?></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button id="modalclosebtn" type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$('.openBtn').on('click',function(){
    $('.modal-body').load('icons.php',function(){
        $('#iconmodal').modal({show:true});
    });
});
</script>

<?php
echo ( ! empty ( $error ) ? print_error ( $error ) : '' ) . print_trailer();

?>
