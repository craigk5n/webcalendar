<?php
/* $Id$ */
include_once 'includes/init.php';

// load user and global cats
load_user_categories ();

$catList = $catNames = $error = '';

if ( $CATEGORIES_ENABLED == 'N' )
  exit;

$cats = getGetValue ( 'cats' );
$form = getGetValue ( 'form' );

$eventcats = explode ( ',', $cats );

print_header ( array ( 'js/catsel.php/false/' . $form ),
  '', '', true, false, true );

ob_start ();

echo '
    <table align="center" border="0" width="250px">
      <tr>
        <th colspan="3">' . translate ( 'Categories' ) . '</th>
      </tr>
      <form action="" method="post" name="editCategories" '
 . 'onSubmit="sendCats (this)">
      <tr>
        <td valign="top">';

if ( ! empty ( $categories ) ) {
  echo '
          <select name="cats[]" size="10">
            <option disabled>' . translate ( 'AVAILABLE CATEGORIES' )
   . '</option>';

  foreach ( $categories as $K => $V ) {
    // None is index -1 and needs to be ignored
    if ( $K > 0 && ( $V['cat_owner'] == $login || $is_admin ||
        substr ( $form, 0, 4 ) == 'edit' ) ) {
      $tmpStr = $K . '" name="' . $V['cat_name'] . '">' . $V['cat_name'];
      echo '
            <option value="' . ( empty ( $V['cat_owner'] )
        ? "-$tmpStr" . '<sup>*</sup>' : $tmpStr ) . '</option>';
    } 
  } 
  echo '
          </select>';
} 
echo '
        </td>
        <td valign="center"><input type="button" value=">>" onclick="selAdd ()"'
 . ' /></td>
        <td align="center" valign="top">
          <select name="eventcats[]" size="9" multiple>
            <option disabled>' . translate ( 'ENTRY CATEGORIES' ) . '</option>';

if ( strlen ( $cats ) ) {
  foreach ( $eventcats as $K ) {
    // disable if not creator and category is Global
    $neg_num = $show_ast = '';
    $disabled = ( empty ( $categories[abs ( $K )]['cat_owner'] ) &&
      substr ( $form, 0, 4 ) != 'edit' ? 'disabled' : '' );
    if ( empty ( $categories[abs ( $K )]['cat_owner'] ) ) {
      $neg_num = '-';
      $show_ast = '*';
    } 
    echo '
            <option value="' . "$neg_num$K\" $disabled>"
     . $categories[abs ( $K )]['cat_name'] . $show_ast . '</option>';
  } 
} 

ob_end_flush ();

echo '
          </select>
          <input type="button" value="' . translate ( 'Remove' )
 . '" onclick="selRemove ()" />
        </td>
      </tr>
      <tr>
        <td valign="top" align="right">*' . translate ( 'Global Category' )
 . '&nbsp;&nbsp;&nbsp;<input type="button" value="' . translate ( 'Ok' )
 . '" onclick="sendCats ()" /></td>
        <td colspan="2" align="left">&nbsp;&nbsp;<input type="button" value="'
 . translate ( 'Cancel' ) . '" onclick="window.close ()" /></td>
      </tr>
      </form>
    </table>
    ' . print_trailer ( false, true, true );

?>
