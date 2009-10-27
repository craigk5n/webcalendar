<?php
/* $Id$
 *
 * This page handles managing a user's layers and works with
 * layer_ajax.php to make changes.
 */
include_once 'includes/init.php';
send_no_cache_header ();

$layer_user = $login;
$u_url = '';
$updating_public = false;

$public = getValue ( 'public' );

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

$layerStr = translate ( 'Layer' );
$editLayerStr = translate ( 'Edit layer' );
$editStr = translate ( 'Edit' );
$deleteStr = translate ( 'Delete' );
$deleteLayerStr = translate ( 'Delete layer' );
$areYouSureStr = translate ( 'Are you sure you want to delete this layer?' );
$sourceStr = translate ( 'Source' );
$colorStr = translate ( 'Color' );
$duplicatesStr = translate ( 'Duplicates' );
$noStr = translate ( 'No' );
$yesStr = translate ( 'Yes' );
$disabledStr = translate ( 'Disabled' );
$enableLayersStr = translate ( 'Enable layers' );
$LAYERS_ENABLED = translate ( 'Layers are currently enabled.' );
$LAYERS_DISABLED = translate ( 'Layers are currently disabled.' );
$public_link = str_replace ( 'XXX', $PUBLIC_ACCESS_FULLNAME, translate (
  'Click here to modify the layers settings for the XXX calendar.' ) );
$LOADING = '<center><img src="images/loading_animation.gif" alt=""/></center>';


$BodyX = 'onload="load_layers();"';

// Add Modal Dialog javascript/CSS
$HEAD =
  '<link rel="stylesheet" href="includes/js/dhtmlmodal/windowfiles/dhtmlwindow.css" type="text/css" />' . "\n" .
  '<script type="text/javascript" src="includes/js/dhtmlmodal/windowfiles/dhtmlwindow.js"></script>' . "\n" .
  '<link rel="stylesheet" href="includes/js/dhtmlmodal/modalfiles/modal.css" type="text/css" />' . "\n" .
  '<script type="text/javascript" src="includes/js/dhtmlmodal/modalfiles/modal.js"></script>' . "\n";

print_header ( array ( 'js/visible.php' ), $HEAD, $BodyX );

ob_start ();

if ( $ALLOW_VIEW_OTHER != 'Y' )
  echo print_not_auth ();
else {
  if ( $is_admin && empty ( $public ) &&
    ( ! empty ( $PUBLIC_ACCESS ) && $PUBLIC_ACCESS == 'Y' ) ) {
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

<br/><br/>

<div id="layerlist" style="margin-left: 25px;"> <?php echo $LOADING;?> </div>

<br/>

<div class="layerButtons" style="margin-left: 25px;">
<input type="button" value="<?php etranslate('Add layer');?>..."
  onclick="return edit_layer(-1)" />
</div>
<br/>

<!--
<input type="button" value="Refresh"
  onclick="return load_layers()" /> <br />
-->

<?php
}

// Create list of users for edit layer dialog.
$userlist = Array ();
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

<div id="editCatsDiv" style="display: none;">
  <div style="background-color: <?php echo $BGCOLOR;?>; color: <?php echo $TEXTCOLOR;?>; padding: 10px;">
  <form name="editLayerForm" id="editLayerForm">
    <input type="hidden" name="editLayerId" id="editLayerId" value="" />
    <input type="hidden" name="editLayerDelete" id="editLayerDelete" value="0" />
    <table border="0">
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
    <br/>
    <center>
      <input id="editLayerDeleteButton" type="button" value="<?php etranslate("Delete");?>"
      onclick="if ( confirm ( '<?php echo $areYouSureStr;?>' ) ) {
        document.getElementById('editLayerDelete').setAttribute ( 'value', '1' );
        modalEditLayerDialog.hide();
        }" />
    <input type="button" value="<?php etranslate("Save");?>"
      onclick="modalEditLayerDialog.hide()" /></center>
  </form>
  </div>
</div>


<script type="text/javascript">
var layers = Array ();
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
        //var response = transport.responseText.evalJSON ();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        var response = eval('(' + transport.responseText + ')');
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

function load_layers ()
{
  layers = Array ();
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
        //var response = transport.responseText.evalJSON ();
        // Hmmm... The Prototype JSON above doesn't seem to work!
        var response = eval('(' + transport.responseText + ')');
      } catch ( err ) {
        alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + transport.responseText );
        return;
      }
      if ( response.error ) {
        alert ( '<?php etranslate('Error');?>: '  + response.message );
        return;
      }
      var x = '<table class="layertable" border="1" cellspacing="0" cellpadding="0" summary=""><th><?php echo $sourceStr;?></th><th><?php echo $colorStr;?></th><th><?php echo $duplicatesStr;?></th></tr>\n';
      for ( var i = 0; i < response.layers.length; i++ ) {
        var l = response.layers[i];
        layers[l.id] = { id: l.id, source: l.source, color: l.color,
          dups: l.dups, fullname: l.fullname };
        x += '<tr onclick="return edit_layer(' + l.id + ')"'
          + ' style="color:' + l.color + '">'
          + '<td>' + l.fullname + '</td><td>' + l.color
          + '</td><td>' +
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

function edit_layer (id)
{
  modalEditLayerDialog = dhtmlmodal.open ( "modalEditLayerDialog", "div",
    "editCatsDiv",
    ( id < 0 ? '<?php etranslate('Add Layer');?>' :
    '<?php etranslate("Edit Layer");?>' ),
    "width=375px,height=150px,resize=1,scrolling=1,center=1" );
  if ( id < 0 ) {
    $('editLayerDeleteButton').setAttribute ( 'disabled', 'true' );
  } else {
    $('editLayerDeleteButton').removeAttribute ( 'disabled' );
  }
  $('editLayerDelete').setAttribute ( "value", 0 );
  // Find correct user in select list
  var o = $('editLayerSource');
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

  modalEditLayerDialog.onclose = function () {
    // NOTE: Cannot seem to use Prototype format of $('id') here.
    // It causes the code to just stop (without error) on FF3... not sure why.
    // Maybe a conflict with the dhtmlwindow code....
    var layeruser = '<?php echo $layer_user;?>';
    var o = document.getElementById('editLayerSource');
    var source = o.options[o.selectedIndex].value;
    var color = document.getElementById('editLayerColor').value;
    var dups = document.getElementById('editLayerDups').checked ? 'Y' : 'N';
    var del = document.getElementById('editLayerDelete').value;
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
          //var response = transport.responseText.evalJSON ();
          // Hmmm... The Prototype JSON above doesn't seem to work!
          var response = eval('(' + response + ')');
        } catch ( err ) {
          alert ( '<?php etranslate('Error');?>: <?php etranslate('JSON error');?> - ' + err + "\n\n" + response );
          return;
        }
        if ( response.error ) {
          alert ( '<?php etranslate("Error");?>:\n\n' + response.message );
        } else {
          //alert("Success! \n\n" + response);
          // Reload layers
          load_layers ();
        }
      },
      onFailure: function() { alert( '<?php etranslate( 'Error' );?>' ) }
    });
    return true;
  }
}

</script>

<?php

ob_end_flush ();

echo print_trailer ();

?>
