<?php
include_once 'includes/init.php';

$icon_path = "icons/";

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

if ( ! empty ( $_FILES['FileName'] ) ) {
  $file = $_FILES['FileName'];
} else if ( ! empty ( $HTTP_POST_FILES['FileName'] ) ) {
  $file = $HTTP_POST_FILES['FileName'];
}

if ( ! $is_my_event )
  $error = translate ( "You are not authorized" ) . ".";

$delete = getPostValue ( 'delete' );
if ( empty ( $error ) && ! empty ( $delete ) ) {
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
  if ( $is_admin ) {
    if ( !  dbi_query ( "DELETE FROM webcal_entry_categories WHERE cal_id = $id AND " .
      " ( cat_owner = '$login' OR cat_owner IS NULL)" ) ) 
    $error = translate ("Database error") . ": " . dbi_error();
  } else {
    if ( !  dbi_query ( "DELETE FROM webcal_entry_categories WHERE cal_id = $id " .
      "AND cat_owner = '$login'" ) )
    $error = translate ("Database error") . ": " . dbi_error();
 }
 
  //Rename any icons associated with this cat_id
  $catIcon = $icon_path ."cat-" . $id . ".gif";
  $bakIcon = $icon_path . "cat-" . date ("YmdHis" ) . ".gif";
  if ( file_exists ( $catIcon ) ){
    rename ( $catIcon, $bakIcon );
  } 

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
  //Save icon if uploaded
  if ( ! empty ( $file['tmp_name'] ) && $file['type'] == 'image/gif' ){
    //$icon_props = getimagesize ( $file['tmp_name']  );
    //print_r ($icon_props );
    $path_parts = pathinfo( $_SERVER['SCRIPT_FILENAME']);
    $catIcon =  $icon_path . "cat-" . $id . ".gif";
    $fullIcon = $path_parts['dirname'] . "/" .$catIcon;
    $bakIcon = $icon_path . "cat-" . date ("YmdHis" ) . ".gif";
    if ( file_exists ( $catIcon ) )
      rename ( $catIcon, $bakIcon );
    $file_result = move_uploaded_file ( $file['tmp_name'] , $fullIcon );
    //echo "Upload Result:" . $file_result;
  }
  //Copy icon if local file specified
  $urlname = getPostvalue ( 'urlname' );
  if ( ! empty ( $urlname ) && file_exists ( $icon_path . $urlname  )  ) {
    copy ( $icon_path . $urlname, $icon_path . "cat-" . $id . ".gif" );
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
