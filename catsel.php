<?php // $Id: catsel.php,v 1.28.2.1 2013/01/24 21:15:09 cknudsen Exp $
include_once 'includes/init.php';

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

print_header( array( 'js/catsel.php/false/' . $form ),
  '<script type=text/javascript" src="includes/js/catsel.js"></script>',
  '', true, false, true );
echo '
    <table class="aligncenter" width="90%">
      <tr>
        <th colspan="3">' . translate ( 'Categories' ) . '</th>
      </tr>
      <form action="" method="post" name="editCategories" '
 . 'onSubmit="sendCats( this )">
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
        <td class="alignmiddle"><input type="button" value=">>" onclick="selAdd()"'
 . ' /></td>
        <td class="aligncenter aligntop">
          <select name="eventcats[]" size="9"  multiple="multiple">
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
          <input type="button" value="' . translate ( 'Remove' )
 . '" onclick="selRemove()" />
        </td>
      </tr>
      <tr>
        <td class="aligntop alignright">*' . translate ( 'Global Category' )
 . '&nbsp;&nbsp;&nbsp;<input type="button" value="' . translate ( 'OK' )
 . '" onclick="sendCats()" /></td>
        <td colspan="2" class="alignleft">&nbsp;&nbsp;<input type="button" value="'
 . translate ( 'Cancel' ) . '" onclick="window.close()" /></td>
      </tr>
      </form>
    </table>
    ' . print_trailer ( false, true, true );

?>
