<?php
include_once 'includes/init.php';

$icon_path = "icons/";
$icon_max_size = '3000';

// does the category belong to the user?
$is_my_event = false;
if ( empty ( $id ) ) {
  $is_my_event = true; // new event
} else {
  $res = dbi_execute ( "SELECT cat_id, cat_owner FROM webcal_categories " .
    "WHERE cat_id = ?", array( $id ) );
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
    if ( ! dbi_execute ( "DELETE FROM webcal_categories " .
      "WHERE cat_id = ? AND " .
      "( cat_owner = ? OR cat_owner IS NULL )", array( $id, $login ) ) )
      $error = translate ("Database error") . ": " . dbi_error();
  } else {
    if ( ! dbi_execute ( "DELETE FROM webcal_categories " .
      "WHERE cat_id = ? AND cat_owner = ?", array( $id, $login ) ) )
      $error = translate ("Database error") . ": " . dbi_error();
  }
      
  // Set any events in this category to NULL
  if ( $is_admin ) {
    if ( !  dbi_execute ( "DELETE FROM webcal_entry_categories WHERE cal_id = ? AND " .
      " ( cat_owner = ? OR cat_owner IS NULL)", array( $id, $login ) ) ) 
    $error = translate ("Database error") . ": " . dbi_error();
  } else {
    if ( !  dbi_execute ( "DELETE FROM webcal_entry_categories WHERE cal_id = ? " .
      "AND cat_owner = ?", array( $id, $login ) ) )
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
    $sql = "UPDATE webcal_categories SET cat_name = ? " .
      "WHERE cat_id = ?";
    if ( ! dbi_execute ( $sql, array( $catname, $id ) ) ) {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  } else {
    // add new category
    // get new id
    $res = dbi_execute ( "SELECT MAX(cat_id) FROM webcal_categories" );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0] + 1;
      dbi_free_result ( $res );
      if ( $is_admin ) {
        if ( $isglobal == "Y" )
          $catowner = NULL;
        else
          $catowner = $login;
      } else
        $catowner = $login;
      $sql = "INSERT INTO webcal_categories " .
        "( cat_id, cat_owner, cat_name ) " .
        "VALUES ( ?, ?, ? )";
      if ( ! dbi_execute ( $sql, array( $id, $catowner, $catname ) ) ) {
        $error = translate ("Database error") . ": " . dbi_error();
      }
    } else {
      $error = translate ("Database error") . ": " . dbi_error();
    }
  }
	if (  is_dir($icon_path) && ( ! empty ( $ENABLE_ICON_UPLOADS ) && 
	  $ENABLE_ICON_UPLOADS == "Y" || $is_admin ) ) { 
		//Save icon if uploaded
		if ( ! empty ( $file['tmp_name'] ) && $file['type'] == 'image/gif' &&
		  $file['size'] <= $icon_max_size ){
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
		} else if ( ! empty ( $file['tmp_name'] ) && $file['size'] > $icon_max_size ){
		  $error = translate ( "File size exceeds maximum" ) ;
		} else if ( ! empty ( $file['tmp_name'] ) && $file['type'] != 'image/gif' ){
		  $error = translate ( "File is not a gif image" ) ;
		}
		//Copy icon if local file specified
		$urlname = getPostvalue ( 'urlname' );
		if ( ! empty ( $urlname ) && file_exists ( $icon_path . $urlname  )  ) {
			copy ( $icon_path . $urlname, $icon_path . "cat-" . $id . ".gif" );
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
