<?php

/**
 * User Management.
 * 
 * See nonuser_mgmt.php for managing NonUser Calendars (Resource Calendars) and remote_mgmt.php for
 * Remote Calendars. 
 */

include_once 'includes/init.php';

if (empty($login) || $login == '__public__') {
    // Do not allow public access.
    do_redirect(empty($STARTVIEW) ? 'month.php' : $STARTVIEW);
    exit;
}

$LOADING = '<center><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></center>';
$doUser = (!access_is_enabled() ||
    access_can_access_function(ACCESS_ACCOUNT_INFO));
$doUsers = ($is_admin ||
    (access_is_enabled() &&
        access_can_access_function(ACCESS_USER_MANAGEMENT)));

$areYouSure = translate('Are you sure you want to delete this user?');
$deleteUserInfo = translate('This will delete all events for this user.') .
    ' ' . translate('This action cannot be undone.');

$yesStr = translate('Yes');
$noStr = translate('No');
$noLoginError = translate('Username cannot be blank.');
$invalidEmail = translate('Invalid email address');
$passwordsMismatchError = translate('The passwords were not identical.');
$noPasswordError = translate('You have not entered a password.');
$invalidFirstName = translate('Invalid first name.');
$invalidLastName = translate('Invalid last name.');

print_header(
    '',
    '',
    'onload="load_users();"'
);

?>


<h3><?php etranslate('Users'); ?></h3>
<!-- Error Alert -->
<div id="main-dialog-alert" class="alert alert-info" style="display: none">
    <span id="infoMessage"></span>
    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
</div>
<!-- Users loaded via AJAX -->
<table class="table table-striped" id="user-table">
    <thead>
        <tr>
            <th scope="col"><?php etranslate('Username') ?></th>
            <th scope="col"><?php etranslate('First Name') ?></th>
            <th scope="col"><?php etranslate('Last Name') ?></th>
            <th scope="col"><?php etranslate('E-mail address') ?></th>
            <th scope="col"><?php etranslate('Admin') ?></th>
            <th>
                <!-- dropdown menu -->
            </th>
        </tr>
    <tbody id="user-tbody">
    </tbody>
    </thead>
</table>

<br />
<?php if ($is_admin && $admin_can_delete_user && access_can_access_function(ACCESS_USER_MANAGEMENT)) { ?>
<div class="userButtons">
    <input class="btn btn-primary" type="button" value="<?php etranslate('Add User'); ?>..." onclick="return edit_user('')" />
</div>
<?php } ?>

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
                    <input type="hidden" name="editUserDelete" id="editUserDelete" value="0" />
                    <input type="hidden" name="editUserAdd" id="editUserAdd" value="0" />
                    <div class="form-inline" is="divEditUsername">
                        <label class="col-5" for="editUsername"><?php etranslate('Username') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editUsername" name="editUsername" placeholder="<?php echo translate('New username') . ' (' . translate('required') . ')'; ?>" MAXLENGTH="25" />
                    </div>
                    <div class="form-inline mt-1" id="div-editFirstname">
                        <label class="col-5 for=" editFirstname"><?php etranslate('First Name') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editFirstname" name="editFirstname" MAXLENGTH="25" />
                    </div>
                    <div class="form-inline mt-1" id="div-editLastname">
                        <label class="col-5 for=" editLastname"><?php etranslate('Last Name') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editLastname" name="editLastname" MAXLENGTH="25" />
                    </div>
                    <div class="form-inline mt-1" id="div-editEmail">
                        <label class="col-5 for=" editEmail"><?php etranslate('Email') ?>: </label>
                        <input type="email" class="col-7 form-control" id="editEmail" name="editEmail" MAXLENGTH="75" />
                    </div>
                    <div class="form-inline mt-1" id="div-editPassword1">
                        <label class="col-5 for=" editPassword1"><?php etranslate('Password') ?>: </label>
                        <input type="password" class="col-7 form-control" id="editPassword1" name="editPassword1" />
                    </div>
                    <div class="form-inline mt-1" id="div-editPassword2">
                        <label class="col-5 for=" editPassword2"><?php etranslate('Password (again)'); ?>: </label>
                        <input type="password" class="col-7 form-control" id="editPassword2" name="editPassword2" />
                    </div>
                    <div class="form-inline mt-1 mb-2" id="div-editEnabled">
                        <label class="col-5 for=" editEnabled"><?php etranslate('Enabled') ?>: </label>
                        <?php echo print_radio('editEnabled'); ?>
                    </div>
                    <div class="form-inline mt-1 mb-2" id="div-editIsAdmin">
                        <label class="col-5 for=" editIsAdmin"><?php etranslate('Admin') ?>: </label>
                        <?php echo print_radio('editIsAdmin'); ?>
                    </div>

                    <div class="modal-footer">
                        <input class="btn btn-secondary" onclick="$('#edit-user-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                        <input class="btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Save"); ?>" onclick="save_handler();" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- change password user modal dialog -->
<div id="edit-password-dialog" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="edit-password-title" class="modal-title"><?php etranslate('Change Password'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#edit-password-dialog').hide();">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Error Alert -->
                <div id="edit-password-dialog-alert" class="alert alert-danger" style="display: none">
                    <strong><?php etranslate("Error"); ?>!</strong>&nbsp;<span id="changePasswordErrorMessage">A problem has been occurred while submitting your data.</span>
                    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
                </div>
                <form name="editPasswordForm" id="editPasswordForm">
                    <div class="form-inline" id="divEditPasswordUsername">
                        <label class="col-5" for="editPasswordUsername"><?php etranslate('Username') ?>: </label>
                        <input disabled="true" type="text" class="col-7 form-control" id="editPasswordUsername" name="editPasswordUsername" />
                    </div>
                    <div class="form-inline mt-1" id="div-setPassword1">
                        <label class="col-5 for=" setPassword1"><?php etranslate('Password') ?>: </label>
                        <input type="password" class="col-7 form-control" id="setPassword1" name="setPassword1" />
                    </div>
                    <div class="form-inline mt-1" id="div-setPassword2">
                        <label class="col-5 for=" setPassword2"><?php etranslate('Password (again)'); ?>: </label>
                        <input type="password" class="col-7 form-control" id="setPassword2" name="setPassword2" />
                    </div>

                    <div class="modal-footer">
                        <input class="form-control btn btn-secondary" onclick="$('#edit-user-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                        <input class="form-control btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Set Password"); ?>" onclick="change_password_handler();" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- delete user modal dialog -->
<?php if ($is_admin && $admin_can_delete_user && access_can_access_function(ACCESS_USER_MANAGEMENT)) { ?>
    <div id="delete-user-dialog" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="delete-user-title" class="modal-title"><?php etranslate('Delete User'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#delete-user-dialog').hide();">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="p-3"><?php echo $areYouSure; ?></div>
                    <div class="p-3 m-3 text-danger"><?php echo $deleteUserInfo; ?></div>
                    <form name="deleteUserForm" id="deleteUserForm">
                        <div class="form-inline" is="divdeleteUsername">
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
<?php } ?>

<script>
    var myLogin = '<?php echo $login; ?>';

    function user_menu(login) {
        // Dropdown menu
        ret = '<div class="btn-group dropleft float-right">\n' +
            '<button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
            '<span class="sr-only">Toggle Dropdown</span> </button> <div class="dropdown-menu">\n';
        // Edit
        ret += "<a class='clickable dropdown-item' onclick=\"return edit_user('" + login +
            "');\"><?php etranslate('Edit User'); ?></a>";
        // Change Password
        ret += "<a class='clickable dropdown-item' onclick=\"return change_password('" + login +
            "');\"><?php etranslate('Change Password'); ?></a>";
        // Delete User
        <?php if ($is_admin && $admin_can_delete_user && access_can_access_function(ACCESS_USER_MANAGEMENT)) { ?>
            // Cannot delete yourself
            if (myLogin != login) {
                ret += '<div class="dropdown-divider"></div>';
                ret += "<a class='clickable dropdown-item' onclick=\"return delete_user('" + login +
                    "');\"><?php etranslate('Delete User'); ?></a>";
            }
        <?php } ?>
        ret += "</div></div>\n";
        return ret;
    }

    function load_users() {
        users = [];
        $('#user-tbody').html('<tr><td colspan="5"><?php echo $LOADING; ?></td></tr>');
        $.post('users_ajax.php', {
                action: 'userlist',
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
                console.log('response.users.length=' + response.users.length);
                var tbody = '';
                for (var i = 0; i < response.users.length; i++) {
                    var u = response.users[i];
                    users[i] = {
                        login: u.login,
                        lastname: u.lastname,
                        firstname: u.firstname,
                        is_admin: u.is_admin,
                        enabled: u.enabled,
                        email: u.email,
                        fullname: u.fullname
                    };
                    // Show users only to admins and self
		            <?php if (!$is_admin) { ?>
			            if (myLogin == u.login) {
		            <?php } ?>
                    tbody += '<tr><td>' + u.login + '</td><td>' + (u.firstname == null ? '' : u.firstname) +
                        '</td><td>' + (u.lastname == null ? '' : u.lastname) + '</td><td>' + (u.email == null ? '' : u.email) +
                        '</td><td>' +
                        (u.is_admin == 'Y' ? '<img class="button-icon-inverse" src="images/bootstrap-icons/check-circle.svg" />' : '') +
                        '</td><td>' + user_menu(u.login) + '</td></tr>\n';
                    <?php if (!$is_admin) { ?>
                        }
                    <?php } ?>
                }
                $('#user-tbody').html(tbody);
                //console.log('tbody=' + tbody);
            },
            'json');
    }

    // Minimal email validation.  There are lots of examples of REs for this that fail against valid
    // email addresses.
    function validateEmail() {
        var basicEmailRegex = /^[^\s@]+@[^\s@]+$/;
        var elem = $("#editEmail").val();
        // Allow empty
        if (elem.length == 0)
            return true;
        if (elem.match(basicEmailRegex))
            return true;
        else
            return false;
    }

    // Mostly make sure there is no HTML in here.
    function validateFirstName() {
        var firstNameRegex = /^[^<>]+$/;
        var elem = $("#editFirstname").val();
        // Allow empty
        if (elem.length == 0)
            return true;
        if (elem.match(firstNameRegex))
            return true;
        else
            return false;
    }

    // Mostly make sure there is no HTML in here.
    function validateLastName() {
        var lastNameRegex = /^[^<>]+$/;
        var elem = $("#editLastname").val();
        // Allow empty
        if (elem.length == 0)
            return true;
        if (elem.match(lastNameRegex))
            return true;
        else
            return false;
    }

    function edit_user(login) {
        console.log('edit_user(' + login + ')');
        $('#edit-user-dialog-alert').hide();
        if (login == "") {
            $('#editUserDeleteButton').prop('disabled', true);
        } else {
            $('#editUserDeleteButton').prop('disabled', false);
        }
        $('#editUserDelete').prop("value", 0);
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
            $('#edit-user-title').html('<?php etranslate('Add User'); ?>');
            $('#editUserAdd').prop("value", "1");
            $('#editUsername').prop("disabled", false);
            $('#editUsername').prop("value", "");
            $('#editFirstname').prop("value", "");
            $('#editLastname').prop("value", "");
            $('#editEmail').prop("value", "");
            $('#editPassword1').prop("value", "");
            $('#editPassword2').prop("value", "");
            $('#div-editPassword1').show();
            $('#div-editPassword2').show();
            $('#editIsAdmin_N').prop("checked", true);
            $('#editEnabled_Y').prop("checked", true);
        } else {
            // Edit user
            $('#edit-user-title').html('<?php etranslate('Edit User'); ?>');
            $('#editUserAdd').prop("value", "0");
            $('#editUsername').prop("value", user['login']);
            $('#editUsername').prop("disabled", true);
            $('#editUsername').prop("value", user['login']);
            $('#editFirstname').prop("value", user['firstname']);
            $('#editLastname').prop("value", user['lastname']);
            $('#editEmail').prop("value", user['email']);
            $('#editPassword1').prop("value", "");
            $('#editPassword2').prop("value", "");
            $('#div-editPassword1').hide();
            $('#div-editPassword2').hide();
            console.log('user enabled: ' + user['enabled']);
            if (user['enabled'] == 'Y')
                $('#editEnabled_Y').prop("checked", true);
            else
                $('#editEnabled_N').prop("checked", true);
            if (user['is_admin'] == 'Y')
                $('#editIsAdmin_Y').prop("checked", true);
            else
                $('#editIsAdmin_N').prop("checked", true);
        }
        $('#edit-user-dialog').show();
    }

    function save_handler() {
        var login = $('#editUsername').val();
        var firstname = $('#editFirstname').val();
        var lastname = $('#editLastname').val();
        var email = $('#editEmail').val();
        var is_admin = $('#editIsAdmin_Y').is(':checked') ? 'Y' : 'N';
        var enabled = $('#editEnabled_Y').is(':checked') ? 'Y' : 'N';
        // Only for add
        var password1 = $('#editPassword1').val();
        var password2 = $('#editPassword2').val();
        var retStatus = 1;
        var isAdd = 0;

        var add = $('#editUserAdd').val();
        if (add == "1") {
            isAdd = 1;
            if (login.length == 0) {
                $('#errorMessage').html('<?php echo  $noLoginError; ?>');
                $('#edit-user-dialog-alert').show();
                return;
            }
            if (password1.length == 0) {
                $('#errorMessage').html('<?php echo  $noPasswordError; ?>');
                $('#edit-user-dialog-alert').show();
                return;
            }
            if (password1 != password2) {
                $('#errorMessage').html('<?php echo $passwordsMismatchError; ?>');
                $('#edit-user-dialog-alert').show();
                return;
            }
        }
        if (!validateFirstName()&&false) {
            $('#errorMessage').html('<?php echo $invalidFirstName; ?>');
            $('#edit-user-dialog-alert').show();
            return;
        }
        if (!validateLastName()&&false) {
            $('#errorMessage').html('<?php echo $invalidLastName; ?>');
            $('#edit-user-dialog-alert').show();
            return;
        }
        // Validate email
        if (!validateEmail()) {
            $('#errorMessage').html('<?php echo $invalidEmail; ?>');
            $('#edit-user-dialog-alert').show();
            return;
        }
        console.log("Sending save...\nlogin: " + login + "\nfirstname: " + firstname +
            "\nlastname: " + lastname + "\nemail: " + email + "\nis_admin: " + is_admin);

        $.post('users_ajax.php', {
                    add: add, // "1" or "0"
                    action: "save",
                    login: login,
                    firstname: firstname,
                    lastname: lastname,
                    email: email,
                    is_admin: is_admin, // "Y" or "N"
                    enabled: enabled, // "Y" or "N"
                    password: password1, // For add only
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
                        alert('<?php etranslate('Error'); ?>: <?php etranslate('JSON error'); ?> - ' + err);
                        retStatus = 1;
                        return;
                    }
                    if (response.error) {
                        alert('<?php etranslate('Error'); ?>: ' + response.message);
                        retStatus = 1;
                        return;
                    }
                    // Close window
                    $('#edit-user-dialog').hide();
                    // Reload layers
                    load_users();
                    retStatus = 0;
                },
                'json').done(function() {
                if (retStatus == 0) {
                    if (isAdd) {
                        $('#infoMessage').html('<?php etranslate('User successfully added.') ?>');
                    } else {
                        $('#infoMessage').html('<?php etranslate('User successfully updated.') ?>')
                    }
                    $('#main-dialog-alert').show();
                }
            })
            .fail(function(jqxhr, settings, ex) {
                alert('<?php etranslate('Error'); ?>:' + ex);
            });
    }

    function change_password(user) {
        console.log('change_password(' + user + ')');
        $('#edit-password-dialog-alert').hide();
        $('#editPasswordUsername').val(user);
        $('#setPassword1').prop("value", "");
        $('#setPassword2').prop("value", "");
        $('#edit-password-dialog').show();
    }

    function change_password_handler() {
        var login = $('#editPasswordUsername').val();
        console.log("Setting password for username: " + login);
        var error = '';

        var password1 = $('#setPassword1').val();
        var password2 = $('#setPassword2').val();
        if (password1.length == 0) {
            $('#changePasswordErrorMessage').html('<?php echo  $noPasswordError; ?>');
            $('#edit-password-dialog-alert').show();
            return;
        }
        if (password1 != password2) {
            $('#changePasswordErrorMessage').html('<?php echo $passwordsMismatchError; ?>');
            $('#edit-password-dialog-alert').show();
            return;
        }

        $.post('users_ajax.php', {
                    action: "set-password",
                    login: login,
                    password: password1,
                    csrf_form_key: '<?php echo getFormKey(); ?>'
                },
                function(data, status) {
                    console.log('Data: ' + data);
                    var stringified = JSON.stringify(data);
                    console.log("change_password_handler Data: " + stringified + "\nStatus: " + status);
                    try {
                        var response = jQuery.parseJSON(stringified);
                        console.log('change_password_handler response=' + response);
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
                    console.log("change password success");
                    // Close window
                    $('#edit-password-dialog').hide();
                    // Reload layers
                    load_users();
                    $('#infoMessage').html('<?php etranslate('Password successfully updated.') ?>');
                    $('#main-dialog-alert').show();
                } else {
                    alert('<?php etranslate('Error'); ?>: ' + error);
                }
            })
            .fail(function(jqxhr, settings, ex) {
                alert('<?php etranslate('Error'); ?>:' + ex);
            });
    }

    <?php if ($is_admin && $admin_can_delete_user && access_can_access_function(ACCESS_USER_MANAGEMENT)) { ?>

        function delete_user(user) {
            console.log('delete_user(' + user + ')');
            $('#deleteUsername').val(user);
            $('#delete-user-dialog').show();
        }

        function delete_handler() {
            var login = $('#deleteUsername').val();
            console.log("Sending delete for username: " + login);
            var error = '';

            $.post('users_ajax.php', {
                        action: "delete",
                        login: login,
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
    <?php } ?>
</script>
</body>
<?php echo print_trailer(); ?>
