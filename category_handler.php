<?php
include_once 'includes/init.php';

// does the category belong to the user?
$is_my_event = false;
if ( empty ( $id ) ) {
  $is_my_event = true; // new event
} else {
  $res = dbi_query ( "SELECT cat_id, cat_owner FROM webcal_categories " .
    "WHERE cat_id = $id" );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row[0] == $id && $row[1] == $login )
      $is_my_event = true;
    else if ( $row[0] == $id && empty ( $row[1] ) && $is_admin )
      $is_my_event = true; // global category
    dbi_free_result ( $res );
  } else {
    $error = translate("Database error") . ": " . dbi_error ();
  }
}

if ( ! $is_my_event )
  $error = translate ( "You are not authorized" ) . ".";


if ( empty ( $error ) &&
  ( $action == "Delete" || $action == translate ("Delete") ) ) {
  // delete this category
  if ( $is_admin ) {
    if ( ! dbi_query ( "DELETE FROM webcal_categories " .
      "WHERE cat_id = $id AND " .
      "( cat_owner = '$login' OR cat_owner IS NULL )" ) )
      $error = translate ("Database error") . ": " . dbi_error();
  } else {
    if ( ! dbi_query ( "DELETE FROM webcal_categories " .
      "WHERE cat_id = $id AND cat_owner = '$login'" ) )
      $error = translate ("Database error") . ": " . dbi_error();
  }
      
  // Set any events in this category to NULL
  if ( ! dbi_query ( "UPDATE webcal_entry_user SET cal_category = NULL " .
    "WHERE cal_category = $id" ) )
    $error = translate ("Database error") . ": " . dbi_error();
} else if ( empty ( $error ) ) {
  if ( ! empty ( $id ) ) {
    # update (don't let them change global status)
    $sql = "UPDATE webcal_categories SET cat_name = '$catname' " .
      "WHERE cat_id = $id";
    if ( ! dbi_query ( $sql ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
    // add new category
    // get new id
    $res = dbi_query ( "SELECT MAX(cat_id) FROM webcal_categories" );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0] + 1;
      dbi_free_result ( $res );
      if ( $is_admin ) {
        if ( $isglobal == "Y" )
          $catowner = "NULL";
        else
          $catowner = "'$login'";
      } else
        $catowner = "'$login'";
      $sql = "INSERT INTO webcal_categories " .
        "( cat_id, cat_owner, cat_name ) " .
        "VALUES ( $id, $catowner, '$catname' )";
      if ( ! dbi_query ( $sql ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  }
}
if ( empty ( $error ) )
  do_redirect ( "category.php" );

print_header();
?>
<h2><?php etranslate("Error")?></h2>

<blockquote>
<?php echo $error; ?>
</blockquote>

<?php print_trailer(); ?>
</body>
</html>
