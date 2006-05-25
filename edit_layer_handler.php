<?php
/* $Id$ */
include_once 'includes/init.php';

$error = '';

if ( $ALLOW_VIEW_OTHER != 'Y' ) {
  $error = translate( 'You are not authorized' );
}

if ( empty ( $dups ) )
  $dups = 'N';

$updating_public = false;
if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $updating_public = true;
  $layer_user = '__public__';
} else if ( empty ( $cal_login ) ){
  $layer_user = $login;
  save_layer ( $layer_user, $layeruser, $layercolor, $dups, $id );
} else 
//see if we are processing multiple layer_users as admin
if ( $is_admin && ! empty ( $cal_login ) ) {
  for ( $i=0, $cnt = count ( $cal_login ); $i < $cnt; $i++ ) {
    save_layer ( $cal_login[$i], $layeruser, $layercolor, 'N', $id ); 
  }
}



function save_layer ( $layer_user, $layeruser, $layercolor, $dups, $id ) {
  global $error, $layers;
  if ( $layer_user == $layeruser )
    $error = translate ( 'You cannot create a layer for yourself' ) . '.';
  
  load_user_layers ( $layer_user, 1 );
  
  if ( ! empty ( $layeruser ) && $error == '' ) {
    // existing layer entry
    if ( ! empty ( $layers[$id]['cal_layeruser'] ) ) {
      // update existing layer entry for this user
      $layerid = $layers[$id]['cal_layerid'];
  
      dbi_execute ( 'UPDATE webcal_user_layers SET cal_layeruser = ?, ' .
        'cal_color = ?, cal_dups = ? WHERE cal_layerid = ?', 
        array( $layeruser, $layercolor, $dups, $layerid ) );
  
    } else {
      // new layer entry
      // check for existing layer for user.  can only have one layer per user
      $res = dbi_execute ( 'SELECT COUNT(cal_layerid) FROM webcal_user_layers ' .
        'WHERE cal_login = ? AND cal_layeruser = ?', array( $layer_user, $layeruser ) );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        if ( $row[0] > 0 ) {
          $error = translate ( 'You can only create one layer for each user' ) . '.';
        }
        dbi_free_result ( $res );
      }
      if ( $error == '' ) {
        $res = dbi_execute ( 'SELECT MAX(cal_layerid) FROM webcal_user_layers' );
        if ( $res ) {
          $row = dbi_fetch_row ( $res );
          $layerid = $row[0] + 1;
        } else {
          $layerid = 1;
        }
        dbi_execute ( 'INSERT INTO webcal_user_layers ( cal_layerid, cal_login, ' .
          'cal_layeruser, cal_color, cal_dups ) VALUES ( ?, ?, ?, ?, ? )', 
          array( $layerid, $layer_user, $layeruser, $layercolor, $dups ) );
      }
    }
  }
}

//We don't want to throw error if doing a multiple save
if ( $error == '' || ! empty ( $cal_login ) ) {
  if ( $updating_public )
    do_redirect ( 'layers.php?public=1' );
  else
    do_redirect ( 'layers.php' );
  exit;
}

print_header();
?>

<h2><?php etranslate( 'Error' )?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php echo print_trailer(); ?>

</body>
</html>
