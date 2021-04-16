<?php

/**
 * Description
 *   Handler for AJAX requests from users_mgmt.php, nonuser_mgmt.php
 *   and remotecal_mgmt.php.
 */
include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.php';

$WebCalendar = new WebCalendar(__FILE__);

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';
require_valid_referring_url();

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';
include 'includes/ajax.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();
load_user_preferences();
$WebCalendar->setLanguage();

$action = getValue('action');

$sendPlainText = false;
$format = getValue('format');
if (
  !empty($format) &&
  ($format == 'text' || $format == 'plain')
) {
  $sendPlainText = true;
}

$notAuthStr = print_not_auth();
$deleteStr = translate('Deleting users not supported.');
$notIdenticalStr = translate('The passwords were not identical.');
$noPasswordStr = translate('You have not entered a password.');
$blankUserStr = translate('Username cannot be blank.');

// Make sure this is only called from user_mgmt.php
if (!empty($_SERVER['HTTP_REFERER'])) {
  $refurl = parse_url($_SERVER['HTTP_REFERER']);
  if (!empty($refurl['path']))
    $referer = strrchr($refurl['path'], '/(user_mgmt|resourcecal_mgmt).php');
}
#if ($referer != '/user_mgmt.php' && $referer != '/resourcecal_mgmt.php') {
#  activity_log(0, $login, $login, SECURITY_VIOLATION, 'Hijack attempt:edit_user');
#  ajax_send_error(translate('Not authorized'));
#  exit;
#}

$error = '';

if ($action == 'userlist') {
  // Use JSON to encode our list of users.
  $userlist = user_get_users();
  $ret_users = [];
  foreach ($userlist as $user) {
    // Skip public user
    if ($user['cal_login'] != '__public__') {
      $ret_users[] =  [
        'login' => $user['cal_login'],
        'lastname' => $user['cal_lastname'],
        'firstname' => $user['cal_firstname'],
        'is_admin' => empty($user['cal_is_admin']) ? 'N' : $user['cal_is_admin'],
        'enabled' => empty($user['cal_enabled']) ? 'Y' : $user['cal_enabled'],
        'email' => $user['cal_email'],
        'fullname' => $user['cal_fullname']
      ];
      // Not including password hash 'cal_password'
    }
  }
  ajax_send_object('users', $ret_users, $sendPlainText);
} else if ($action == 'save' && ($is_admin || getPostValue('login') == $login)) {
  // Only admin user can add/edit other users
  if (!$is_admin) {
    if (!access_can_access_function(ACCESS_USER_MANAGEMENT))
      $error = $notAuthStr;
  } else if (!$admin_can_add_user) {
    $error = translate('Unsupported action');
  }
  if (empty($error)) {
    save_user(
      getPostValue('add') == '1' ? true : false,
      getPostValue('login'),
      getPostValue('lastname'),
      getPostValue('firstname'),
      getPostValue('is_admin') == 'Y' ? 'Y' : 'N',
      getPostValue('enabled') == 'Y' ? 'Y' : 'N',
      getPostValue('email'),
      getPostValue('password')
    );
  }
  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else if ($action == 'set-password') {
  $password = getPostValue('password');
  $user = getPostValue('login');

  if (empty($user)) {
    $error = translate('Unsupported action');
  } else if (empty($password)) {
    $error = $blankUserStr;
  } else {
    if (!access_can_access_function(ACCESS_USER_MANAGEMENT) && !access_can_access_function(ACCESS_ACCOUNT_INFO))
      $error = $notAuthStr;
  }
  if (empty($error)) {
    user_update_user_password($user, $password);
    activity_log(
      0,
      $login,
      $user,
      LOG_USER_UPDATE,
      translate('Set Password')
    );
  }
  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else if ($action == 'delete') {
  $user = getPostValue('login');
  // Only admin user can add/edit other users
  if (!$is_admin) {
    if (!access_can_access_function(ACCESS_USER_MANAGEMENT))
      $error = $notAuthStr;
  } else if (!$admin_can_add_user) {
    $error = translate('Unsupported action');
  } else if (empty($user)) {
    $error = translate('Unsupported action') . ': ' . $blankUserStr;
  } else if ($user == $login) {
    // Cannot delete yourself
    $error = translate('Unsupported action');
  }
  if (empty($error)) {
    // TODO: user_delete_user doesn't do any error checking...
    user_delete_user($user); // Will also delete user's events.
    activity_log(0, $login, $user, LOG_USER_DELETE, '');
  }
  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else if ($action == 'remote-cal-list') {
  // Get layers for this user so we can see if the remote calendars are being used as a layer.
  load_user_layers($login, 1);
  $active_layers = [];
  foreach ( $layers as $layer ) {
    $active_layers[$layer['cal_layeruser']] = 1;
  }
  // Use JSON to encode our list of remote calendar users.
  $userlist = get_nonuser_cals($login, true);
  //echo "<pre>"; print_r($userlist); echo "</pre>"; exit;
  $ret_users = [];
  foreach ($userlist as $user) {
    // Skip public user
    if ($user['cal_login'] != '__public__') {
      $cnt = empty($active_layers[$user['cal_login']]) ? 0 : 1;
      $ret_users[] =  [
        'login' => $user['cal_login'],
        'lastname' => $user['cal_lastname'],
        'firstname' => $user['cal_firstname'],
        'admin' => $user['cal_admin'],
        'public' => empty($user['cal_is_public']) ? 'Y' : $user['cal_is_public'],
        'url' => $user['cal_url'],
        'fullname' => $user['cal_fullname'],
        'layercount' => $cnt
      ];
      // Not including password hash 'cal_password'
    }
  }
  ajax_send_object('users', $ret_users, $sendPlainText);
} else if ($action == 'save-remote-cal') {
  // Verify access to this page is allowed.
  if ($REMOTES_ENABLED != 'Y' || (access_is_enabled() && !access_can_access_function(ACCESS_IMPORT))) {
    $error = $notAuthStr;
  }
  if (empty($error)) {
    $error = save_remote_calendar(
      getPostValue('add') == '1' ? true : false,
      getPostValue('login'),
      getPostValue('lastname'),
      getPostValue('firstname'),
      getPostValue('url'),
      getPostValue('public')
    );
  }
  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else if ($action == 'delete-remote-cal') {
  $username = getPostValue('login');
  // Make sure the current user is the admin of this remote calendar.

  // Delete events from this remote calendar.
  $error = delete_remote_calendar($username);

  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else {
  ajax_send_error(translate('Unsupported action') . ': ' . $action);
}

exit;


// Add/Update a user
// We ignore password params on an update since there is a separate function
// for updating passwords.
function save_user($add, $user, $lastname, $firstname, $is_admin, $enabled, $email, $password)
{
  global $error, $blankUserStr, $login;

  if (addslashes($user) != $user) {
    $error = 'Invalid characters in login.';
  } else if ($add && empty($user)) {
    $error = $blankUserStr;
  }

  if (empty($error) && $add) {
    // Add user
    user_add_user(
      $user,
      $password,
      $firstname,
      $lastname,
      $email,
      $is_admin,
      $enabled
    );
    activity_log(
      0,
      $login,
      $user,
      LOG_USER_ADD,
      "$firstname $lastname"
        . (empty($email) ? '' : " <$email>")
    );
  } else if (empty($error)) {
    // Update user
    user_update_user($user, $firstname, $lastname, $email, $is_admin, $enabled);
    activity_log(
      0,
      $login,
      $user,
      LOG_USER_UPDATE,
      "$firstname $lastname" . (empty($email) ? '' : " <$email>")
    );
  }
}

// Add or update a remote calendar.
function save_remote_calendar($isAdd, $username, $lastname, $firstname, $url, $ispublic)
{
  global $login, $PUBLIC_ACCESS;
  $error = '';

  // This remote calendar cannot be used as a public calendar if Public Access is
  // not enabld in settings.
  if (empty($PUBLIC_ACCESS) || $PUBLIC_ACCESS != 'Y') {
    $ispublic = 'N';
  } else if (empty($ispublic) || $ispublic != 'Y') {
    $ispublic = 'N';
  }

  // Check for invalid characters in username
  if (!preg_match('/^[\w]+$/', $username)) {
    $error = translate('Calendar ID') . ' ' . translate('word characters only') . '.';
  }

  // Might want to move this into user.php instead of having SQL here... 
  if (empty($error) && !$isAdd) {
    // Updating
    $query_params = [];
    $sql = 'UPDATE webcal_nonuser_cals SET cal_lastname = ?';
    $query_params[] = $lastname;
    $sql .= ', cal_firstname = ?';
    $query_params[] = $firstname;
    $sql .= ', cal_url = ?';
    $query_params[] = $url;
    $sql .= ', cal_is_public = ?';
    $query_params[] = $ispublic;
    // NOTE: We don't update the 'admin' of the remote calendar.
    // Whoever created it owns it forever.
    $sql .= ' WHERE cal_login = ?';
    $query_params[] = $username;

    if (!dbi_execute($sql, $query_params)) {
      $error = db_error();
    } else {
      activity_log(
        0,
        $login,
        $username,
        LOG_USER_UPDATE,
        'Updated remote calendar'
      );
    }
    return $error;
  } else if (empty($error)) {
    // Add
    if (!dbi_execute(
      'INSERT INTO webcal_nonuser_cals (cal_login, ' .
        'cal_firstname, cal_lastname, cal_admin, cal_is_public, cal_url) ' .
        'VALUES ( ?, ?, ?, ?, ?, ? )',
      [$username, $firstname, $lastname, $login, $ispublic, $url]
    )) {
      $error = db_error();
    } else {
      activity_log(
        0,
        $login,
        $username,
        LOG_USER_ADD,
        'Added remote calendar'
      );
    }

    return $error;
  }
}

function delete_remote_calendar($username)
{
  global $login, $notAuthStr;
  // Make sure the current user is the admin of this remote calendar.
  nonuser_load_variables($username, "TESTLOAD");
  if (empty($GLOBALS["TESTLOADadmin"]) || $GLOBALS["TESTLOADadmin"] != $login) {
    return $notAuthStr;
  }

  // Delete any layers other users may have that point to this user.
  dbi_execute(
    'DELETE FROM webcal_user_layers WHERE cal_layeruser = ?',
    [$username]
  );

  // Delete any UAC calendar access entries for this  user.
  dbi_execute('DELETE FROM webcal_access_user WHERE cal_login = ?
     OR cal_other_user = ?', [$username, $username]);

  // Delete any UAC function access entries for this  user.
  dbi_execute(
    'DELETE FROM webcal_access_function WHERE cal_login = ?',
    [$username]
  );

  // Delete user.
  if (!dbi_execute(
    'DELETE FROM webcal_nonuser_cals WHERE cal_login = ?',
    [$username]
  )) {
    $error = db_error();
  }

  if (empty($error)) {
    activity_log(
      0,
      $login,
      $username,
      LOG_USER_DELETE,
      'Deleted remote calendar'
    );
  }

  return $error;
}
