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

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include 'includes/access.php';
include 'includes/validate.php';
include 'includes/ajax.php';
include 'includes/xcal.php';

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
$invalidFirstName = translate('Invalid first name.');
$invalidLastName = translate('Invalid last name.');

// Make sure this is only called from user_mgmt.php
if (!empty($_SERVER['HTTP_REFERER'])) {
  $refurl = parse_url($_SERVER['HTTP_REFERER']);
  if (!empty($refurl['path']))
    $referer = strrchr($refurl['path'], '/(user_mgmt|resourcecal_mgmt).php');
}
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
  $post_firstname = getPostValue('firstname');
  $post_lastname = getPostValue('lastname');
  if (addslashes($post_firstname) != $post_firstname || strip_tags_content($post_firstname) != $post_firstname) {
    $error = $invalidFirstName;
  }
  if (addslashes($post_lastname) != $post_lastname || strip_tags_content($post_lastname) != $post_lastname) {
    $error = $invalidLastName;
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
  foreach ($layers as $layer) {
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
      $event_cnt = get_event_count_for_user($user['cal_login']);
      $last_upd = get_remote_calendar_last_update($user['cal_login']);
      $last_checked = get_remote_calendar_last_checked($user['cal_login']);
      $ret_users[] =  [
        'login' => $user['cal_login'],
        'lastname' => $user['cal_lastname'],
        'firstname' => $user['cal_firstname'],
        'admin' => $user['cal_admin'],
        'public' => empty($user['cal_is_public']) ? 'Y' : $user['cal_is_public'],
        'url' => $user['cal_url'],
        'fullname' => $user['cal_fullname'],
        'layercount' => $cnt,
        'eventcount' => $event_cnt,
        'lastupdated' => empty($last_upd) ? '' : date_to_str($last_upd, '', false),
        'lastchecked' => empty($last_checked) ? '' : date_to_str($last_checked, '', false)
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
  if (addslashes($firstname) != $firstname || strip_tags_content($firstname) != $firstname) {
    $error = $invalidFirstName;
  }
  if (addslashes($lastname) != $lastname || strip_tags_content($lastname) != $lastname) {
    $error = $invalidLastName;
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
} else if ($action == 'reload-remote-cal') {
  // import_data may output stuff, so catch it and discard.
  ob_start ();
  if (!ini_get('allow_url_fopen')) {
    $error = 'Your PHP setting for allow_url_fopen will not allow a remote calendar to be downloaded.';
  }
  if (empty($error)) {
    $username = getPostValue('login');
    $cals = get_nonuser_cals($login, true);
    $url = '';
    $found = 0;
    for ($i = 0; $i < count($cals); $i++) {
      if ($cals[$i]['cal_login'] == $username) {
        $url = $cals[$i]['cal_url'];
        $found = 1;
      }
    }
    if (!$found) {
      $error = 'No such remote calendar ' . $username;
    } else if (empty($url)) {
      $error = 'Calendar is not a remote calendar';
    }
  }
  $message = '';
  if (empty($error) && !empty($url)) {
    $arr = load_remote_calendar($username, $url);
    if (empty($arr[0])) {
      // Success (or not updated)
      if (!empty($arr[3])) {
        $message = $arr[3];
      } else {
        $message = $arr[1] . ' ' . translate('events added') . ', ' . $arr[2] . ' ' . translate('events deleted');
      }
    } else {
      // Error
      $error = $arr[3];
    }
  }
  ob_end_clean();
  if ($error == '') {
    //echo "SUCCESS: $message\n";
    ajax_send_success(false, $message);
  } else {
    ajax_send_error($error);
  }
} else if ($action == 'resource-cal-list') {
  // Use JSON to encode our list of resource calendars (aka "nonuser" calendars).
  $userlist = get_nonuser_cals();
  //echo "<pre>"; print_r($userlist); echo "</pre>"; exit;
  $ret_users = [];
  foreach ($userlist as $user) {
    // Skip public user && and ignore those with URL (remote calendars)
    if ($user['cal_login'] != '__public__' && empty($user['cal_url'])) {
      $event_cnt = get_event_count_for_user($user['cal_login']);
      $ret_users[] =  [
        'login' => $user['cal_login'],
        'lastname' => $user['cal_lastname'],
        'firstname' => $user['cal_firstname'],
        'admin' => $user['cal_admin'],
        'public' => empty($user['cal_is_public']) ? 'Y' : $user['cal_is_public'],
        'url' => $user['cal_url'],
        'fullname' => $user['cal_fullname'],
        'eventcount' => $event_cnt
      ];
      // Not including password hash 'cal_password'
    }
  }
  ajax_send_object('users', $ret_users, $sendPlainText);
} else if ($action == 'save-resource-cal') {
  // Verify access to this page is allowed.
  if (! $is_admin) {
    $error = $notAuthStr;
  }
  if (addslashes($firstname) != $firstname || strip_tags_content($firstname) != $firstname) {
    $error = $invalidFirstName;
  }
  if (addslashes($lastname) != $lastname || strip_tags_content($lastname) != $lastname) {
    $error = $invalidLastName;
  }
  if (empty($error)) {
    $error = save_resource_calendar(
      getPostValue('add') == '1' ? true : false,
      getPostValue('login'),
      getPostValue('lastname'),
      getPostValue('firstname'),
      getPostValue('public'),
      getPostValue('admin')
    );
  }
  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else if ($action == 'group-list') {
  // Use JSON to encode our list of groups.
  $groups = get_groups($login, true);
  $ret_groups = [];
  foreach ($groups as $group) {
    $ret_groups[] =  [
      'group_id' => $group['cal_group_id'],
      'name' => $group['cal_name'],
      'owner' => $group['cal_owner'],
      'last_update' => empty($group['cal_last_update']) ? '' : date_to_str($group['cal_last_update'], '', false),
      'users' => $group['cal_users']
    ];
  }
  ajax_send_object('groups', $ret_groups, $sendPlainText);
} else if ($action == 'save-group') {
  $ret = save_group(
    getPostValue('add') == '1' ? true : false,
    getPostValue('id'),
    getPostValue('name'),
    getPostValue('users')
  );
  $error = $ret[0];
  $msg = $ret[1];
  if ($error == '')
    ajax_send_success(false, $msg);
  else
    ajax_send_error($error);
} else if ($action == 'delete-group') {
  $id = getPostValue('id');
  if (empty($id)) {
    $error = "Missing Group Id from delete request";
  } else {
    // Delete this group.
    dbi_execute ( 'DELETE FROM webcal_group WHERE cal_group_id = ? ',
      [$id] );
    dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_group_id = ? ',
     [$id] );
  }
  if ($error == '')
    ajax_send_success();
  else
    ajax_send_error($error);
} else {
  ajax_send_error(translate('Unsupported action') . ': ' . $action);
}

exit;


function strip_tags_content($text) {
  return strip_tags($text);
}

// Add/Update a user
// We ignore password params on an update since there is a separate function
// for updating passwords.
function save_user($add, $user, $lastname, $firstname, $is_admin, $enabled, $email, $password)
{
  global $error, $blankUserStr, $login;

  if (addslashes($user) != $user || strip_tags_content($user) != $user) {
    $error = 'Invalid characters in login.';
  } else if ($add && empty($user)) {
    $error = $blankUserStr;
  }

  if (addslashes($firstname) != $firstname || strip_tags_content($firstname) != $firstname) {
    $error = $invalidFirstName;
  }
  if (addslashes($lastname) != $lastname || strip_tags_content($lastname) != $lastname) {
    $error = $invalidLastName;
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

// Add or update a resource calendar
function save_resource_calendar($isAdd, $username, $lastname, $firstname, $ispublic, $admin) {
  // A Resource Calendar is identical in the database to a Remote Calendar except that
  // the URL is empty and you can specify the Admin (owner).  A Remote Calendar is always
  // owned by an Admin user.
  return save_remote_calendar($isAdd, $username, $lastname, $firstname, null, $ispublic, $admin);
}

// Add or update a remote calendar.
function save_remote_calendar($isAdd, $username, $lastname, $firstname, $url, $ispublic, $owner='')
{
  global $login, $PUBLIC_ACCESS;
  $error = '';

  // This calendar cannot be used as a public calendar if Public Access is
  // not enabled in settings.
  if (empty($PUBLIC_ACCESS) || $PUBLIC_ACCESS != 'Y') {
    $ispublic = 'N';
  } else if (empty($ispublic) || $ispublic != 'Y') {
    $ispublic = 'N';
  }

  // Check for invalid characters in username
  if (!preg_match('/^[\w]+$/', $username)) {
    return translate('Calendar ID') . ' ' . translate('word characters only') . '.';
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
    if (!empty($owner)) {
      $sql .= ', cal_admin = ?';
      $query_params[] = $owner;
    }
    $sql .= ', cal_is_public = ?';
    $query_params[] = $ispublic;
    // NOTE: We don't update the 'admin' of the remote calendar.
    // Whoever created it owns it forever.
    $sql .= ' WHERE cal_login = ?';
    $query_params[] = $username;

    if (!dbi_execute($sql, $query_params, false, false)) {
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
    // If no owner set, use the current user
    if (empty($owner)) {
      $owner = $login;
    }
    // Add
    if (!dbi_execute(
      'INSERT INTO webcal_nonuser_cals (cal_login, ' .
        'cal_firstname, cal_lastname, cal_admin, cal_is_public, cal_url) ' .
        'VALUES ( ?, ?, ?, ?, ?, ? )',
      [$username, $firstname, $lastname, $owner, $ispublic, $url], false, false
    )) {
      $error = dbi_error();
      if(stripos($error, "uplicate")>0) {
        $error = translate('Calendar ID already in use');
      } else {
        $error = db_error();
      }
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

  // Delete events from this remote calendar.
  user_delete_events($username);

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

// Add or update a group.
function save_group($isAdd, $id, $name, $users) {
  global $login;
  $error = '';
  $dateYmd = date('Ymd');
  $msg = 'None';

  // Might want to move this into user.php instead of having SQL here... 
  if (!$isAdd) {
    // Updating
    $query_params = [];
    $sql = 'UPDATE webcal_group SET cal_name = ?';
    $query_params[] = $name;
    $sql .= ', cal_last_update = ?';
    $query_params[] = $dateYmd;
    $sql .= ' WHERE cal_group_id = ?';
    $query_params[] = $id;
    if (!dbi_execute($sql, $query_params, false, false)) {
      $error = db_error();
    } else {
      activity_log(
        0,
        $login,
        $login,
        LOG_USER_UPDATE,
        'Updated group: ' . $name
      );
      $msg = 'Group updated.';
    }
  } else {
    // Get next id
    $id = 1;
    $sql = 'SELECT MAX(cal_group_id) FROM webcal_group';
    $res = dbi_execute($sql);
    if ($res) {
      if ($row = dbi_fetch_row($res)) {
        $id = $row[0] + 1;
      }
      dbi_free_result($res);
    }
    // Add
    if (!dbi_execute(
      'INSERT INTO webcal_group (cal_group_id, cal_name, cal_owner, cal_last_update) ' .
      'VALUES ( ?, ?, ?, ?)',
      [$id, $name, $login, $dateYmd]
    )) {
      $error = dbi_error();
    } else {
      activity_log(
        0,
        $login,
        $login,
        LOG_USER_ADD,
        'Added new group: ' . $name
      );
      $msg = 'Group added.';
    }
  }

  // Now delete old group members and add new ones.
  if (empty($error)) {
    $msg .= ' Users added:';
    if (!$isAdd) {
      dbi_execute('DELETE FROM webcal_group_user where cal_group_id = ?', [$id]);
    }
    foreach (explode(' ',
      $users
    ) as $user) {
      $msg .= ' ' . $user;
      dbi_execute(
        'INSERT INTO webcal_group_user (cal_group_id, cal_login) ' .
        'VALUES (?,?)',
        [$id, $user]
      );
    }
  }

  return [$error, $msg];
}
?>
