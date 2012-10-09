<?php /* $Id$ */
/**
 * This page handles managing a user's layers
 * and works with layer_ajax.php to make changes.
 */
include_once 'includes/init.php';
send_no_cache_header();

$layer_user = $login;
$public = getValue( 'public' );
$u_url = '';
$updating_public = false;

if ( $is_admin && ! empty ( $public ) && $PUBLIC_ACCESS == 'Y' ) {
  $layer_user = '__public__';
  $u_url = '&amp;public=1';
  $updating_public = true;
}

load_user_layers ( $layer_user, 1 );

$layers_enabled = 0;
$res = dbi_execute ( 'SELECT cal_value FROM webcal_user_pref
  WHERE cal_setting = \'LAYERS_STATUS\' AND cal_login = ?',
  array ( $layer_user ) );

if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $layers_enabled = ( $row[0] == 'Y' ? 1 : 0 );
  dbi_free_result ( $res );
}

$areYouSureStr   = translate( 'really delete layer' );
$deleteLayerStr  = translate( 'Delete layer' );
$deleteStr       = translate( 'Delete' );
$disabledStr     = translate( 'Disabled' );
$editLayerStr    = translate( 'Edit layer' );
$editStr         = translate( 'Edit' );
$enableLayersStr = translate( 'Enable layers' );
$layerStr        = translate( 'Layer' );
$LAYERS_DISABLED = translate( 'Layers are currently disabled.' );
$LAYERS_ENABLED  = translate( 'Layers are currently enabled.' );

$LOADING = '<center><img src="images/loading_animation.gif" alt=""></center>';
$public_link = str_replace( 'XXX', $PUBLIC_ACCESS_FULLNAME,
  translate( 'modify XXX layers settings' ) );

$headExtras = '<link href="includes/tabcontent/tabcontent.css" rel="stylesheet">
    <link href="includes/js/modalbox/modalbox.css" rel="stylesheet" media="screen">
    <script src="includes/js/visible.js"></script>
    <script src="includes/js/modalbox/modalbox.js"></script>
    <script src="includes/tabcontent/tabcontent.js"></script>';

ob_start();
print_header ( '', $headExtras, 'onload="load_layers();"' );

if ( $ALLOW_VIEW_OTHER != 'Y' )
  echo print_not_auth();
else {
  if ( empty ( $PUBLIC_ACCESS ) )
    $PUBLIC_ACCESS = 'N';

  echo ( $is_admin && empty ( $public ) && $PUBLIC_ACCESS == 'Y' ? '
    <div class="rightsidetip">
      <a href="layers.php?public=1">' . $public_link . '</a>
    </div>' : '' ) . '
    <h2>' . ( $updating_public
    ? translate ( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' : '' )
   . translate ( 'Layers' ) . '&nbsp;<img src="images/help.gif" alt="'
   . translate ( 'Help' ) . '" class="help" onclick="window.open( '
   . '\'help_layers.php\', \'cal_help\', \'dependent,menubar,scrollbars,'
   . 'height=400,width=400,innerHeight=420,outerWidth=420\' );"></h2>
    ' . display_admin_link() . '
    <form>
      <div class="note">
        <span id="layerstatus">'
    . ( $layers_enabled ? $LAYERS_ENABLED : $LAYERS_DISABLED )
    . '</span> &nbsp; &nbsp; &nbsp;
        <input type="button" onclick="return set_layer_status(true);" value="'
    . $enableLayersStr . '" id="enablebutton"'
    . ( $layers_enabled ? ' disabled' : '' ) . '>
        <input type="button" onclick="return set_layer_status(false);" value="'
    . translate ( 'Disable Layers' ) . '" id="disablebutton"'
    .  ( $layers_enabled ? '>' : ' disabled>' ) . '
      </div><br><br>
      <div id="layerlist" style="margin-left: 25px;">' . $LOADING . '</div><br>
      <div class="layerButtons" style="margin-left: 25px;">
        <input type="button" value="' . translate ( 'Add layer' )
    . '..." onclick="return edit_layer(-1)">
      </div><br>

<!--
      <input type="button" value="Refresh" onclick="return load_layers()"><br>
-->';
}

// Create list of users for edit layer dialog.
$userlist = array();
if ( $single_user == 'N' ) {
  $otherlist = $userlist = get_my_users ( '', 'view' );
  if ( $NONUSER_ENABLED == 'Y' ) {
    // Restrict NUC list if groups are enabled.
    $nonusers = get_my_nonusers ( $login, true, 'view' );
    $userlist = ( $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $nonusers, $userlist )
      : array_merge ( $userlist, $nonusers ) );
  }
  if ( $REMOTES_ENABLED == 'Y' ) {
    $remotes = get_nonuser_cals ( $login, true );
    $userlist = ( $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $remotes, $userlist )
      : array_merge ( $userlist, $remotes ) );
  }

  $num_users = $osize = $size = 0;
  $others = $users = '';

  foreach ( $userlist as $i ) {
    if ( $i['cal_login'] != $layer_user ) {
      $users .= $option . $i['cal_login'] . '">' . $i['cal_fullname'] . '</option>';
    }
  }

  foreach ( $otherlist as $i ) {
    if ( $i['cal_login'] != $layer_user ) {
      $osize++;
      $others .= $option . $i['cal_login'] . '">' . $i['cal_fullname'] . '</option>';
    }
  }
  // TODO: handle $otherlist like 1.2 code
}

?>

</form>

<div id="editLayerDiv" style="display: none;">
  <div style="background-color: <?php echo $BGCOLOR;?>; color: <?php echo $TEXTCOLOR;?>; padding: 10px;">
  <form name="editLayerForm" id="editLayerForm">
    <input type="hidden" name="editLayerId" id="editLayerId" value="">
    <input type="hidden" name="editLayerDelete" id="editLayerDelete" value="0">
    <table>
      <tr><td class="tooltip" title="<?php etranslate('user to display on your cal');?>"><label><?php echo translate( 'Source_' );?></label></td>
        <td><select id="editLayerSource" name="editLayerSource">
            <?php echo $users;?>
        </td></tr>
      <tr><td class="tooltip" title="<?php etranslate('text color of new layer');?>"><label><?php echo translate( 'Color_' );?></label></td>
        <td><?php echo print_color_input_html ( 'editLayerColor', '',
            '#000000' );?>
        </td></tr>
      <tr><td class="tooltip" title="<?php etranslate('show duplicate events');?>"><label><?php echo translate( 'Duplicates_' );?></label></td>
        <td><input type="checkbox" name="editLayerDups" id="editLayerDups">
        </td></tr>
    </table>
    <br>
    <center>
      <input id="editLayerDeleteButton" type="button" value="<?php etranslate("Delete");?>"
      onclick="if ( confirm ( '<?php echo $areYouSureStr;?>' ) ) {
        $('editLayerDelete').setAttribute ( 'value', '1' );
        edit_window_closed (); Modalbox.hide ();
        }">
    <input type="button" value="<?php echo $saveStr;?>"
      onclick="edit_window_closed(); Modalbox.hide() "></center>
  </form>
  </div>
</div>


<script>
var layers = Array();
// Set the LAYER_STATUS value in webcal_user_pref for either the current
// user or the public user ('__public__') with an AJAX call to
// layers_ajax.php.
function set_layer_status (enable)
{
  var status = ( enable ? 'enable' : 'disable' );
  new Ajax.Request('layers_ajax.php',
  {
    method: 'post',
    parameters: { action: status<?php
      if ( $updating_public ) { echo ", public: 1"; }
    ?> },
    onSuccess: function( transport ) {
      var response = transport.responseText || "no response text";
      try  {
        response = transport.responseText.evalJSON();
      } catch ( err ) {
        alert ( xlate['JSONerrXXX'].replace(/XXX/, err) + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate("Error");?>:\n\n' + response.message );
      } else {
        //alert("Success! \n\n" + response);
        if ( enable ) {
          $('layerstatus').innerHTML = '<?php echo $LAYERS_ENABLED;?>';
          $('enablebutton').setAttribute ( 'disabled', 'true' );
          $('disablebutton').removeAttribute ( 'disabled' );
          alert('<?php echo strip_tags ( $LAYERS_ENABLED );?>');
        } else {
          $('layerstatus').innerHTML = '<?php echo $LAYERS_DISABLED;?>';
          $('disablebutton').setAttribute ( 'disabled', 'true' );
          $('enablebutton').removeAttribute ( 'disabled' );
          alert('<?php echo strip_tags ( $LAYERS_DISABLED );?>');
        }
      }
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
  return true;
}

function load_layers()
{
  layers = Array();
  $('layerlist').innerHTML = '<?php echo $LOADING;?>';
  new Ajax.Request('layers_ajax.php',
  {
    method:'post',
    parameters: { action: 'list'<?php
      if ( $updating_public ) { echo ", public: 1"; }
    ?> },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php echo $err_Str . translate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        response = transport.responseText.evalJSON();
      } catch ( err ) {
        alert ( xlate['JSONerrXXX'].replace(/XXX/, err) + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php echo $err_Str;?>' + response.message );
        return;
      }
      var x = '<table id="layertable" border="1" summary=""><th><?php echo translate( 'Source' );?></th><th><?php echo translate( 'Color' );?></th><th><?php echo translate( 'Duplicates' );?></th></tr>\n';
      for ( var i = 0; response.layers[i]; i++ ) {
        var cl = ( i % 2 == 0 ) ? 'even' : 'odd';
        var l = response.layers[i];
        layers[l.id] = { id: l.id, source: l.source, color: l.color,
          dups: l.dups, fullname: l.fullname };
        x += '<tr onclick="return edit_layer(' + l.id + ')">' + '<td class="' +
          cl + '">' + l.fullname + '</td><td class="' + cl + '">' + l.color +
          '<span class="colorsample" style="background-color: ' + l.color +
          '">&nbsp;</span></td><td class="' + cl + '">' +
          // translate ( 'no' ) translate ( 'yes' )
          ( l.dups == 'Y' ? xlate['no'] : xlate['yes'] ) + '</td></tr>\n';
      }
      x += '</table>\n';
      $('layerlist').innerHTML = x;
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
  return true;
}

function edit_window_closed () {
  var layeruser = '<?php echo $layer_user;?>';
  var o = $('editLayerSource');
  var source = o.options[o.selectedIndex].value;
  var color = $('editLayerColor').value;
  var dups = $('editLayerDups').checked ? 'Y' : 'N';
  var del = $('editLayerDelete').value;
  var id = $('editLayerId').value;
  var action = ( del > 0 ) ? 'delete' : 'save';
  //alert ( "Sending save...\nid: " + id + "\nlayeruser: " + layeruser +
  //  "\nsource: " + source + "\ncolor: " + color + "\ndups: " + dups +
  //  "\ndelete: " + del );
  new Ajax.Request('layers_ajax.php',
  {
    method:'post',
    parameters: { action: action, id: id, layeruser: layeruser,
      source: source, color: color, dups: dups },
    onSuccess: function( transport ) {
      var response = transport.responseText || "no response text";
      try  {
        response = transport.responseText.evalJSON();
      } catch ( err ) {
        alert ( xlate['JSONerrXXX'].replace(/XXX/, err) + "\n\n" + response );
        return;
      }
      if ( response.error ) {
        alert ( '<?php echo $err_Str;?>\n\n' + response.message );
      } else {
        //alert("Success! \n\n" + response);
        // Reload layers
        load_layers();
      }
    },
    onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
  });
  return true;
}

function edit_layer (id)
{
  var titleStr = '';
  if ( id < 0 )
    titleStr = '<?php etranslate('Add Layer');?>';
  else
    titleStr = '<?php etranslate('Edit Layer');?>';

  // While I like the visual effects that you get with transitions enabled,
  // it causes some of the javascript (setting selectedIndex) to fail for
  // some reason.  So, it is disabled here.
  Modalbox.show($('editLayerDiv'), {title: titleStr, width: 375, transitions: false, closeString: '<?php etranslate('Cancel');?>' });

  if ( id < 0 ) {
    $('editLayerDeleteButton').setAttribute ( 'disabled', 'true' );
  } else {
    $('editLayerDeleteButton').removeAttribute ( 'disabled' );
  }
  $('editLayerDelete').setAttribute ( "value", 0 );
  // Find correct user in select list
  var o = $('editLayerSource');
  var found = false;
  if ( id > 0 ) {
    var n = layers[id]['source'];
    for ( var i in o.options ) {
      if ( i.value == n ) {
        o.selectedIndex = i;
      }
    }
  }
  $('editLayerId').setAttribute ( "value", id );
  $('editLayerColor').setAttribute ( "value", id < 0 ? '#000000' : layers[id]['color'] );
  // Also change the background color of the sample.
  $('editLayerColor_sample').style.background =
    ( id < 0 ? '#000000' : layers[id]['color'] );
  if ( id < 0 )
    $('editLayerDups').removeAttribute ( "checked" );
  else if ( layers[id]['dups'] == 'Y' )
    $('editLayerDups').setAttribute ( "checked", "checked" );
  else
    $('editLayerDups').removeAttribute ( "checked" );
}

</script>

<?php
echo print_trailer();
ob_end_flush();

?>
