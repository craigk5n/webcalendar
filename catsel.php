<?php
/* $Id$ */
include_once 'includes/init.php';

// load user and global cats
load_user_categories ();

$error = '';
$catList = $catNames = '';

if ( $CATEGORIES_ENABLED == 'N' ) {
  exit;
}

$form = getGetValue ( 'form' );
$cats = getGetValue ( 'cats' );
$header_text = translate( 'ENTRY CATEGORIES' );


$eventcats = explode(',' , $cats);

$INC = array("js/catsel.php/false/$form");
print_header($INC,'','',true, false, true);
?>
<table align="center"  border="0" width="250px">
<tr><th colspan="3"><?php etranslate( 'Categories' )?></th></tr>
<form action="" method="post"  name="editCategories" onSubmit="sendCats(this)">
<?php
  echo "<tr><td valign=\"top\">\n";
  if ( ! empty ( $categories ) ) {
   echo '<select name="cats[]" size="10">' . "\n" . 
    '<option disabled>' . translate( 'AVAILABLE CATEGORIES' ) . '</option>';
    foreach ( $categories as $K => $V ) {
      //None is index -1 and needs to be ignored
      if ( $K > 0 && ( $V['cat_owner'] == $login || $is_admin ||
          substr ( $form, 0, 4 ) == 'edit' ) ) {
        $tmpStr = $K . '" name="' . $V['cat_name'] . '">' . $V['cat_name'];
        echo '
              <option value="' . ( empty ( $V['cat_owner'] )
          ? "-$tmpStr" . '<sup>*</sup>' : $tmpStr ) . '</option>';
      }
    }
  echo '</select>
  </td>';
  }
  echo '<td valign="center"><input type="button" value=">>" onclick="selAdd()" /></td>';
  echo '<td align="center" valign="top"><select name="eventcats[]" size="9" multiple>' ."\n" . 
    '<option disabled>' . $header_text . "</option>\n";
  if ( strlen ( $cats ) ) {
  foreach ( $eventcats as $K) {  
   //disable if not creator and category is Global
   $neg_num = $show_ast = '';
   $disabled = ( empty ( $categories[abs($K)]['cat_owner'] ) && 
     substr($form,0,4) != 'edit'? 'disabled': '');
     if ( empty ( $categories[abs($K)]['cat_owner'] ) ) {
       $neg_num = "-";
       $show_ast = "*";
     }
   echo "<option value=\"$neg_num$K\"  $disabled>" . 
    $categories[abs($K)]['cat_name'] . $show_ast . "</option>\n";
  }
 }
 echo "</select>\n"; 
 echo '<input type="button" value="' . translate( 'Remove' ) . 
   '" onclick="selRemove()" />';

  echo "</td></tr>\n"; 
?>
  <tr><td valign="top" align="right">*<?php etranslate( 'Global Category' ) ?>
 &nbsp;&nbsp;&nbsp;<input type="button" value="<?php etranslate( 'Ok' ) ?>" onclick="sendCats()" /></td>
 <td colspan="2" align="left">&nbsp;&nbsp;  
   <input type="button" value="<?php etranslate( 'Cancel' ) ?>" onclick="window.close()" />
 </td></tr>
 </form>
</table>


<?php echo print_trailer ( false, true, true ); ?>

