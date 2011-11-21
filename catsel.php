<?php // $Id$
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

ob_start();
setcookie( 'frm', $form );
print_header( '', '', '', true, false, true );

echo '
    <table align="center" width="90%" summary="">
      <tr>
        <th colspan="3">' . translate ( 'Categories' ) . '</th>
      </tr>
      <form action="" method="post" id="editCategories" name="editCategories">
      <tr>
        <td valign="top">';

if ( ! empty ( $categories ) ) {
  echo '
          <select name="cats[]" size="10">
            <option disabled>' . $availCatStr . '</option>';

  foreach ( $categories as $K => $V ) {
    // None is index -1 and needs to be ignored
    if ( $K > 0 && ( $V['cat_owner'] == $login || $is_admin ||
        substr ( $form, 0, 4 ) == 'edit' ) ) {
      $tmpStr = $K . '" name="' . $V['cat_name'] . '">' . $V['cat_name'];
      echo $option . ( empty ( $V['cat_owner'] )
        ? "-$tmpStr" . '<sup>*</sup>' : $tmpStr ) . '</option>';
    }
  }
  echo '
          </select>';
}
echo '
        </td>
        <td valign="center"><input type="button" id="selAdd" value=">>"></td>
        <td align="center" valign="top">
          <select name="eventcats[]" size="9" multiple>
            <option disabled>' . $entryCatStr . '</option>';

if ( strlen ( $cats ) ) {
  foreach ( $eventcats as $K ) {
    // disable if not creator and category is Global
    $show_ast = '';
    if ( empty ( $categories[abs ( $K )]['cat_owner'] ) ) {
      $show_ast = '*';
    }
    echo $option . $K
     .  ( empty( $categories[abs( $K )]['cat_owner'] )
      && substr( $form, 0, 4 ) != 'edit' ? '" disabled>' : '">' )
     . $categories[abs( $K )]['cat_name'] . $show_ast . '</option>';
  }
}

echo '
          </select>
          <input type="button" id="selRem" value="' . translate( 'Remove' ) . '>
        </td>
      </tr>
      <tr>
        <td valign="top" align="right">*' . translate ( 'Global Category' )
 . '&nbsp;&nbsp;&nbsp;<input type="button" id="sendCat" value="' . $okStr
 . '"></td>
        <td colspan="2" align="left">&nbsp;&nbsp;<input type="button" id="canCat"'
 . ' value="' . translate( 'Cancel' ) . '"></td>
      </tr>
      </form>
    </table>' . print_trailer( false, true, true );
ob_end_flush();

?>
