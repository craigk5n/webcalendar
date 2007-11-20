<?php
/*
 * $Id$
 *
 * Page Description:
 *  This page will handle adding blobs into the database.  It will
 *  present the form page on a GET and handle updating the database
 *  on a POST.
 *  This includes:
 *    Add comment to an event
 *    Add attachment to an event
 *
 * Input Parameters:
 *  For GET:
 *    id - event id (optional for some types)
 *    type - C=comment, A=attachment
 *  For POST:
 *    id - event id (optional for some types)
 *    type - C=comment, A=attachment
 *    description - (for type=C and A)
 *    comment - (for type=C)
 *    FileName - (for type=A)
 *
 * Comments:
 *  TODO: add email notification when attachment or comment is added
 */
include_once 'includes/init.php';

$type = $WC->getValue ( 'type' );

$eid = $WC->getId();

$error = '';

switch ( $type ) {
  case 'C':
    if ( empty ( $eid ) )
      $error = 'No id specified';
    $smarty->assign ( 'title', translate ( 'Add Comment' ) );
    break;
  case 'A':
    if ( empty ( $eid ) )
      $error = 'No id specified';
    $smarty->assign ( 'title', translate ( 'Add Attachment' ) );
    $upload = ini_get ( 'file_uploads' );
    $upload_enabled = ! empty ( $upload ) &&
      preg_match ( "/(On|1|true|yes)/i", $upload );
    if ( ! $upload_enabled ) {
      $error = 'You must enable file_uploads in php.ini';
    }
    break;
  default:
    $error = 'Invalid type';
    break;
}

$can_add = false;
if ( $WC->isAdmin() )
  $can_add = true;

// Get event details if this is associated with an event
if ( empty ( $error ) && ! empty ( $eid ) ) {
  // is this user a participant or the creator of the event?
  $sql = 'SELECT we.cal_id FROM webcal_entry we, webcal_entry_user weu 
    WHERE we.cal_id = weu.cal_id AND we.cal_id = ?
    AND (we.cal_create_by = ? OR weu.cal_login_id = ?)';
  $res = dbi_execute ( $sql, array( $eid, $WC->loginId(), 
    $WC->loginId() ) );
  if ( $res ) {
    $row = dbi_fetch_row ( $res );
    if ( $row && $row[0] > 0 ) {
      $is_my_event = true; // user is participant
    }
    dbi_free_result ( $res );
  }
}

if ( $type == 'A' ) {
  if ( ! getPref ( '_ALLOW_ATTACH' ) )
    $error = print_not_auth ();
  else if ( empty ( $error ) && getPref ( '_ALLOW_ATTACH_PART' ) && $is_my_event )
    $can_add = true;
  else if ( getPref ( '_ALLOW_ATTACH_ANY' ) )
    $can_add = true;
} else if ( $type == 'C' ) {
  if ( ! getPref ( '_ALLOW_COMMENTS' ) )
    $error = print_not_auth ();
  else if ( empty ( $error ) && getPref ( '_ALLOW_COMMENTS_PART' ) && $is_my_event )
    $can_add = true;
  else if ( getPref ( '_ALLOW_COMMENTS_ANY' ) )
    $can_add = true;
}
//check UAC
$can_add = $can_add || access_user_calendar ( 'edit', $WC->userId() );


if ( ! $can_add )
  $error = print_not_auth ();

if ( ! empty ( $error ) ) {
  build_header ();
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

// Handle possible POST first
if ( empty ( $REQUEST_METHOD ) )
  $REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];
if ( $REQUEST_METHOD == 'POST' ) {

  // get next id first
  $res = dbi_execute ( 'SELECT MAX(cal_blob_id) FROM webcal_blob' );
  if ( ! $res ) {
    die_miserable_death ( translate( 'Database error' ) . ': ' .
      dbi_error () );
  }
  if ( $row = dbi_fetch_row ( $res ) )
    $nextid = $row[0] + 1;
  else
    $nextid = 1;
  dbi_free_result ( $res );

  if ( $type == 'C' ) {
    // Comment
    $description = $WC->getValue ( 'description' );
    $comment = $WC->getValue ( 'comment' );
    $sql = 'INSERT INTO webcal_blob ( cal_blob_id, cal_id, cal_login_id, 
      cal_name, cal_description, cal_size, cal_mime_type, cal_type, 
      cal_mod_date, cal_blob ) 
      VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
    if ( ! dbi_execute ( $sql, array( $nextid, $eid, $WC->loginId(), NULL, 
      $description, 0, 'text/plain', 'C', time(), NULL ) ) ) {
      $error = db_error ();
    } else {
      if ( ! dbi_update_blob ( 'webcal_blob', 'cal_blob',
        "cal_blob_id = $nextid", $comment ) ) {
        $error = db_error ();
      } else {
        // success!  redirect to view event page
        activity_log ( $eid, $WC->loginId(), $WC->loginId(), 
		  LOG_COMMENT, '' );
        do_redirect ( "view_entry.php?eid=$eid" );
      }
    }
  } else if ( $type == 'A' ) {
    // Attachment
    $description = $WC->getValue ( 'description' );
    if ( ! empty ( $_FILES['FileName'] ) )
      $file = $_FILES['FileName'];
    if ( empty ( $file['file'] ) )
      $error = 'File Upload error!<br/>';

    //print_r ( $file ); exit;
    $mimetype = $file['type'];
    $filesize = $file['size'];
    $filename = $file['name'];
    $tmpfile = $file['tmp_name'];
    if ( empty ( $description ) )
      $description = $filename;

    $data = '';
    $fd = @fopen ( $tmpfile, 'r' );
    if ( ! $fd )
      die_miserable_death ( "Error reading temp file: $tmpfile" );
    if ( ! empty ( $error ) ) {
      while ( ! feof ( $fd ) ) {
        $data .= fgets ( $fd, 4096 );
      }
    }
    fclose ( $fd );

    $comment = $WC->getValue ( 'description' );
    $sql = 'INSERT INTO webcal_blob ( cal_blob_id, cal_id, cal_login_id, 
      cal_name, cal_description, cal_size, cal_mime_type, cal_type, 
      cal_mod_date, cal_blob ) 
      VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )';
    if ( ! dbi_execute ( $sql, array( $nextid, $eid, 
	  $WC->loginId(), $filename, 
      $description, $filesize, $mimetype, 'A', time(), NULL ) ) ) {
      $error = db_error ();
    } else {
      if ( ! dbi_update_blob ( 'webcal_blob', 'cal_blob',
        "cal_blob_id = $nextid", $data ) ) {
        $error = db_error ();
      } else {
        // success!  redirect to view event page
        activity_log ( $eid, $WC->loginId(), $WC->loginId(), 
		  LOG_ATTACHMENT, $filename );
        do_redirect ( "view_entry.php?eid=$eid" );
      }
    }
  } else {
    die_miserable_death ( 'Unsupported type' ); // programmer error
  }
  if ( ! empty ( $error ) ) {
    build_header ();
    echo print_error ( $error );
    echo print_trailer ();
    exit;
  }
}

// Do we use FCKEditor?
if ( getPref ( '_ALLOW_HTML_DESCRIPTION' ) ){
  if ( file_exists ( 'includes/FCKeditor-2.0/fckeditor.js' ) &&
    file_exists ( 'includes/FCKeditor-2.0/fckconfig.js' ) ) {
    $smarty->assign ( 'use_fckeditor', true );
  }
}


build_header ();

$smarty->assign ( 'type', $type );

$smarty->display ( 'docadd.tpl' );
 ?>

