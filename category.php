<?php
/* $Id$ */
include_once 'includes/init.php';

// load user and global cats
load_user_categories ();

$error = '';

$icon_path = 'icons/';

if ( $CATEGORIES_ENABLED == 'N' ) {
  send_to_preferred_view ();
  exit;
}

$catIconStr = translate ( 'Category Icon' );
$globalStr = translate ( 'Global' );

// If editing, make sure they are editing their own
// (or they are an admin user).
if ( ! empty ( $id ) ) {
  if ( empty ( $categories[$id] ) )
    $error = translate( 'Invalid entry id' ) . ': ' . $id;

    $idStr = "<input name=\"id\" type=\"hidden\" value=\"$id\" />";
    $button = translate( 'Save' );
    $catname = $categories[$id]['cat_name'];
    $catowner = $categories[$id]['cat_owner'];
    $catcolor = $categories[$id]['cat_color'];
    $catIcon = $icon_path . 'cat-' . $id . '.gif';
} else {
  $idStr = '';
  $catname = '';
  $catcolor = '#000000';
}

$showIcon = ( ! empty ( $catIcon ) && file_exists ( $catIcon ) ? 'visible' : 'hidden' );

$INC = array('js/visible.php');
print_header($INC);
?>
<h2><?php etranslate( 'Categories' )?></h2>
<?php
echo display_admin_link();

if ( empty ( $add ) )
  $add = 0;

// Adding/Editing category
$button = translate( $add == '1' ? 'Add' : 'Save' );

if ( ( ( $add == '1' ) || ( ! empty ( $id ) ) ) && empty ( $error ) ) { ?>
  <form action="category_handler.php" method="post" name="catform" enctype="multipart/form-data">
  <?php echo $idStr ."\n" ?>
  <table cellspacing="2" cellpadding="3">
    <tr><td width="25%">
      <label for="catname">
  <?php
  etranslate( 'Category Name' )?>:</label></td>
  <td colspan="3"><input type="text" name="catname" size="20" value="<?php   echo htmlspecialchars ( $catname ); ?>" />
  </td></tr>
<?php  if ( $is_admin && empty ( $id ) ) { ?>
   <tr><td><label for="isglobal"><?php echo $globalStr?>:</label></td>
      <td colspan="3">
      <label><input type="radio" name="isglobal" value="N" <?php if ( ! empty ( $catowner ) || empty ( $id ) ) echo ' checked="checked"';?> />&nbsp;<?php etranslate ( 'No')?></label>&nbsp;&nbsp;
      <label><input type="radio" name="isglobal" value="Y" <?php if ( empty ( $catowner ) && ! empty ( $id ) ) echo ' checked="checked"';?> />&nbsp;<?php etranslate ( 'Yes' )?></label>
  </td></tr>
  <?php } ?>
  <tr><td>
<?php echo print_color_input_html ( 'catcolor', 
   translate( 'Color' ), $catcolor ) ?>
  </td></tr>
  <tr id="cat_icon" style="visibility:<?php echo $showIcon ?>"><td>
    <label><?php echo $catIconStr?>:</label></td>
     <td colspan="3"><img src="<?php echo $catIcon?>" name="urlpic" 
      id="urlpic" alt="<?php echo $catIconStr?>" />
     </td></tr>
     <tr id="remove_icon" style="visibility:<?php echo $showIcon ?>">
     <td><label for="delIcon">
      <?php echo translate( 'Remove Icon' )?>:</label></td>
      <td colspan="3"><input type="checkbox" name="delIcon" value="Y" />
      </td></tr>
  <tr><td colspan="4">
  <label for="FileName">
<?php if ( is_dir($icon_path) && ( ! empty ( $ENABLE_ICON_UPLOADS ) &&
  $ENABLE_ICON_UPLOADS == 'Y' || $is_admin )) { 
  echo translate ( 'Add Icon to Category' ) . '</label><br />&nbsp;&nbsp;&nbsp;'.
   translate ( 'Upload' ) . '&nbsp;<span style="font-size:small;">(' . 
   translate ('gif 3kb max') . ')</span> :'; ?>
 <input type="file" name="FileName" id="fileupload" size="45" maxlength="50" /> 
  </td></tr>
  </tr><td colspan="4">
  <input type="hidden" name="urlname" size="50"   />
  &nbsp;&nbsp;&nbsp;<input type="button" value="<?php 
    etranslate ( 'Search for existing icons', true);?>" onclick="window.open('icons.php', 'icons','dependent,menubar=no,scrollbars=n0,height=300,width=400, outerHeight=320,outerWidth=420');" />
  </td></tr>
  </tr><td colspan="4">
<?php } //end test of ENABLE_ICON_UPLOADS ?>
  <input type="submit" name="action" value="<?php echo $button;?>" />
  <?php if ( ! empty ( $id ) ) {  ?>
 <input type="submit" name="delete" value="<?php etranslate( 'Delete' );?>" onclick="return confirm('<?php etranslate( 'Are you sure you want to delete this entry?', true); ?>')" />
  <?php }  ?>
  </td></tr>
  </table>
  </form>
  <?php
} else if ( empty ( $error ) ) {
  // Displaying Categories
  $global_found = false;
  if ( ! empty ( $categories ) ) {
    echo '<ul>';
    foreach ( $categories as $K => $V ) {
      if ( $K <1 ) continue;
      $catIcon = $icon_path. 'cat-' . $K . '.gif';
      $color = ( ! empty ( $V['cat_color'] ) ? $V['cat_color'] : '#000000' );
      $catStr = "<span style=\"color:{$color};\">{$V['cat_name']}</span>"; 
      echo '<li>';
      if ( $V['cat_owner'] == $login || $is_admin )
        echo "<a href=\"category.php?id=$K\">{$catStr}</a>";
      else
        echo $catStr;
      if ( empty ( $V['cat_owner'] ) ) {
        echo '<sup>*</sup>';
        $global_found = true;
      }
      if ( file_exists ( $catIcon ) ){
        echo "\n&nbsp;&nbsp;<img src=\"$catIcon\" alt=\"" 
          . $catIconStr. '" title="' . $catIconStr . "\" />";
      }
      echo "</li>\n";
    }
    echo '</ul>';
  }
  if ( $global_found )
    echo "<br /><br />\n<sup>*</sup> " . $globalStr;
  echo '<p><a href="category.php?add=1">' . translate( 'Add New Category' ) 
    . "</a></p><br />\n";
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
}
echo print_trailer(); ?>
