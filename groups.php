<?php
/**
 * Group Management.
 */

include_once 'includes/init.php';

if (empty($login) || $login == '__public__' ||
  empty($GROUPS_ENABLED) || $GROUPS_ENABLED != 'Y') {
    // Do not allow public access.
    do_redirect(empty($STARTVIEW) ? 'month.php' : $STARTVIEW);
    exit;
}

$LOADING = '<center><img src="images/loading_animation.gif" alt="" /></center>';
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

print_header(
    '',
    '',
    'onload="load_groups();"'
);

?>
<h2><?php etranslate('Groups');?></h2>

<!-- Error Alert -->
<div id="main-dialog-alert" class="alert alert-info" style="display: none">
    <span id="infoMessage"></span>
    <button type="button" class="close" onclick="$('.alert').hide()">&times;</button>
</div>
<!-- Users loaded via AJAX -->
<table class="table table-striped" id="user-table">
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
    <tbody id="user-tbody">
    </tbody>
    </thead>
</table>


<?php

$count = $lastrow = 0;
$newGroupStr = translate ( 'Add New Group' );

$res = dbi_execute ( 'SELECT cal_group_id, cal_name FROM webcal_group
  ORDER BY cal_name' );
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    echo ( $count == 0 ? '
      <ul>' : '' ) . '
        <li><a title="' . $row[1] . '" href="group_edit.php?id=' . $row[0] . '"'
     . $targetStr . $row[1] . '</a></li>';
    $count++;
    $lastrow = $row[0];
  }
  if ( $count > 0 )
    echo '
      </ul>';

  dbi_free_result ( $res );
}

?>

<div class="userButtons">
    <input class="btn btn-primary" type="button" value="<?php etranslate('Add Group'); ?>..." onclick="return edit_group('')" />
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
                <form name="editUserForm" id="editUserForm">
                    <input type="hidden" name="editUserDelete" id="editUserDelete" value="0" />
                    <input type="hidden" name="editUserAdd" id="editUserAdd" value="0" />
                    <div class="form-inline" is="divEditUsername">
                        <label class="col-5" for="editUsername"><?php etranslate('Username') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editUsername" name="editUsername" placeholder="<?php echo translate('New username') . ' (' . translate('required') . ')'; ?>" />
                    </div>
                    <div class="form-inline mt-1" id="div-editFirstname">
                        <label class="col-5 for=" editFirstname"><?php etranslate('First Name') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editFirstname" name="editFirstname" />
                    </div>
                    <div class="form-inline mt-1" id="div-editLastname">
                        <label class="col-5 for=" editLastname"><?php etranslate('Last Name') ?>: </label>
                        <input type="text" class="col-7 form-control" id="editLastname" name="editLastname" />
                    </div>
                    <div class="form-inline mt-1" id="div-editEmail">
                        <label class="col-5 for=" editEmail"><?php etranslate('Email') ?>: </label>
                        <input type="email" class="col-7 form-control" id="editEmail" name="editEmail" />
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
                        <input class="form-control btn btn-secondary" onclick="$('#edit-group-dialog').hide();" data-dismiss="modal" type="button" value="<?php etranslate("Cancel"); ?>" />
                        <input class="form-control btn btn-primary" data-dismiss="modal" type="button" value="<?php etranslate("Save"); ?>" onclick="save_handler();" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

function load_groups () {
}

</script>

<?php echo print_trailer (); ?>
