<?php
include_once 'includes/init.php';

// load user and global cats
load_user_categories ();

$error = "";

if ( $categories_enabled == "N" ) {
  send_to_preferred_view ();
  exit;
}

// If editing, make sure they are editing their own
// (or they are an admin user).
if ( ! empty ( $id ) ) {
  $res = dbi_query ( "SELECT cat_id, cat_owner FROM webcal_categories WHERE " .
    "cat_id = $id" );
  if ( $res ) {
    if ( $row = dbi_fetch_row ( $res ) ) {
      if ( $row[0] != $id )
        $error = translate ( "Invalid entry id" ) . ": " . $id;
      else if ( $row[1] != $login && ! $is_admin )
        $error = translate ( "You are not authorized" ) . ".";
    }
    dbi_free_result ( $res );
  } else {
    $error = translate("Database error") . ": " . dbi_error ();
  }
}

print_header();
?>
<h2><?php etranslate("Categories")?></h2>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />
<?php

if ( empty ( $add ) )
  $add = 0;

// Adding/Editing category
if ( ( ( $add == '1' ) || ( ! empty ( $id ) ) ) && empty ( $error ) ) {
  $button = translate("Add");
  ?>
  <form action="category_handler.php" method="post">
  <?php
  if ( ! empty ( $id ) ) {
    echo "<input name=\"id\" type=\"hidden\" value=\"$id\" />";
    $button = translate("Save");
    $catname = $categories[$id];
    $catowner = $category_owners[$id];
  } else {
    $catname = '';
  }
  ?>
  <?php etranslate("Category Name")?>: <input type="text" name="catname" size="20" value="<?php echo htmlspecialchars ( $catname ); ?>" />
  <br />
  <?php if ( $is_admin && empty ( $id ) ) { ?>
    <?php etranslate("Global")?>:
      <label><input type="radio" name="isglobal" value="N" <?php if ( ! empty ( $catowner ) || empty ( $id ) ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("No")?></label>&nbsp;&nbsp;
      <label><input type="radio" name="isglobal" value="Y" <?php if ( empty ( $catowner ) && ! empty ( $id ) ) echo " checked=\"checked\"";?> />&nbsp;<?php etranslate("Yes")?></label>
  <?php } ?>
  <br /><br />
  <input type="submit" name="action" value="<?php echo $button;?>" />
  <?php if ( ! empty ( $id ) ) {  ?>
	<input type="submit" name="action" value="<?php etranslate("Delete");?>" onclick="return confirm('<?php etranslate("Are you sure you want to delete this entry?"); ?>')" />
  <?php }  ?>
  </form>
  <?php
} else if ( empty ( $error ) ) {
  // Displaying Categories
  $global_found = false;
  if ( ! empty ( $categories ) ) {
    echo "<ul>";
    foreach ( $categories as $K => $V ) {
      echo "<li>";
      if ( $category_owners[$K] == $login || $is_admin )
        echo "<a href=\"category.php?id=$K\">$V</a>";
      else
        echo $V;
      if ( empty ( $category_owners[$K] ) ) {
        echo "<sup>*</sup>";
	$global_found = true;
      }
      echo "</li>\n";
    }
    echo "</ul>";
  }
  if ( $global_found )
    echo "<br /><br />\n<sup>*</sup> " . translate ( "Global" );
  echo "<p><a href=\"category.php?add=1\">" . translate("Add New Category") . "</a></p><br />\n";
}

if ( ! empty ( $error ) ) {
  echo "<span style=\"font-weight:bold;\">" . translate ( "Error" ) . ":</span>" . $error;
}
?>

<?php print_trailer(); ?>
</body>
</html>
