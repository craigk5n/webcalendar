<?php
/**
 * Group Management.
 */

include_once 'includes/init.php';

if (
    empty($login) || $login == '__public__' ||
    empty($GROUPS_ENABLED) || $GROUPS_ENABLED != 'Y'
) {
    // Do not allow public access.
    do_redirect(empty($STARTVIEW) ? 'month.php' : $STARTVIEW);
    exit;
}

if ($is_admin || (access_is_enabled() && access_can_access_function(ACCESS_USER_MANAGEMENT))) {
    // User has access
} else {
    do_redirect(empty($STARTVIEW) ? 'month.php' : $STARTVIEW);
}
$LOADING = '<center><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></center>';

$areYouSure = translate('Are you sure you want to delete this group?');
$deleteGroupInfo = translate('This action cannot be undone.');

$yesStr = translate('Yes');
$noStr = translate('No');
$noNameError = translate('Group Name cannot be blank.');
$noUsersSelectedError = translate('You must selected one or more users.');

print_header(
    '',
    '',
    'onload="load_groups();"'
);

?>
<h2><?php etranslate('Groups'); ?></h2>

<!-- Error Alert -->
<div id="main-dialog-alert" class="alert alert-info" style="display: none">
    <span id="infoMessage"></span>
    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
</div>
<!-- Users loaded via AJAX -->
<table class="table table-striped" id="group-table">
    <thead>
        <tr>
            <th scope="col"><?php etranslate('Group name') ?></th>
            <th scope="col"><?php etranslate('Created by') ?></th>
            <th scope="col"><?php etranslate('Updated') ?></th>
            <th scope="col"><?php etranslate('Users') ?></th>
            <th>
                <!-- dropdown menu -->
            </th>
        </tr>
    <tbody id="group-tbody">
    </tbody>
    </thead>
</table>

<div class="userButtons">
    <input class="btn btn-primary" type="button" value="<?php etranslate('Add Group'); ?>..." onclick="return edit_group(0,'')" />
</div>

<!-- add/edit group modal dialog -->
<div id="edit-group-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="edit-group-title" class="modal-title">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#edit-group-dialog').hide();">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Error Alert -->
                <div id="edit-group-dialog-alert" class="alert alert-danger" style="display: none">
                    <strong><?php etranslate("Error"); ?>!</strong>&nbsp;<span id="errorMessage">A problem has been occurred while submitting your data.</span>
                    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
                </div>
                <form name="editGroupForm" id="editGroupForm">
                    <input type="hidden" name="editGroupAdd" id="editGroupAdd" value="0" />
                    <input type="hidden" name="editGroupId" id="editGroupId" value="0" />
                    <div class="form-inline" is="divEditName">
                        <label class="col-5" for="editName"><?php etranslate('Group name') ?>: </label>
                        <input required type="text" class="col-7 form-control" id="editName" name="editName" placeholder="<?php echo translate('New group name') . ' (' . translate('required') . ')'; ?>" />
                        <div id="invalid-name-error" class="invalid-feedback text-right">
                            <?php echo $noNameError; ?>
                        </div>
                    </div>
                    <div class="form-inline mt-1" id="div-editUsers">
                        <label class="col-5" for="editUsers"><?php etranslate('Users') ?>: </label>
                        <select multiple size="10" class="col-7 form-control" id="editUsers" name="editUsers">
                        <?php
                            if (empty($NONUSER_ENABLED) || $NONUSER_ENABLED != 'Y') {
                                $users = get_my_users();
                            } else {
                                if (!empty($NONUSER_AT_TOP) && $NONUSER_AT_TOP == 'Y') {
                                    $users = array_merge(get_nonuser_cals($login), get_my_users());
                                } else {
                                    $users = array_merge(get_my_users(), get_nonuser_cals($login));
                                }
                            }   
                            foreach ($users as $user) {
                                echo '<option value="' . htmlspecialchars($user['cal_login']) . '">' . htmlspecialchars($user['cal_fullname']) . "</option>\n";
                            }
                        ?>
                        <select>
                    </div>

                    <div class="modal-footer">
                        <input class="form-control btn btn-secondary" onclick="$('#edit-group-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                        <input class="form-control btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Save"); ?>" onclick="save_handler();" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- delete group modal dialog -->
<div id="delete-group-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="delete-group-title" class="modal-title"><?php etranslate('Delete Group'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#delete-group-dialog').hide();">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="p-3"><?php echo $areYouSure; ?></div>
                <div class="p-3 m-3 text-danger"><?php echo $deleteGroupInfo; ?></div>
                <form name="deleteGroupForm" id="deleteGroupForm">
                    <input type="hidden" name="deleteGroupId" id="deleteGroupId" />
                    <div class="form-inline" id="divdeleteGroup">
                        <label class="col-5" for="deleteGroupName"><?php etranslate('Group name') ?>: </label>
                        <input disabled="true" type="text" class="col-7 form-control" id="deleteGroupName" name="deleteGroupName" />
                    </div>
                </form>
                <br>
                <div class="modal-footer">
                    <input class="form-control btn btn-secondary" onclick="$('#delete-group-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                    <input class="form-control btn btn-danger" type="submit" name="delete" value="<?php etranslate('Delete') ?>" onclick="delete_handler ();" />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function group_menu(id, name) {
        console.log('group_menu(' + id + ', "' + name + '")');
        // Dropdown menu
        ret = '<div class="btn-group dropleft float-right">\n' +
            '<button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
            '<span class="sr-only">Toggle Dropdown</span> </button> <div class="dropdown-menu">\n';
        // Edit
        ret += "<a class='clickable dropdown-item' onclick=\"return edit_group(" + id + ", '" + name +
            "');\"><?php etranslate('Edit Group'); ?></a>";
        // Delete Group
        ret += "<a class='clickable dropdown-item' onclick=\"return delete_group(" + id + ", '" + name +
            "');\"><?php etranslate('Delete Group'); ?></a>";
        ret += "</div></div>\n";
        return ret;
    }

    function load_groups() {
        groups = [];
        $('#group-tbody').html('<tr><td colspan="5"><?php echo $LOADING; ?></td></tr>');
        $.post('users_ajax.php', {
                action: 'group-list',
                csrf_form_key: '<?php echo getFormKey(); ?>'
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
                console.log('response.groups.length=' + response.groups.length);
                var tbody = '';
                for (var i = 0; i < response.groups.length; i++) {
                    var g = response.groups[i];
                    console.log("Group " + i + ": " + "id=" + g.group_id + ", name=" + g.name);
                    groups[i] = {
                        id: g.group_id,
                        name: g.name,
                        owner: g.owner,
                        last_update: g.last_update,
                        users: g.users
                    };
                    var users = '';
                    for (var j = 0; j < g.users.length; j++) {
                        if (j > 0)
                            users += ', ';
                        users +=
                            g.users[j]['cal_fullname'] ? g.users[j]['cal_fullname'] : g.users[j]['cal_login'];
                    }
                    groups[i].usernames = users;
                    tbody += '<tr><td>' + g.name + '</td><td>' + g.owner +
                        '</td><td>' + g.last_update + '</td><td>' + users +
                        '</td><td>' + group_menu(g.group_id, g.name) + '</td></tr>\n';
                }
                $('#group-tbody').html(tbody);
            },
            'json');
    }

    function edit_group(id) {
        console.log('edit_group(' + id + ')');
        $('#edit-group-dialog-alert').hide();
        $('#invalid-name-error').hide();
        // Clear previous user selections
        $('#editUsers option').attr('selected',false).change();
        //$('#editUsers').val('').trigger("change");
        // Find correct group in our group list
        var group = null;
        var found = false;
        for (var i = 0; i < groups.length; i++) {
            if (id == groups[i]['id']) {
                found = true;
                group = groups[i];
            }
        }
        var selectedUsers = [];
        var cnt = 0;
        for (var i = 0; found && id > 0 && i < group.users.length; i++ ) {
            selectedUsers[cnt++] = group.users[i]['cal_login'];
        }
        if (id == 0) {
            // Add group
            $('#edit-group-title').html('<?php etranslate('Add Group'); ?>');
            $('#editGroupId').prop("value", "0");
            $('#editUserAdd').prop("value", "1");
            $('#editName').prop("value", "");
            // Select current user only
            $('#editUsers option[value=<?php echo $login;?>]').attr('selected',true).change();
        } else {
            // Edit user
            $('#edit-group-title').html('<?php etranslate('Edit Group'); ?>');
            $('#editGroupId').prop("value", id);
            $('#editUserAdd').prop("value", "0");
            $('#editName').prop("value", group['name']);
            for (var i = 0; i < cnt; i++) {
                console.log('Selected user: ' + selectedUsers[i]);
                $('#editUsers option[value=' + selectedUsers[i] + ']').attr('selected',true).change();
            }

        }
        $('#edit-group-dialog').show();
    }

    function save_handler() {
        var id =$('#editGroupId').val();
        var name = $('#editName').val();
        var add = id > 0 ? false : true;

        $('#invalid-name-error').hide();

        var selectedUsers = $('#editUsers').val();
        if (! selectedUsers || selectedUsers.length == 0) {
            $('#errorMessage').html('<?php echo  $noUsersSelectedError; ?>');
            $('#edit-group-dialog-alert').show();
            return;
        }
        // Use space as delimeter since logins cannot have spaces in them.
        var users = selectedUsers.join(' ');

        var foundError = false;
        // Name required
        if ($('#editName').val() == "") {
            $('#invalid-name-error').show();
            foundError = true;
        }

        if (foundError)
            return;

        console.log("Sending save...\nadd " + add + ", id: " + id + "\nname: " + name +
            "\nusers: " + users);
        var error = '';

        $.post('users_ajax.php', {
                    add: add ? 1 : 0, // "1" or "0"
                    action: "save-group",
                    id: id,
                    name: name,
                    users: users,
                    csrf_form_key: '<?php echo getFormKey(); ?>'
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
                        // Reload users
                        load_groups();
                    }
                },
                'json')
            .done(function() {
                if (error.length == 0) {
                    if (add == 1) {
                        $('#infoMessage').html('<?php echo translate('Group successfully added.') ?>');
                    } else {
                        $('#infoMessage').html('<?php etranslate('Group successfully updated.') ?>');
                    }
                    $('#main-dialog-alert').show();
                    $('#edit-group-dialog').hide();
                } else {
                    $('#errorMessage').html(error);
                    $('#edit-group-dialog-alert').show();
                }
            })
            .fail(function(jqxhr, settings, ex) {
                $('#errorMessage').html('<?php etranslate('Error'); ?>:' + ex);
                $('#edit-group-dialog-alert').show();
            });
    }

    function delete_group(id, name) {
        console.log('delete_group(' + id + ', "' + name + '")');
        console.log('id = "' + id + '"');
        $('#deleteGroupId').val(id);
        $('#deleteGroupName').val(name);
        $('#delete-group-dialog').show();
    }

    function delete_handler() {
        var id = $('#deleteGroupId').val();
        console.log("Sending delete for group: " + id);
        var error = '';

        $.post('users_ajax.php', {
                    action: "delete-group",
                    id: id,
                    csrf_form_key: '<?php echo getFormKey(); ?>'
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
                    $('#delete-group-dialog').hide();
                    // Reload users
                    load_groups();
                    $('#infoMessage').html('<?php etranslate('Group successfully deleted.') ?>');
                    $('#main-dialog-alert').show();
                } else {
                    alert('<?php etranslate('Error'); ?>: ' + error);
                }
            })
            .fail(function(jqxhr, settings, ex) {
                alert('<?php etranslate('Error'); ?>:' + ex);
            });
    }
</script>

<?php echo print_trailer(); ?>
