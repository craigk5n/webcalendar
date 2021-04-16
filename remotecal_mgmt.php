<?php

/**
 * Remote Calendar Management. 
 * NOTE: Very similar to user_mgmt.php with a lot of code copied from there.
 */

include_once 'includes/init.php';

// Verify access to this page is allowed.
if (empty($login) || $login == '__public__') {
    // Do not allow public access.
    do_redirect(empty($STARTVIEW) ? 'month.php' : $STARTVIEW);
    exit;
}
if ($REMOTES_ENABLED != 'Y' || (access_is_enabled() && !access_can_access_function(ACCESS_IMPORT))) {
    do_redirect(empty($STARTVIEW) ? 'month.php' : $STARTVIEW);
}

$LOADING = '<center><img src="images/loading_animation.gif" alt="" /></center>';
$areYouSure = translate('Are you sure you want to delete this remote calendar?');
$deleteUserInfo = translate('This will remove all events for this remote calendar.') .
    ' ' . translate('This action cannot be undone.');
$yesStr = translate('Yes');
$noStr = translate('No');
$noLoginError = translate('Username cannot be blank.');
$noUrlError = translate('You have not entered a URL.');
$noTooltip = translate('This remote calendar does not have a layer.  Add a layer for this calendar to view it in your calendar.');
$sourceStr = translate('Source');
$colorStr = translate('Color');
$duplicatesStr = translate('Duplicates');


print_header(
    '',
    '',
    'onload="load_users();"'
);

?>


<h3><?php etranslate('Remote Calendars'); ?></h3>
<!-- Error Alert -->
<div id="main-dialog-alert" class="alert alert-info" style="display: none">
    <span id="infoMessage"></span>
    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
</div>
<!-- Users loaded via AJAX -->
<table class="table table-striped" id="user-table">
    <thead>
        <tr>
            <th scope="col">
                <div data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('Unique Calendar ID for remote calendar') ?>"><?php etranslate('Calendar ID') ?></div>
            </th>
            <th scope="col"><?php etranslate('First Name') ?></th>
            <th scope="col"><?php etranslate('Last Name') ?></th>
            <th scope="col">
                <div data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('Calendar user who created this remote calendar') ?>"><?php etranslate('Admin') ?></div>
            </th>
            <?php if (!empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') { ?>
                <th scope="col">
                    <div data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('Enabling allows this remote calendar to be used as a public calendar, and a link directly to it will be displayed on the login page.') ?>"><?php etranslate('Public Access') ?></div>
                </th>
            <?php } ?>
            <th scope="col">
                <div data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('URL for the ICS file used to import events for this remote calendar') ?>"><?php etranslate('Calendar URL') ?></div>
            </th>
            <th scope="col">
                <!-- dropdown menu -->
            </th>
        </tr>
    <tbody id="user-tbody">
    </tbody>
    </thead>
</table>

<br />
<div class="userButtons">
    <input class="btn btn-primary" type="button" value="<?php etranslate('Add Remote Calendar'); ?>..." onclick="return edit_user('')" />
</div>

<!-- add/edit user modal dialog -->
<div id="edit-user-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="edit-user-title" class="modal-title">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#edit-user-dialog').hide();">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Error Alert -->
                <div id="edit-user-dialog-alert" class="alert alert-danger" style="display: none">
                    <strong><?php etranslate("Error"); ?>!</strong>&nbsp;<span id="errorMessage">A problem has been occurred while submitting your data.</span>
                    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
                </div>
                <form name="editUserForm" id="editUserForm">
                    <input type="hidden" name="editUserAdd" id="editUserAdd" value="0" />
                    <div class="form-inline" id="divEditUsername">
                        <label class="col-5" for="editUsername" data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('Unique Calendar ID for remote calendar') ?>"><?php etranslate('Calendar ID') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editUsername" name="editUsername" placeholder="<?php echo translate('New ID') . ' (' . translate('required') . ')'; ?>" />
                    </div>
                    <div class="form-inline mt-1" id="div-editFirstname">
                        <label class="col-5 for=" editFirstname"><?php etranslate('First Name') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editFirstname" name="editFirstname" />
                    </div>
                    <div class="form-inline mt-1" id="div-editLastname">
                        <label class="col-5 for=" editLastname"><?php etranslate('Last Name') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editLastname" name="editLastname" />
                    </div>
                    <?php if (!empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') { ?>
                        <div class="form-inline mt-1" id="div-editPublic">
                            <label class="col-5 for=" editPublic" data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('Enabling allows this remote calendar to be used as a public calendar, and a link directly to it will be displayed on the login page.') ?>"><?php etranslate('Public Access') ?>: </label>
                            <?php echo print_radio('editPublic'); ?>
                        </div>
                    <?php } ?>
                    <div class="form-inline mt-1" id="div-editURL">
                        <label class="col-5 for=" editURL" data-toggle="tooltip" data-placement="bottom" title="<?php etranslate('URL for the ICS file used to import events for this remote calendar') ?>"><?php etranslate('URL') ?>: </label>
                        <input type="email" class="col-7 form-control" id="editURL" name="editURL" />
                    </div>

                    <div class="modal-footer">
                        <input class="form-control btn btn-secondary" onclick="$('#edit-user-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                        <input class="form-control btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Save"); ?>" onclick="save_handler();" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- delete user modal dialog -->
<div id="delete-user-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="delete-user-title" class="modal-title"><?php etranslate('Delete Remote Calendar'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#delete-user-dialog').hide();">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="p-3"><?php echo $areYouSure; ?></div>
                <div class="p-3 m-3 text-danger"><?php echo $deleteUserInfo; ?></div>
                <form name="deleteUserForm" id="deleteUserForm">
                    <div class="form-inline" id="divdeleteUsername">
                        <label class="col-5" for="deleteUsername"><?php etranslate('Username') ?>: </label>
                        <input disabled="true" type="text" class="col-7 form-control" id="deleteUsername" name="deleteUsername" />
                    </div>
                </form>
                <br>
                <div class="modal-footer">
                    <input class="form-control btn btn-secondary" onclick="$('#delete-user-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                    <input class="form-control btn btn-danger" type="submit" name="delete" value="<?php etranslate('Delete') ?>" onclick="delete_handler ();" />
                </div>
            </div>
        </div>
    </div>
</div>

<div id="edit-layer-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="edit-layer-title" class="modal-title"><?php etranslate('Add Layer'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#edit-layer-dialog').hide();">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form name="editLayerForm" id="editLayerForm">
                    <input type="hidden" name="addLayerRemoteCalendarLogin" id="addLayerRemoteCalendarLogin" value="" />
                    <table>
                        <tr>
                            <td style="padding-right: 2em" data-toggle="tooltip" data-placement="top" title="<?php etranslate('The text color of the new layer that will be displayed in your calendar.'); ?>"><label><?php echo $colorStr; ?>:</label></td>
                            <td><?php echo print_color_input_html('editLayerColor', '', '#000000'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right: 2em" data-toggle="tooltip" data-placement="top" title="<?php etranslate('If checked, events that are duplicates of your events will be shown.'); ?>"><label><?php echo $duplicatesStr; ?>:</label></td>
                            <td><input class="form-control" type="checkbox" name="editLayerDups" id="editLayerDups" />
                            </td>
                        </tr>
                    </table>
                    <div class="modal-footer">
                        <input class="form-control btn btn-secondary" onclick="$('#edit-layer-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>">
                        <input class="form-control btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Save"); ?>" onclick="edit_layer_window_closed(); $('#edit-layer-dialog').hide();" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    var myLogin = '<?php echo $login; ?>';

    function user_menu(login, showAddLayer) {
        // Dropdown menu
        ret = '<div class="btn-group dropleft float-right">\n' +
            '<button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
            '<span class="sr-only">Toggle Dropdown</span> </button> <div class="dropdown-menu">\n';
        // Edit
        ret += "<a class='clickable dropdown-item' onclick=\"return edit_user('" + login +
            "');\"><?php etranslate('Edit'); ?>...</a>";
        if (showAddLayer) {
            ret += "<a class='clickable dropdown-item' onclick=\"return add_layer('" + login +
                "');\"><?php etranslate('Add Layer'); ?>...</a>";
        }
        // Delete User
        <?php if ($is_admin && $admin_can_delete_user && access_can_access_function(ACCESS_USER_MANAGEMENT)) { ?>
            // Cannot delete yourself
            if (myLogin != login) {
                ret += '<div class="dropdown-divider"></div>';
                ret += "<a class='clickable dropdown-item' onclick=\"return delete_user('" + login +
                    "');\"><?php etranslate('Delete'); ?></a>";
            }
        <?php } ?>
        ret += "</div></div>\n";
        return ret;
    }

    function load_users() {
        console.log("In load_users");
        users = [];
        $('#user-tbody').html('<tr><td colspan="5"><?php echo $LOADING; ?></td></tr>');
        $.post('users_ajax.php', {
                action: 'remote-cal-list'
            },
            function(data, status) {
                var stringified = JSON.stringify(data);
                console.log("Data: " + stringified + "\nStatus: " + status);
                try {
                    var response = jQuery.parseJSON(stringified);
                } catch (err) {
                    alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
                    return;
                }
                if (response.error) {
                    alert('<?php etranslate('Error'); ?>: ' + response.message);
                    return;
                }
                console.log('response.users.length=' + response.users.length);
                var tbody = '';
                for (var i = 0; i < response.users.length; i++) {
                    var u = response.users[i];
                    users[i] = {
                        login: u.login,
                        lastname: u.lastname,
                        firstname: u.firstname,
                        admin: u.admin,
                        public: u.public,
                        url: u.url,
                        fullname: u.fullname,
                        layercount: u.layercount
                    };
                    var tooltip = u.layercount == 0 ? 'data-toggle="tooltip" data-placement="bottom" title="<?php echo $noTooltip; ?>"' : '';
                    var warning = u.layercount == 0 ? '<img class="button-icon-inverse" src="images/open-iconic/svg/warning.svg" />' : '';
                    var id = u.login.substring(0, 5) == '<?php echo $NONUSER_PREFIX; ?>' ? u.login.substring(5) : u.login;
                    tbody += '<tr><td ' + tooltip + '>' + warning + id + '</td><td>' + (u.firstname == null ? '' : u.firstname) +
                        '</td><td>' + (u.lastname == null ? '' : u.lastname) + '</td><td>' + (u.admin == null ? '' : u.admin) +
                        <?php if (!empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') { ?> '</td><td>' + (u.public == 'Y' ? '<?php echo $yesStr; ?>' : '<?php echo $noStr; ?>') +
                        <?php } ?> '</td><td>' +
                        (u.url == null ? '' : u.url) +
                        '</td><td>' + user_menu(u.login, u.layercount == 0) + '</td></tr>\n';
                }
                $('#user-tbody').html(tbody);
                //console.log('tbody=' + tbody);
                // Update tooltips
                $('[data-toggle="tooltip"]').tooltip();
            },
            'json');
    }

    // Minimal URL validation.
    function validateURL() {
        var elem = $("#editURL").val();
        // Replace "webcal://" with "https://"
        if (elem.match(/^webcal:\/\//)) {
            var upd = 'https://' + elem.substring(9);
            $("#editURL").val(upd);
        }
        elem = $("#editURL").val();
        var regex = /^(http|https|ftp):\/\/[^\s]+$/;
        if (elem.match(regex))
            return true;
        else
            return false;
    }

    function edit_user(login) {
        console.log('edit_user(' + login + ')');
        $('#edit-user-dialog-alert').hide();
        // Find correct user in our user list
        var user = null;
        for (var i = 0; i < users.length; i++) {
            if (login == users[i]['login']) {
                found = true;
                user = users[i];
            }
        }
        if (login == "") {
            // Add user
            $('#edit-user-title').html('<?php etranslate('Add Remote Calendar'); ?>');
            $('#editUserAdd').prop("value", "1");
            $('#editUsername').prop("disabled", false);
            $('#editUsername').prop("value", "");
            $('#editFirstname').prop("value", "");
            $('#editLastname').prop("value", "");
            <?php if (!empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') { ?>
                $('#editPublic_N').prop("checked", true);
            <?php } ?>
            $('#editURL').prop("value", "");
        } else {
            // Edit user
            $('#edit-user-title').html('<?php etranslate('Edit'); ?>');
            $('#editUserAdd').prop("value", "0");
            $('#editUsername').prop("value", user['login'].substring('<?php echo $NONUSER_PREFIX; ?>'.length));
            $('#editUsername').prop("disabled", true);
            $('#editFirstname').prop("value", user['firstname']);
            $('#editLastname').prop("value", user['lastname']);
            <?php if (!empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') { ?>
                if (user['public'] == 'Y')
                    $('#editPublic_Y').prop("checked", true);
                else
                    $('#editPublic_N').prop("checked", true);
            <?php } ?>
            // We don't edit the admin (manager) of the remote calendar.
            $('#editURL').prop("value", user['url']);
        }
        $('#edit-user-dialog').show();
    }

    function save_handler() {
        var login = '<?php echo $NONUSER_PREFIX; ?>' + $('#editUsername').val();
        var firstname = $('#editFirstname').val();
        var lastname = $('#editLastname').val();
        <?php if (!empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') { ?>
            console.log('editPublic_Y: ' + $('#editPublic_Y').is(':checked'));
            var public = $('#editPublic_Y').is(':checked') ? 'Y' : 'N';
        <?php } else { ?>
            var public = 'N';
        <?php } ?>

        var add = $('#editUserAdd').val();
        if (add == "1") {
            if (login.length == 0) {
                $('#errorMessage').html('<?php echo  $noLoginError; ?>');
                $('#edit-user-dialog-alert').show();
                return;
            }
        }
        // Validate URL
        if (!validateURL()) {
            $('#errorMessage').html('<?php echo $noUrlError; ?>');
            $('#edit-user-dialog-alert').show();
            return;
        }
        var url = $('#editURL').val();
        if (url.length == 0) {
            $('#errorMessage').html('<?php echo  $noUrlError; ?>');
            $('#edit-user-dialog-alert').show();
            return;
        }
        console.log("Sending save...\nadd " + add + ", login: " + login + "\nfirstname: " + firstname +
            "\nlastname: " + lastname + "\npublic: " + public + "\nurl: " + url);
        var error = '';

        $.post('users_ajax.php', {
                    add: add, // "1" or "0"
                    action: "save-remote-cal",
                    login: login,
                    firstname: firstname,
                    lastname: lastname,
                    public: public,
                    url: url
                },
                function(data, status) {
                    console.log('Data: ' + data);
                    var stringified = JSON.stringify(data);
                    console.log("save_handler Data: " + stringified + "\nStatus: " + status);
                    try {
                        var response = jQuery.parseJSON(stringified);
                        console.log('save_handler response=' + response);
                    } catch (err) {
                        //alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
                        error = err;
                        return;
                    }
                    if (response.error) {
                        error = response.message;
                        //alert('<?php etranslate('Error'); ?>: ' + response.message);
                        return;
                    }
                    if (error == '') {
                        // Close window
                        $('#edit-user-dialog').hide();
                        // Reload layers
                        load_users();
                    }
                },
                'json')
            .done(function() {
                if (error.length == 0) {
                    if (add == 1) {
                        $('#infoMessage').html('<?php echo translate('Remote Calendar successfully added. You must add a new layer to your calendar to view the events from this remote calendar on your calendar.') ?>');
                    } else {
                        $('#infoMessage').html('<?php etranslate('Remote Calendar successfully updated.') ?>');
                    }
                    $('#main-dialog-alert').show();
                } else {
                    $('#errorMessage').html(error);
                    $('#edit-user-dialog-alert').show();
                }
            })
            .fail(function(jqxhr, settings, ex) {
                $('#errorMessage').html('<?php etranslate('Error'); ?>:' + ex);
                $('#edit-user-dialog-alert').show();
            });
    }



    function delete_user(user) {
        console.log('delete_user(' + user + ')');
        $('#deleteUsername').val(user.substring('<?php echo $NONUSER_PREFIX; ?>'.length));
        $('#delete-user-dialog').show();
    }

    function delete_handler() {
        var login = '<?php echo $NONUSER_PREFIX; ?>' + $('#deleteUsername').val();
        console.log("Sending delete for username: " + login);
        var error = '';

        $.post('users_ajax.php', {
                    action: "delete-remote-cal",
                    login: login
                },
                function(data, status) {
                    console.log('Data: ' + data);
                    var stringified = JSON.stringify(data);
                    console.log("delete_handler Data: " + stringified + "\nStatus: " + status);
                    try {
                        var response = jQuery.parseJSON(stringified);
                        console.log('delete_handler response=' + response);
                    } catch (err) {
                        console.log('Error: ' + err);
                        error = '<?php etranslate('JSON error'); ?>' + ' - ' + err;
                        return;
                    }
                    if (response.error) {
                        console.log('Error: ' + response.message);
                        error = '<?php etranslate('Error'); ?>' + ' - ' + response.message;
                        return;
                    }
                })
            .done(function() {
                if (error.length == 0) {
                    // Close window
                    $('#delete-user-dialog').hide();
                    // Reload layers
                    load_users();
                    $('#infoMessage').html('<?php etranslate('User successfully deleted.') ?>');
                    $('#main-dialog-alert').show();
                } else {
                    alert('<?php etranslate('Error'); ?>: ' + error);
                }
            })
            .fail(function(jqxhr, settings, ex) {
                alert('<?php etranslate('Error'); ?>:' + ex);
            });
    }

    // Add a new layer
    function add_layer(remoteCalLogin) {
        console.log('add_layer(' + remoteCalLogin + ')');
        // Find correct user in select list
        $('#addLayerRemoteCalendarLogin').val(remoteCalLogin);
        $('#editLayerColor').prop("value", '#000000'); // default to black
        // Also change the background color of the sample.
        //$('#editLayerColor_sample').style.background =
        //  ( id < 0 ? '#000000' : layers[id]['color'] );
        $('#editLayerDups').prop("checked", false);
        $('#edit-layer-dialog').show();
    }

    // Handler for save in Add Layer window
    function edit_layer_window_closed() {
        var layeruser = '<?php echo $login; ?>';
        var source = $('#addLayerRemoteCalendarLogin').val();
        var color = $('#editLayerColor').val();
        var dups = $('#editLayerDups').is(':checked') ? 'Y' : 'N';
        var action = 'save';
        console.log("Sending save...\nlayeruser: " + layeruser +
          "\nsource: " + source + "\ncolor: " + color + "\ndups: " + dups);

        $.post('layers_ajax.php', {
            action: action,
            id: -1,
            layeruser: layeruser,
            source: source,
            color: color,
            dups: dups
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
            // Reload users
            load_users();
          });
    }

    // Init tooltips
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
</body>
<?php echo print_trailer(); ?>