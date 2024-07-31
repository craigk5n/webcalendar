<?php
require_once 'includes/init.php';

// load user and global cats
load_user_categories();

$catList = $catNames = $error = '';

if ( $CATEGORIES_ENABLED == 'N' )
  exit;

$cats = getGetValue ( 'cats' );
$form = getGetValue ( 'form' );

$eventcats = explode ( ',', $cats );

$availCatStr = translate ( 'AVAILABLE CATEGORIES' );
$availCatFiller = str_repeat( '&nbsp;', ( 30 - strlen ( $availCatStr ) ) / 2 );
if ( strlen ( $availCatStr ) < 30 )
  $availCatStr = $availCatFiller . $availCatStr . $availCatFiller;

$entryCatStr = translate ( 'ENTRY CATEGORIES' );
$entryCatFiller = str_repeat( '&nbsp;', ( 30 - strlen ( $entryCatStr ) ) / 2 );
if ( strlen ( $entryCatStr ) < 30 )
  $entryCatStr = $entryCatFiller . $entryCatStr . $entryCatFiller;

print_header ( ['js/catsel.php/false/' . $form, 'js/catsel.js'],
  '', '', true, false, true );
echo '
    <table class="aligncenter" width="90%">
      <tr>
        <th colspan="3">' . translate ( 'Categories' ) . '</th>
      </tr>
      <form action="" method="post" name="editCategories" '
 . 'onSubmit="sendCats( this )">' . csrf_form_key() . '
      <tr>
        <td class="aligntop">';

if ( ! empty ( $categories ) ) {
  echo '
          <select name="cats[]" size="10">
            <option disabled>' . $availCatStr . '</option>';

  foreach ( $categories as $K => $V ) {
    // None is index -1 and needs to be ignored
    if ( $K > 0 && ( $V['cat_owner'] == $login || $is_admin ||
        substr ( $form, 0, 4 ) == 'edit' ) ) {
      $tmpStr = $K .
        '" name="' . htmlentities ( $V['cat_name'] ) .
        '">' . htmlentities ( $V['cat_name'] );
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
        <td class="AlignMiddle"><button type="button" onclick="selAdd()">>>'
  . '</button></td>
        <td class="aligncenter aligntop">
          <select name="eventcats[]" size="9" multiple>
            <option disabled>' . $entryCatStr . '</option>';

if ( strlen ( $cats ) ) {
  foreach ( $eventcats as $K ) {
    // disable if not creator and category is Global
    $show_ast = '';
    $disabled = ( empty ( $categories[abs ( $K )]['cat_owner'] ) &&
      substr ( $form, 0, 4 ) != 'edit' ? 'disabled' : '' );
    if ( empty ( $categories[abs ( $K )]['cat_owner'] ) ) {
      $show_ast = '*';
    }
    echo '
            <option value="' . "$K\" $disabled>"
     . htmlentities ( $categories[abs ( $K )]['cat_name'] ) . $show_ast . '</option>';
  }
}
echo '
          </select>
          <button type="button" onclick="selRemove()">'
  . translate ( 'Remove' ) . '</button>
        </td>
      </tr>
      <tr>
        <td class="aligntop alignright">*' . translate ( 'Global Category' )
  . '&nbsp;&nbsp;&nbsp;<button type="button" onclick="sendCats()">'
  . translate ( 'OK' ) . '</button></td>
        <td class="AlignLeft" colspan="2"><button type="button" '
  . 'onclick="window.close()">' . translate ( 'Cancel' ) . '</button></td>
      </tr>
      </form>
    </table>
    ' . print_trailer ( false, true, true );

?>
