<?php
include_once 'includes/init.php';

$page = getPostValue ( 'page' );
$name = getPostValue ( 'name' );

//we're processing edit_remotes Calendar ID field
if ( $page == 'edit_remotes' || $page == 'edit_nonuser') {
   $rows = dbi_get_cached_rows ( "SELECT cal_login FROM webcal_nonuser_cals" );  
  if ( $rows ) {
    foreach ( $rows as $row ) {
      // assuming we are using '_NUC_' as $NONUSER_PREFIX
      if ( $name ==  substr ( $row[0],  strlen ( $NONUSER_PREFIX ) ) )
        echo translate ( "Duplicate Name", true ) . ": $name";
    }
  }

} else

//we're processing edit_user Calendar ID field
if ( $page == 'edit_user' ) {
   $rows = dbi_get_cached_rows ( "SELECT cal_login FROM webcal_user" );  
  if ( $rows ) {
    foreach ( $rows as $row ) {
      if ( $name ==  $row[0] )
        echo translate ( "Duplicate Name", true ) . ": $name";
    }
  }

}
?>
