<?php_track_vars?>
<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();

include "includes/translate.inc";

$my_event = false;
$can_edit = false;

// First, check to see if this user should be able to delete this event.
if ( $id > 0 ) {
  // first see who has access to edit this entry
  if ( $is_admin ) {
    $can_edit = true;
  } else if ( $readonly ) {
    $can_edit = false;
  } else {
    $can_edit = false;
    $sql = "SELECT webcal_entry.cal_id FROM webcal_entry, " .
      "webcal_entry_user WHERE webcal_entry.cal_id = " .
      "webcal_entry_user.cal_id AND webcal_entry.cal_id = $id " .
      "AND (webcal_entry.cal_create_by = '$login' " .
      "OR webcal_entry_user.cal_login = '$login')";
    $res = dbi_query ( $sql );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row && $row[0] > 0 )
        $can_edit = true;
      dbi_free_result ( $res );
    }
  }
}

// See who owns the event.  Owner should be able to delete.
$res = dbi_query (
  "SELECT cal_create_by FROM webcal_entry WHERE cal_id = $id" );
if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $owner = $row[0];
  dbi_free_result ( $res );
  if ( $owner == $login ) {
    $my_event = true;
    $can_edit = true;
  }
}


if ( ! $can_edit ) {
  $error = translate ( "You are not authorized" );
}

if ( $id > 0 && empty ( $error ) ) {
  if ( ! empty ( $date ) ) {
    $thisdate = $date;
  } else {
    $res = dbi_query ( "SELECT cal_date FROM webcal_entry WHERE cal_id = $id" );
    if ( $res ) {
      // date format is 19991231
      $row = dbi_fetch_row ( $res );
      $thisdate = $row[0];
    }
  }

  // Only allow delete of webcal_entry & webcal_entry_repeats
  // if owner or admin, not participant.
  if ( $is_admin || $my_event ) {

    // Email participants that the event was deleted
  
    $sql = "SELECT cal_login FROM webcal_entry_user WHERE cal_id = $id";
    $res = dbi_query ( $sql );
    $partlogin = array ();
    if ( $res ) {
      while ( $row = dbi_fetch_row ( $res ) ) {
        if ( $row[0] != $login )
	  $partlogin[] = $row[0];
      }
      dbi_free_result($res);
    }

    // Get event name
    $sql = "SELECT cal_name FROM webcal_entry WHERE cal_id = $id";
    $res = dbi_query($sql);
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $name = $row[0];
      dbi_free_result ( $res );
    }
  
  
    for ( $i = 0; $i < count ( $partlogin ); $i++ ) {
      $do_send = get_pref_setting ( $partlogin[$i], "EMAIL_EVENT_DELETED" );
      user_load_variables ( $partlogin[$i], "temp" );
      if ( $partlogin[$i] != $login && $do_send == "Y" &&
        strlen ( $tempemail ) ) {
        $msg = translate("Hello") . ", " . $tempfullname . ".\n\n" .
          translate("An appointment has been canceled for you by") .
          " " . $login_fullname .  ". " .
          translate("The subject was") . " \"" . $name . "\"\n\n";
        if ( strlen ( $login_email ) )
          $extra_hdrs = "From: $login_email\nX-Mailer: " . translate("Title");
        else
          $extra_hdrs = "From: $email_fallback_from\nX-Mailer: " . translate("Title");
        mail ( $tempemail,
          translate("Title") . " " . translate("Notification") . ": " . $name,
          $msg, $extra_hdrs );
      }
    }

    dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
    dbi_query ( "DELETE FROM webcal_reminder_log WHERE cal_id = $id" );
  } else {
    // not the owner of the event and are not the admin
    // just delete the event from this user's calendar and leave it for
    // everyone else, unless this user is the only participant, in which
    // case, we delete everything about this event.
    $res = dbi_query (
      "SELECT COUNT(cal_login) FROM webcal_entry_user " .
      "WHERE cal_id = $id" );
    $delete_all = FALSE;
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      if ( $row[0] <= 1 )
        $delete_all = TRUE;
      dbi_free_result ( $res );
    }
    
    if ( $delete_all ) {
      dbi_query ( "DELETE FROM webcal_entry WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_user WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_entry_repeats WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_site_extras WHERE cal_id = $id" );
      dbi_query ( "DELETE FROM webcal_reminder_log WHERE cal_id = $id" );
    } else {
      dbi_query ( "DELETE FROM webcal_entry_user " .
        "WHERE cal_id = $id AND cal_login = '$login'" );
    }
  }
}

if ( strlen ( get_last_view() ) ) {
  $url = get_last_view();
} else {
  $redir = "";
  if ( $thisdate != "" )
    $redir = "?date=$thisdate";
  if ( $user != "" ) {
    if ( $redir != "" )
      $redir .= "&";
    $redir .= "user=$user";
  }
  $url = "$STARTVIEW.php" . $redir;
}
if ( empty ( $error ) ) {
  do_redirect ( $url );
  exit;
}
?>
<HTML>
<HEAD><TITLE><?php etranslate("Title")?></TITLE>
<?php include "includes/styles.inc"; ?>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php etranslate("Error")?></H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php include "includes/trailer.inc"; ?>

</BODY>
</HTML>
