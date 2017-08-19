<?php
/**
 * Description
 *   Handler for AJAX requests from layers.php.
 *   We use JSON for some of the data we send back to the AJAX request.
 *   Because JSON support was not built-in to PHP until 5.2, we have our
 *   own implmentation in includes/JSON.php.
 */
include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';
require_valid_referring_url ();

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';
include 'includes/JSON.php';
include 'includes/ajax.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();
load_user_preferences();
$WebCalendar->setLanguage();

$action = getValue ( 'action' );
$public = getValue ( 'public' );

$sendPlainText = false;
$format = getValue ( 'format' );
if ( ! empty ( $format ) &&
 ( $format == 'text' || $format == 'plain' ) );
$sendPlainText = true;

$error = '';

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
} else {
  $layer_user = $login;
}

if ( $action == 'enable' || $action == 'disable' ) {
  // Toggle LAYER_STATUS in the user's preferences between N and Y.
  dbi_execute( 'DELETE FROM webcal_user_pref WHERE cal_login = ?
    AND cal_setting = "LAYERS_STATUS"',  [$layer_user] );

  if( ! dbi_execute( 'INSERT INTO webcal_user_pref ( cal_login, cal_setting,
      cal_value ) VALUES ( ?, \'LAYERS_STATUS\', ? )',
      [$layer_user, ( $action === 'enable' ? 'Y' : 'N' )] ) ) {
    ajax_send_error ( translate ( 'Unable to update preference' ) . ': ' . dbi_error() );
  } else {
    // Success
    ajax_send_success();
  }
} else if ( $action == 'list' ) {
  // Use JSON to encode our list of layers.
  load_user_layers ( $layer_user, 1 );
  $ret_layers = [];
  foreach ( $layers as $layer ) {
    user_load_variables ( $layer['cal_layeruser'], 'layer' );
    $ret_layers[] =  ['id' => $layer['cal_layerid'],
      'source' => $layer['cal_layeruser'],
      'color' => $layer['cal_color'],
      'dups' => $layer['cal_dups'],
      'fullname' => $layerfullname];
  }
  ajax_send_object ( 'layers', $ret_layers, $sendPlainText );
} else if ( $action == 'save' ) {
  // TODO: we should do some additional checking here to make
  // sure someone isn't asking for a layer they are not authorized to view.
  if ( $ALLOW_VIEW_OTHER != 'Y' ) {
    $error = print_not_auth (7);
  } else {
    save_layer ( getPostValue('layeruser'),
      getPostValue('source'), getPostValue('color'),
      getPostValue('dups') == 'Y' ? 'Y' : 'N',
      getPostValue('id') );
  }
  if ( $error == '' )
    ajax_send_success();
  else
    ajax_send_error ( $error );
} else if ( $action == 'delete' ) {
  // TODO: we should so some additional checking here to make
  // sure someone isn't asking for a layer they are not authorized to view.
  if ( $ALLOW_VIEW_OTHER != 'Y' ) {
    $error = print_not_auth (7);
  } else {
    $id = getPostValue ( 'id' );
    if ( $id <= 0 ) {
      $error = translate('Invalid entry id.');
    } else {
      delete_layer ( getPostValue('layeruser'), $id );
    }
  }
  if ( $error == '' )
    ajax_send_success();
  else
    ajax_send_error ( $error );
} else {
  ajax_send_error ( translate('Unknown error.') );
}

exit;

function delete_layer ( $user, $id ) {
  global $error, $layers;

  if ( ! dbi_execute ( 'DELETE FROM webcal_user_layers ' .
   ' WHERE cal_layerid = ? AND cal_login = ?',
    [$id, $user] ) ) {
    $error = translate ( "Database error" ) . ": " . dbi_error();
  }
}

function save_layer ( $user, $source, $layercolor, $dups, $id ) {
  global $error, $layers;

  if ( $user == $source )
    $error = translate ( 'You cannot create a layer for yourself.' );

  load_user_layers ( $user, 1 );

  if ( ! empty ( $source ) && $error == '' ) {
    // Existing layer entry.
    if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) {
      // Update existing layer entry for this user.
      $layerid = $layers[$id]['cal_layerid'];

      dbi_execute ( 'UPDATE webcal_user_layers SET cal_layeruser = ?,
        cal_color = ?, cal_dups = ? WHERE cal_layerid = ?',
        [$source, $layercolor, $dups, $layerid] );
    } else {
      // New layer entry.
      // Check for existing layer for user. Can only have one layer per user.
      $res = dbi_execute ( 'SELECT COUNT( cal_layerid ) FROM webcal_user_layers
        WHERE cal_login = ? AND cal_layeruser = ?',
        [$user, $source] );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row[0] > 0 )
          $error = translate ( 'You can only create one layer for each user.' );

        dbi_free_result ( $res );
      }
      if ( $error == '' ) {
        $res = dbi_execute ( 'SELECT MAX( cal_layerid ) FROM webcal_user_layers' );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          $layerid = $row[0] + 1;
        } else
          $layerid = 1;
        dbi_execute ( 'INSERT INTO webcal_user_layers ( cal_layerid, cal_login,
          cal_layeruser, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )',
          [$layerid, $user, $source, $layercolor, $dups] );
      }
    }
  }
}


?>
