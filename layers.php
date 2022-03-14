<?php
/**
 * This page handles managing a user's layers
 * and works with layer_ajax.php to make changes.
 */
include_once 'includes/init.php';
send_no_cache_header();

$layer_user = $login;
$public = getValue('public');
$u_url = '';
$updating_public = false;

if ($is_admin && !empty($public) && $PUBLIC_ACCESS == 'Y') {
  $layer_user = '__public__';
  $u_url = '&amp;public=1';
  $updating_public = true;
}

load_user_layers($layer_user, 1);

$layers_enabled = 0;
$res = dbi_execute('SELECT cal_value FROM webcal_user_pref
  WHERE cal_setting = "LAYERS_STATUS"
    AND cal_login = ?', [$layer_user]);

if ($res) {
  $row = dbi_fetch_row($res);
  $layers_enabled = ($row && !empty($row) && $row[0] == 'Y' ? 1 : 0);
  dbi_free_result($res);
}

$areYouSureStr   = translate('Are you sure you want to delete this layer?');
$colorStr        = translate('Color');
$deleteLayerStr  = translate('Delete layer');
$deleteStr       = translate('Delete');
$disabledStr     = translate('Disabled');
$duplicatesStr   = translate('Duplicates');
$editLayerStr    = translate('Edit layer');
$editStr         = translate('Edit');
$enableLayersStr = translate('Enable layers');
$layerStr        = translate('Layer');
$LAYERS_DISABLED = translate('Layers are currently disabled.');
$LAYERS_ENABLED  = translate('Layers are currently enabled.');
$noStr           = translate('No');
$sourceStr       = translate('Source');
$yesStr          = translate('Yes');

$LOADING = '<center><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></center>';
$public_link = str_replace(
  'XXX',
  $PUBLIC_ACCESS_FULLNAME,
  translate('Click to modify layers settings for XXX')
);

$headExtras = '';

print_header(array('js/translate.js.php'), $headExtras, 'onload="load_layers();"');

if ($ALLOW_VIEW_OTHER != 'Y')
  echo print_not_auth();
else {
  if (empty($PUBLIC_ACCESS))
    $PUBLIC_ACCESS = 'N';

  if ($is_admin && empty($public) && $PUBLIC_ACCESS == 'Y') {
?>
    <div class="rightsidetip">
      <a href="layers.php?public=1"><?php echo $public_link; ?></a>
    </div>
  <?php
  }
  echo '
    <h2>' . ($updating_public
    ? translate($PUBLIC_ACCESS_FULLNAME) . '&nbsp;' : '')
    . translate('Layers') . '&nbsp;<img src="images/bootstrap-icons/question-circle-fill.svg" alt="'
    . translate('Help') . '" class="help" onclick="window.open( '
    . '\'help_layers.php\', \'cal_help\', \'dependent,menubar,scrollbars,'
    . 'height=400,width=400,innerHeight=420,outerWidth=420\' );" /></h2>
    ' . display_admin_link();

  ?>
  <form>
    <div class="note">
      <span id="layerstatus">
        <?php echo $layers_enabled ? $LAYERS_ENABLED : $LAYERS_DISABLED; ?>
      </span>
      &nbsp;&nbsp;
      &nbsp;&nbsp;
      <input class="btn btn-primary" type="button" onclick="return set_layer_status(true);" value=<?php echo $enableLayersStr; ?>" id="enablebutton" <?php echo $layers_enabled ? 'disabled="true"' : ''; ?> />
      <input class="btn btn-secondary" type="button" onclick="return set_layer_status(false);" value=<?php etranslate("Disable Layers"); ?>" <?php echo $layers_enabled ? '' : 'disabled="true"'; ?> id="disablebutton" />
    </div>

    <br /><br />

    <div id="layerlist" style="margin-left: 25px;"> <?php echo $LOADING; ?> </div>

    <br />

    <div class="layerButtons" style="margin-left: 25px;">
      <input class="btn btn-primary" type="button" value="<?php etranslate('Add layer'); ?>..." onclick="return edit_layer(-1)" />
    </div>
    <br />

  <?php
}

// Create list of users for edit layer dialog.
$userlist = [];
if ($single_user == 'N') {
  $otherlist = $userlist = get_my_users('', 'view');
  if ($NONUSER_ENABLED == 'Y') {
    // Restrict NUC list if groups are enabled.
    $nonusers = get_my_nonusers($login, true, 'view');
    $userlist = ($NONUSER_AT_TOP == 'Y'
      ? array_merge($nonusers, $userlist)
      : array_merge($userlist, $nonusers));
  }
  if ($REMOTES_ENABLED == 'Y') {
    $remotes = get_nonuser_cals($login, true);
    $userlist = ($NONUSER_AT_TOP == 'Y'
      ? array_merge($remotes, $userlist)
      : array_merge($userlist, $remotes));
  }

  $num_users = $osize = $size = 0;
  $others = $users = '';

  for ($i = 0, $cnt = count($userlist); $i < $cnt; $i++) {
    if ($userlist[$i]['cal_login'] != $layer_user) {
      $users .= '<option value="' . $userlist[$i]['cal_login'] . '"' .
        '>' . $userlist[$i]['cal_fullname'] . '</option>';
    }
  }

  for ($i = 0, $cnt = count($otherlist); $i < $cnt; $i++) {
    if ($otherlist[$i]['cal_login'] != $layer_user) {
      $osize++;
      $others .= '<option value="' . $otherlist[$i]['cal_login'] . '">'
        . $otherlist[$i]['cal_fullname'] . '</option>';
    }
  }
  // TODO: handle $otherlist like 1.2 code
}

?>

  </form>

  <div id="edit-layer-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="edit-layer-title" class="modal-title">Modal title</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#edit-layer-dialog').hide();">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form name="editLayerForm" id="editLayerForm">
            <input type="hidden" name="editLayerId" id="editLayerId" value="" />
            <input type="hidden" name="editLayerDelete" id="editLayerDelete" value="0" />
            <table class="table table-borderless table-responsive">
              <tr>
                <td data-toggle="tooltip" data-placement="top" title="<?php etranslate('Specifies the user that you would like to see displayed in your calendar.'); ?>"><label><?php echo $sourceStr; ?>:</label></td>
                <td><select class="form-control" id="editLayerSource" name="editLayerSource">
                    <?php echo $users; ?>
                </td>
              </tr>
              <tr>
                <td data-toggle="tooltip" data-placement="top" title="<?php etranslate('The text color of the new layer that will be displayed in your calendar.'); ?>"><label><?php echo $colorStr; ?>:</label></td>
                <td><?php echo print_color_input_html('editLayerColor', '', '#000000'); ?>
                </td>
              </tr>
              <tr>
                <td data-toggle="tooltip" data-placement="top" title="<?php etranslate('If checked, events that are duplicates of your events will be shown.'); ?>"><label><?php echo $duplicatesStr; ?>:</label></td>
                <td><input xclass="form-control" type="checkbox" name="editLayerDups" id="editLayerDups" />
                </td>
              </tr>
            </table>
            <div class="modal-footer">
              <input class="btn btn-secondary" onclick="$('#edit-layer-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>">
              <input class="btn btn-danger" id="editLayerDeleteButton" type="button" value="<?php etranslate("Delete"); ?>" onclick="if ( confirm ( '<?php echo $areYouSureStr; ?>' ) ) {
            $('#editLayerDelete').prop ('value', '1');
            edit_window_closed ();
            $('#edit-layer-dialog').hide();
            }" />
              <input class="btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Save"); ?>" onclick="edit_window_closed(); $('#edit-layer-dialog').hide();" />
            </div>
          </form>
        </div>
      </div>
    </div>

<script type="text/javascript">
  $(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
  });

  var layers = [];
  // Set the LAYER_STATUS value in webcal_user_pref for either the current
  // user or the public user ('__public__') with an AJAX call to
  // layers_ajax.php.
  function set_layer_status(enable) {
    var layerstatus = (enable ? 'enable' : 'disable');

    $.post('layers_ajax.php', {
        action: layerstatus,
        csrf_form_key: '<?php echo getFormKey(); ?>'
        <?php
        if ($updating_public) {
          echo ', public: "1"';
        }
        ?>
      },
      function(data, status) {
        var stringified = JSON.stringify(data);
        console.log("set_layer_status Data: " + stringified + "\nStatus: " + status);
        try {
          var response = jQuery.parseJSON(stringified);
          console.log('set_layer_status response=' + response);
        } catch (err) {
          alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
          return;
        }
        if (response.error) {
          console.log('Ajax error: ' + response);
          alert('<?php etranslate("Error"); ?>:\n\n' + response.message);
        } else {
          //alert("Success! \n\n" + response);
          if (enable) {
            $('#layerstatus').html('<?php echo $LAYERS_ENABLED; ?>');
            $('#enablebutton').prop('disabled', true);
            $('#disablebutton').prop('disabled', false);
            alert('<?php echo strip_tags($LAYERS_ENABLED); ?>');
          } else {
            $('#layerstatus').html('<?php echo $LAYERS_DISABLED; ?>');
            $('#disablebutton').prop('disabled', true);
            $('#enablebutton').prop('disabled', false);
            alert('<?php echo strip_tags($LAYERS_DISABLED); ?>');
          }
        }
      });
  }

  function load_layers() {
    layers = [];
    $('#layerlist').html('<?php echo $LOADING; ?>');
    $.post('layers_ajax.php', {
        action: 'list',
        csrf_form_key: '<?php echo getFormKey(); ?>'
        <?php
        if ($updating_public) {
          echo ', public: "1"';
        }
        ?>
      },
      function(data, status) {
        console.log("Data: " + data + "\nStatus: " + status);
        try {
          var response = jQuery.parseJSON(data);
        } catch (err) {
          alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
          return;
        }
        if (response.error) {
          alert('<?php etranslate('Error'); ?>: ' + response.message);
          return;
        }
        console.log('response.layers.length=' + response.layers.length);
        var x = '<table class="table table-striped" id="layertable" border="1"><tr><th><?php echo $sourceStr; ?></th><th><?php echo $colorStr; ?></th><th><?php echo $duplicatesStr; ?></th></tr>\n';
        for (var i = 0; i < response.layers.length; i++) {
          var l = response.layers[i];
          layers[l.id] = {
            id: l.id,
            source: l.source,
            color: l.color,
            dups: l.dups,
            fullname: l.fullname
          };
          x += '<tr onclick="return edit_layer(' + l.id + ')">' +
            '<td>' + l.fullname + '</td><td>' + l.color +
            '<span class="colorsample" style="background-color: ' + l.color +
            '">&nbsp;</span></td><td>' +
            (l.dups == 'Y' ? '<?php echo $yesStr; ?>' : '<?php echo $noStr; ?>') +
            '</td></tr>\n';
        }
        x += '</table>\n';
        $('#layerlist').html(x);
        //console.log('x=' + x);
      });
  }

  function edit_window_closed() {
    var layeruser = '<?php echo $layer_user; ?>';
    var source = $('#editLayerSource').val();
    var color = $('#editLayerColor').val();
    var dups = $('#editLayerDups').is(':checked') ? 'Y' : 'N';
    var del = $('#editLayerDelete').val();
    var id = $('#editLayerId').val();
    var action = (del > 0) ? 'delete' : 'save';
    console.log("Sending save...\nid: " + id + "\nlayeruser: " + layeruser +
      "\nsource: " + source + "\ncolor: " + color + "\ndups: " + dups +
      "\ndelete: " + del);

    $.post('layers_ajax.php', {
        action: action,
        id: id,
        layeruser: layeruser,
        source: source,
        color: color,
        dups: dups,
        csrf_form_key: '<?php echo getFormKey(); ?>'
      },
      function(data, status) {
        var stringified = JSON.stringify(data);
        console.log("set_layer_status Data: " + stringified + "\nStatus: " + status);
        try {
          var response = jQuery.parseJSON(stringified);
          console.log('set_layer_status response=' + response);
        } catch (err) {
          alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
          return;
        }
        if (response.error) {
          alert('<?php etranslate('Error'); ?>: ' + response.message);
          return;
        }
        // Reload layers
        load_layers();
      });
  }

  function edit_layer(id) {
    console.log('edit_layer(' + id + ')');
    var titleStr = '';
    if (id < 0)
      titleStr = '<?php etranslate('Add Layer'); ?>';
    else
      titleStr = '<?php etranslate('Edit Layer'); ?>';
    $('#edit-layer-title').html(titleStr);

    if (id < 0) {
      $('#editLayerDeleteButton').prop('disabled', true);
    } else {
      $('#editLayerDeleteButton').prop('disabled', false);
    }
    $('#editLayerDelete').prop("value", 0);
    // Find correct user in select list
    var o = $('#editLayerSource');
    var optionValues = $("#editLayerSource>option").map(function() { return $(this).val(); });;
    var found = false;
    if (id > 0) {
      console.log('id=' + id + "\noptionValues.length=" + optionValues.length);
      var n = layers[id]['source'];
      for (var i = 0; i < optionValues.length; i++) {
        console.log("Compare " + optionValues[i] + " vs " + n);
        if (optionValues[i] == n) {
          o.val(n);
          console.log('Selecting index ' + i + ", value=" + n);
          found = true;
        }
      }
    }
    if(!found) {
      console.log("Layer ID not found");
      // alert?
    }
    $('#editLayerId').prop("value", id);
    $('#editLayerColor').prop("value", id < 0 ? '#000000' : layers[id]['color']);
    // Also change the background color of the sample.
    //$('#editLayerColor_sample').style.background =
    //  ( id < 0 ? '#000000' : layers[id]['color'] );
    if (id < 0)
      $('#editLayerDups').prop("checked", false);
    else if (layers[id]['dups'] == 'Y')
      $('#editLayerDups').prop("checked", true);
    else
      $('#editLayerDups').prop("checked", false);

    $('#edit-layer-dialog').show();
  }
</script>

<?php
  echo print_trailer();
?>
