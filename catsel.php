<?php
include_once 'includes/init.php';

// load user and global cats
load_user_categories ();

$error = "";
$catList = $catNames = '';

if ( $CATEGORIES_ENABLED == "N" ) {
  exit;
}

$catNames = implode("," , $categories);
$catList = implode(",", array_keys($categories) );
$eventcats = explode("," , $cats);

$INC = array('js/catsel.php');
print_header($INC,'','',true, false, true);
?>
<table align="center"  border="0" width="250px">
<tr><th colspan="3"><?php etranslate("Categories")?></th></tr>
<form action="" method="post"  name="editCategories" onSubmit="sendCats(this)">
<?php
  echo "<tr><td valign=\"top\">\n";
  if ( ! empty ( $categories ) ) {
   echo "<select name=\"cats[]\" size=\"10\">\n" . 
    "<option disabled>AVAILABLE CATEGORIES</option>\n";
    foreach ( $categories as $K => $V ) {
 //     if ( $category_owners[$K] == $login || $is_admin ) {
        if ( empty ( $category_owners[$K] ) ) {
          echo "<option value=\"-$K\" name=\"$V\">$V<sup>*</sup>";
        } else {
          echo "<option value=\"$K\" name=\"$V\">$V";
        }
        echo "</option>\n";
 //     }
    }
  echo "</select>\n</td>";
  }
 echo "<td valign=\"center\"><input type=\"button\" value=\"  >  \" onclick=\"selAdd()\" /></td>";
  echo "<td align=\"center\" valign=\"top\">\n<select name=\"eventcats[]\" size=\"9\" multiple>\n" . 
    "<option disabled>EVENT CATEGORIES</option>\n";
  if ( ! empty ( $cats ) ) {
  foreach ( $eventcats as $K) {  
   //disable if not creator and category is Global
   $neg_num = $show_ast = "";
   $disabled = ( $category_owners[abs($K)] == NULL && $form != 'editentryform'? "disabled": "");
      if ( $category_owners[abs($K)] == NULL) {
     $neg_num = "-";
     $show_ast = "*";
   }
   echo "<option value=\"$neg_num$K\" name=\"$V\" $disabled>" . 
    $categories[abs($K)] . $show_ast . "</option>\n";
  }
 }
 echo "</select>\n"; 
 echo "<input type=\"button\" value=\"Remove\" onclick=\"selRemove()\" />";

  echo "</td></tr>\n"; 
?>
  <tr><td valign="top" align="right">*Global Category
 &nbsp;&nbsp;&nbsp;<input type="button" value="Ok" onclick="sendCats()" /></td>
 <td colspan="2" align="left">&nbsp;&nbsp;  
   <input type="button" value="Cancel" onclick="window.close()" />
 </td></tr>
 </form>
</table>


<?php print_trailer ( false, true, true ); ?>
</body>
</html>
