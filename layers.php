<?php
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
  WHERE cal_setting = "LAYERS_STATUS"
    AND cal_login = ?', [$layer_user] );

if ( $res ) {
  $row = dbi_fetch_row ( $res );
  $layers_enabled = ( $row[0] == 'Y' ? 1 : 0 );
  dbi_free_result ( $res );
}

$areYouSureStr   = translate( 'Are you sure you want to delete this layer?' );
$colorStr        = translate( 'Color' );
$deleteLayerStr  = translate( 'Delete layer' );
$deleteStr       = translate( 'Delete' );
$disabledStr     = translate( 'Disabled' );
$duplicatesStr   = translate( 'Duplicates' );
$editLayerStr    = translate( 'Edit layer' );
$editStr         = translate( 'Edit' );
$enableLayersStr = translate( 'Enable layers' );
$layerStr        = translate( 'Layer' );
$LAYERS_DISABLED = translate( 'Layers are currently disabled.' );
$LAYERS_ENABLED  = translate( 'Layers are currently enabled.' );
$noStr           = translate( 'No' );
$sourceStr       = translate( 'Source' );
$yesStr          = translate( 'Yes' );

$LOADING = '<center><img src="images/loading_animation.gif" alt="" /></center>';
$public_link = str_replace( 'XXX', $PUBLIC_ACCESS_FULLNAME,
  translate( 'Click to modify layers settings for XXX' ) );

// Add ModalBox javascript/CSS & Tab code
$headExtras = '
<script type="text/javascript" src="includes/tabcontent/tabcontent.js"></script>
<link type="text/css" href="includes/tabcontent/tabcontent.css" rel="stylesheet" />
<script type="text/javascript" src="includes/js/modalbox/modalbox.js"></script>
<link rel="stylesheet" href="includes/js/modalbox/modalbox.css" type="text/css"
media="screen" />
';

print_header( array( 'js/translate.js.php', 'js/visible.js/true' ),
  $headExtras, 'onload="load_layers();"' );

if ( $ALLOW_VIEW_OTHER != 'Y' )
  echo print_not_auth();
else {
  if( empty( $PUBLIC_ACCESS ) )
    $PUBLIC_ACCESS = 'N';

  if( $is_admin && empty( $public ) && $PUBLIC_ACCESS == 'Y' ) {
    ?>
    <div class="rightsidetip">
      <a href="layers.php?public=1"><?php echo $public_link;?></a>
    </div>
<?php
  }
  echo '
    <h2>' . ( $updating_public
    ? translate ( $PUBLIC_ACCESS_FULLNAME ) . '&nbsp;' : '' )
   . translate ( 'Layers' ) . '&nbsp;<img src="images/help.gif" alt="'
   . translate ( 'Help' ) . '" class="help" onclick="window.open( '
   . '\'help_layers.php\', \'cal_help\', \'dependent,menubar,scrollbars,'
   . 'height=400,width=400,innerHeight=420,outerWidth=420\' );" /></h2>
    ' . display_admin_link();

  ?>
  <form>
  <div class="note">
  <span id="layerstatus">
  <?php echo $layers_enabled ? $LAYERS_ENABLED : $LAYERS_DISABLED;?>
  </span>
  &nbsp;&nbsp;
  &nbsp;&nbsp;
  <input type="button" onclick="return set_layer_status(true);" value=<?php echo $enableLayersStr;?>" id="enablebutton" <?php echo $layers_enabled ? 'disabled="true"' : '';?> />
  <input type="button" onclick="return set_layer_status(false);" value=<?php etranslate("Disable Layers");?>" <?php echo $layers_enabled ? '' : 'disabled="true"';?> id="disablebutton" />
  </div>

<br /><br />

<div id="layerlist" style="margin-left: 25px;"> <?php echo $LOADING;?> </div>

<br />

<div class="layerButtons" style="margin-left: 25px;">
<input type="button" value="<?php etranslate('Add layer');?>..."
  onclick="return edit_layer(-1)" />
</div>
<br />

<!--
<input type="button" value="Refresh"
  onclick="return load_layers()" /> <br />
-->

<?php
}

// Create list of users for edit layer dialog.
$userlist = [];
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

  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    if ( $userlist[$i]['cal_login'] != $layer_user ) {
      $users .= '<option value="' . $userlist[$i]['cal_login'] . '"' .
       '>' . $userlist[$i]['cal_fullname'] . '</option>';
    }
  }

  for ( $i = 0, $cnt = count ( $otherlist ); $i < $cnt; $i++ ) {
    if ( $otherlist[$i]['cal_login'] != $layer_user ) {
      $osize++;
      $others .= '<option value="' . $otherlist[$i]['cal_login'] . '">'
       . $otherlist[$i]['cal_fullname'] . '</option>';
    }
  }
  // TODO: handle $otherlist like 1.2 code
}

?>

</form>

<div id="editLayerDiv" style="display: none;">
  <div style="background-color: <?php echo $BGCOLOR;?>; color: <?php echo $TEXTCOLOR;?>; padding: 10px;">
  <form name="editLayerForm" id="editLayerForm">
    <input type="hidden" name="editLayerId" id="editLayerId" value="" />
    <input type="hidden" name="editLayerDelete" id="editLayerDelete" value="0" />
    <table>
      <tr><td class="tooltip" title="<?php etranslate('Specifies the user that you would like to see displayed in your calendar.');?>"><label><?php echo $sourceStr;?>:</label></td>
        <td><select id="editLayerSource" name="editLayerSource">
            <?php echo $users;?>
        </td></tr>
      <tr><td class="tooltip" title="<?php etranslate('The text color of the new layer that will be displayed in your calendar.');?>"><label><?php echo $colorStr;?>:</label></td>
        <td><?php echo print_color_input_html ( 'editLayerColor', '',
            '#000000' );?>
        </td></tr>
      <tr><td class="tooltip" title="<?php etranslate('If checked, events that are duplicates of your events will be shown.');?>"><label><?php echo $duplicatesStr;?>:</label></td>
        <td><input type="checkbox" name="editLayerDups" id="editLayerDups" />
        </td></tr>
    </table>
    <br />
    <center>
      <input id="editLayerDeleteButton" type="button" value="<?php etranslate("Delete");?>"
      onclick="if ( confirm ( '<?php echo $areYouSureStr;?>' ) ) {
        $('editLayerDelete').setAttribute ( 'value', '1' );
        edit_window_closed (); Modalbox.hide ();
        }" />
    <input type="button" value="<?php etranslate("Save");?>"
      onclick="edit_window_closed(); Modalbox.hide() " /></center>
  </form>
  </div>
</div>


<script type="text/javascript">
var layers = [];
// Set the LAYER_STATUS value in webcal_user_pref for either the current
// user or the public user ('__public__') with an AJAX call to
// layers_ajax.php.
function set_layer_status (enable)
{
  var status = ( enable ? 'enable' : 'disable' );
  new Ajax.Request('layers_ajax.php',
  {
    method:'post',
    parameters: { action: status<?php
      if ( $updating_public ) { echo ", public: 1"; }
    ?> },
    onSuccess: function( transport ) {
      var response = transport.responseText || "no response text";
      try  {
        response = transport.responseText.evalJSON();
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
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
  layers = [];
  $('layerlist').innerHTML = '<?php echo $LOADING;?>';
  new Ajax.Request('layers_ajax.php',
  {
    method:'post',
    parameters: { action: 'list'<?php
      if ( $updating_public ) { echo ", public: 1"; }
    ?> },
    onSuccess: function( transport ) {
      if ( ! transport.responseText ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('no response from server');?>' );
        return;
      }
      //alert ( "Response:\n" + transport.responseText );
      try  {
        response = transport.responseText.evalJSON();
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate('Error');?>: '  + response.message );
        return;
      }
      var x = '<table id="layertable" border="1"><th><?php echo $sourceStr;?></th><th><?php echo $colorStr;?></th><th><?php echo $duplicatesStr;?></th></tr>\n';
      for ( var i = 0; i < response.layers.length; i++ ) {
        var cl = ( i % 2 == 0 ) ? 'even' : 'odd';
        var l = response.layers[i];
        layers[l.id] = { id: l.id, source: l.source, color: l.color,
          dups: l.dups, fullname: l.fullname };
        x += '<tr onclick="return edit_layer(' + l.id + ')">' +
          '<td class="' + cl + '">' + l.fullname + '</td><td class="' +
          cl + '">' + l.color +
          '<span class="colorsample" style="background-color: ' + l.color +
          '">&nbsp;</span></td><td class="' + cl + '">' +
          ( l.dups == 'Y' ? '<?php echo $yesStr;?>' : '<?php echo $noStr;?>' ) +
          '</td></tr>\n';
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
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + response );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate("Error");?>:\n\n' + response.message );
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
    for ( var i = 0; i < o.options.length; i++ ) {
      if ( o.options[i].value == n ) {
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

?>
