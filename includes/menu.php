<?php
/*
 * Top menu
 */
defined('_ISVALID') or die('You cannot access this file directly!');

global $ALLOW_VIEW_OTHER, $BodyX, $CATEGORIES_ENABLED, $DISPLAY_TASKS,
  $DISPLAY_TASKS_IN_GRID, $fullname, $has_boss, $HOME_LINK, $is_admin,
  $is_assistant, $is_nonuser, $is_nonuser_admin, $login, $login_return_path,
  $MENU_DATE_TOP, $NONUSER_ENABLED, $PUBLIC_ACCESS,
  $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL, $PUBLIC_ACCESS_CAN_ADD,
  $PUBLIC_ACCESS_OTHERS, $readonly, $REMOTES_ENABLED, $REPORTS_ENABLED,
  $REQUIRE_APPROVALS, $show_printer, $single_user, $START_VIEW, $thisday,
  $thismonth, $thisyear, $use_http_auth, $user, $user_fullname, $views, 
  $OVERRIDE_PUBLIC, $GROUPS_ENABLED;

/* -----------------------------------------------------------------------------
         First figure out what options are on and privileges we have
----------------------------------------------------------------------------- */
$can_add = (!empty($readonly) && $readonly != 'Y');
if (access_is_enabled())
  $can_add = access_can_access_function(ACCESS_EVENT_EDIT, $user);

if ($login == '__public__')
  $can_add = (access_is_enabled() ? $can_add : $PUBLIC_ACCESS_CAN_ADD == 'Y');

if (!$is_admin && !$is_assistant && !$is_nonuser_admin) {
  if ($is_nonuser)
    $can_add = false;
  else if (!empty($user) && $user != $login && $user != '__public__')
    $can_add = false;
}

$export_url = $import_url = $new_entry_url = $new_task_url = '';
$search_url = $select_user_url = $unapproved_url = '';

$help_url = 'help_index.php';

$mycal = (empty($STARTVIEW) ? 'index.php' : $STARTVIEW);

$mycal .= (!strpos($mycal, '.php') ? '.php' : '');

if ($can_add) {
  // Add new entry.
  $new_entry_url = 'edit_entry.php';

  if (!empty($thisyear)) {
    $good_date = 'year=' . $thisyear
      . (empty($thismonth) ? '' : '&month=' . $thismonth)
      . (empty($thisday) ? '' : '&day=' . $thisday);
    $new_entry_url .= "?$good_date";
  }
  // Add new task.
  if ($DISPLAY_TASKS_IN_GRID == 'Y' || $DISPLAY_TASKS == 'Y')
    $new_task_url = 'edit_entry.php?eType=task'
      . (empty($thisyear) ? '' : "&$good_date");
}

if ($single_user != 'Y') {
  // Today
  if (!empty($user) && $user != $login) {
    if (!empty($new_entry_url))
      $new_entry_url .= (strpos($new_entry_url, '?') !== FALSE ? '&' : '?') . 'user=' . $user;

    if (!empty($new_task_url))
      $new_task_url .= '&user=' . $user;
  }
  // List Unapproved.
  if (
    $login != '__public__' && !$is_nonuser && $readonly == 'N' &&
    ($REQUIRE_APPROVALS == 'Y' || $PUBLIC_ACCESS == 'Y')
  )
    $unapproved_url = 'list_unapproved.php'
      . ($is_nonuser_admin ? '?user=' . getValue('user') : '');

  // Another User's Calendar.
  if (($login == '__public__' && $PUBLIC_ACCESS_OTHERS != 'Y') ||
    ($is_nonuser && !access_is_enabled())
  ) {
    // Don't allow them to see other people's calendar.
  } else
  if ($ALLOW_VIEW_OTHER == 'Y' || $is_admin) {
    // Also, make sure they able to access either day/week/month/year view.
    // If not, the only way to view another user's calendar is a custom view.
    if (
      !access_is_enabled() ||
      access_can_access_function(ACCESS_ANOTHER_CALENDAR)
    ) {
      // Get count of users this user can see. If > 1, then...

      $ulist = array_merge(
        get_my_users($login, 'view'),
        get_my_nonusers($login, true, 'view')
      );

      //remove duplicates if any
      if (function_exists('array_intersect_key'))
        $ulist = array_intersect_key($ulist, array_unique(array_map('serialize', $ulist)));

      if (count($ulist) > 1)
        $select_user_url = 'select_user.php';
    }
  }
}
// Only display some links if we're viewing our own calendar.
if ((empty($user) || $user == $login) || (!empty($user) && access_is_enabled() &&
  access_user_calendar('view', $user))) {
  // Search
  if (access_can_access_function(ACCESS_SEARCH, $user))
    $search_url = 'search.php';
}
if (empty($user) || $user == $login) {
  // Import/Export
  if (access_is_enabled() || ($login != '__public__' && !$is_nonuser)) {
    if (
      $readonly != 'Y' &&
      access_can_access_function(ACCESS_IMPORT, $user)
    )
      $import_url = 'import.php';

    if (access_can_access_function(ACCESS_EXPORT, $user))
      $export_url = 'export.php';
  }
}
// Help
$showHelp = (access_is_enabled()
  ? access_can_access_function(ACCESS_HELP, $user)
  : ($login != '__public__' && !$is_nonuser));
// Views
$view_cnt = count($views);

$views_link = [];
if ((access_can_access_function(ACCESS_VIEW, $user) && $ALLOW_VIEW_OTHER != 'N') && $view_cnt > 0) {
  //echo "<pre>"; print_r($views); echo "</pre>"; exit;

  for ($i = 0; $i < $view_cnt; $i++) {
    $tmp = [];
    // NOTE: We use htmlspecialchars on the name below.
    $tmp['name'] = $views[$i]['cal_name'];
    $tmp['url'] = str_replace('&amp;', '&', $views[$i]['url'])
      . (empty($thisdate) ? '' : '&date=' . $thisdate);
    $views_link[$i] = $tmp;
  }
  $views_linkcnt = count($views_link);
  $tmp = '';
}
// Reports
$reports_linkcnt = 0;
if (
  !empty($REPORTS_ENABLED) && $REPORTS_ENABLED == 'Y' &&
  access_can_access_function(ACCESS_REPORT, $user)
) {
  $reports_link = [];
  $u_url = (!empty($user) && $user != $login ? '&user=' . $user : '');
  $rows = dbi_get_cached_rows(
    'SELECT cal_report_name, cal_report_id
    FROM webcal_report WHERE cal_login = ? OR ( cal_is_global = \'Y\'
    AND cal_show_in_trailer = \'Y\' ) ORDER BY cal_report_id',
    [$login]
  );
  if ($rows) {
    for ($i = 0, $cnt = count($rows); $i < $cnt; $i++) {
      $row = $rows[$i];
      $tmp = array();
      $tmp['name'] = htmlspecialchars($row[0], ENT_QUOTES);
      $tmp['url'] = 'report.php?report_id=' . $row[1] . $u_url;
      $reports_link[] = $tmp;
    }
  }
  $reports_linkcnt = count($reports_link);
  $tmp = '';
}
// Logout/Login URL
if (!$use_http_auth && $single_user != 'Y') {
  $login_url = 'login.php';

  if (empty($login_return_path))
    $logout_url = $login_url . '?';
  else {
    $login_url .= '?return_path=' . $login_return_path;
    $logout_url = $login_url . '&';
  }
  $logout_url .= 'action=logout';
  // Should we use another application's login/logout pages?
  if (substr($GLOBALS['user_inc'], 0, 9) == 'user-app-') {
    global $app_login_page, $app_logout_page;

    $login_url = 'login-app.php'
      . ($login_return_path != '' && $app_login_page['return'] != ''
        ? '?return_path=' . $login_return_path : '');
    $logout_url = $app_logout_page;
  }
}
// Manage Calendar links.
if (!empty($NONUSER_ENABLED) && $NONUSER_ENABLED == 'Y')
  $admincals = get_nonuser_cals($login);
// Make sure they have access to either month/week/day view. If they do not,
// then we cannot create a URL that shows just the boss' events. So, we would
// not include any of the "manage calendar of" links.
$have_boss_url = true;
if (!access_can_access_function(ACCESS_MONTH, $user) && !access_can_access_function(ACCESS_WEEK, $user) && !access_can_access_function(ACCESS_DAY, $user))
  $have_boss_url = false;

if ($have_boss_url && ($has_boss || !empty($admincals[0]) ||
  ($is_admin && $PUBLIC_ACCESS))) {
  $grouplist = user_get_boss_list($login);

  if (!empty($admincals[0]))
    $grouplist = array_merge($admincals, $grouplist);

  if ($is_admin && $PUBLIC_ACCESS == 'Y') {
    $public = [
      'cal_login' => '__public__',
      'cal_fullname' => translate('Public Access')
    ];
    array_unshift($grouplist, $public);
  }
  $groups = [];
  $grouplistcnt = count($grouplist);
  $gdone = [];
  for ($i = 0; $i < $grouplistcnt; $i++) {
    $l = $grouplist[$i]['cal_login'];
    $f = $grouplist[$i]['cal_fullname'];
    // Don't display current $user in group list.
    if (!empty($user) && $user == $l)
      continue;
    // Do not show duplicate entries.
    if (isset($gdone[$l]))
      continue;
    $gdone[$l] = true;
    /*
Use the preferred view if it is day/month/week/year.php. Try not to use a
user-created view because it might not display the proper user's events.
(Fallback to month.php if this is true.)  Of course, if this user cannot
view any of the standard D/M/W/Y pages, that will force us to use the view.
*/
    $xurl = get_preferred_view('', 'user=' . $l);

    if (strstr($xurl, 'view_')) {
      if (access_can_access_function(ACCESS_MONTH, $user))
        $xurl = 'month.php?user=' . $l;
      elseif (access_can_access_function(ACCESS_WEEK, $user))
        $xurl = 'week.php?user=' . $l;
      elseif (access_can_access_function(ACCESS_DAY, $user))
        $xurl = 'day.php?user=' . $l;
      // Year does not show events, so you cannot manage someone's cal.
    }
    $xurl = str_replace('&amp;', '&', $xurl);
    $tmp = array();
    $tmp['name'] = $f;
    $tmp['url'] = $xurl;
    $groups[] = $tmp;
  }
}
// Help URL
$help_url = (access_can_access_function(ACCESS_HELP, $user));

if (empty($thisyear))
  $thisyear = date('Y');
if (empty($thismonth))
  $thismonth = date('m');
if (empty($thisday))
  $thisday = date('d');

?>
<nav class="navbar navbar-expand-md navbar-light bg-light">
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="navbar-collapse collapse w-50 order-1 order-md-0 dual-collapse2" id="navbarNavDropdown">
    <ul class="navbar-nav">
      <li class="nav-item active">
        <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <nobr><?php etranslate('My Calendar'); ?></nobr>
        </a>
        <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <a class="dropdown-item" href="<?php echo $mycal; ?>"><?php etranslate('Home'); ?></a>
          <?php if (access_can_access_function(ACCESS_DAY)) { ?>
            <a class="dropdown-item" href="day.php"><?php etranslate('Today'); ?></a>
          <?php } ?>
          <?php if (access_can_access_function(ACCESS_WEEK)) { ?>
            <a class="dropdown-item" href="week.php"><?php etranslate('This Week'); ?></a>
          <?php } ?>
          <?php if (access_can_access_function(ACCESS_MONTH)) { ?>
            <a class="dropdown-item" href="month.php"><?php etranslate('This Month'); ?></a>
          <?php } ?>
          <?php if (access_can_access_function(ACCESS_YEAR)) { ?>
            <a class="dropdown-item" href="year.php"><?php etranslate('This Year'); ?></a>
          <?php } ?>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Events'); ?>
        </a>
        <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <?php if (!empty($new_entry_url)) { ?>
            <a class="dropdown-item" href="<?php echo $new_entry_url; ?>"><?php etranslate('Add New Event'); ?></a>
          <?php } ?>
          <?php if (!empty($new_task_url)) { ?>
            <a class="dropdown-item" href="<?php echo $new_task_url; ?>"><?php etranslate('Add New Task'); ?></a>
          <?php } ?>
          <?php if ($is_admin && $readonly != 'Y') { ?>
            <a class="dropdown-item" href="purge.php"><?php etranslate('Delete Entries'); ?></a>
          <?php } ?>
          <?php if (!empty($unapproved_url)) { ?>
            <a class="dropdown-item" href="<?php echo $unapproved_url; ?>"><?php etranslate('Unapproved Entries'); ?></a>
          <?php } ?>
          <?php if (!empty($export_url)) { ?>
            <a class="dropdown-item" href="<?php echo $export_url; ?>"><?php etranslate('Export'); ?></a>
          <?php } ?>
          <?php if (!empty($import_url)) { ?>
            <a class="dropdown-item" href="<?php echo $import_url; ?>"><?php etranslate('Import'); ?></a>
          <?php } ?>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Views'); ?>
        </a>
        <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <?php if (!empty($select_user_url)) { ?>
            <a class="dropdown-item" href="<?php echo $select_user_url; ?>"><?php etranslate('Another Users Calendar'); ?></a>
            <?php }
          if ($login != '__public__') {
            if (!empty($views_link) && $views_linkcnt > 0) { ?>
              <h6 class="dropdown-header"><?php etranslate('My Views'); ?></h6>
              <?php
              for ($i = 0; $i < $views_linkcnt; $i++) {
                $name = empty($views_link[$i]['name']) ? translate('Unnamed') : htmlspecialchars($views_link[$i]['name']);
              ?>
                <a class="dropdown-item" href="<?php echo $views_link[$i]['url'] ?>"><?php echo $name; ?></a>
              <?php
              }
            }

            if (!empty($groups)) { ?>
              <div class="dropdown-divider"></div>
              <h6 class="dropdown-header"><?php etranslate('Manage Calendar of'); ?></h6>
              <?php
              $groupcnt = count($groups);
              for ($i = 0; $i < $groupcnt; $i++) { ?>
                <a class="dropdown-item" href="<?php echo $groups[$i]['url'] ?>"><?php echo $groups[$i]['name'] ?></a>
            <?php
              }
            }
            ?><div class="dropdown-divider"></div><?php
                                                  if (!$is_nonuser && (!access_is_enabled() ||
                                                    access_can_access_function(ACCESS_VIEW_MANAGEMENT, $user)) && $readonly != 'Y') { ?>
              <a class="dropdown-item" href="views.php"><?php etranslate('Manage Views') ?></a>
          <?php
                                                  }
                                                }
          ?>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Reports'); ?>
        </a>
        <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <?php if ($is_admin && (!access_is_enabled() ||
            access_can_access_function(ACCESS_SECURITY_AUDIT, $user))) { ?>
            <a class="dropdown-item" href="security_audit.php"><?php etranslate('Security Audit'); ?></a>
          <?php } ?>
          <?php if ($is_admin && (!access_is_enabled() ||
            access_can_access_function(ACCESS_ACTIVITY_LOG, $user))) { ?>
            <a class="dropdown-item" href="activity_log.php"><?php etranslate('Activity Log'); ?></a>
          <?php } ?>
          <?php if ($is_admin && (!access_is_enabled() ||
            access_can_access_function(ACCESS_ACTIVITY_LOG, $user))) { ?>
            <a class="dropdown-item" href="activity_log.php?system=1"><?php etranslate('System Log'); ?></a>
          <?php } ?>
          <?php if ($REPORTS_ENABLED == 'Y') { ?>
            <div class="dropdown-divider"></div>
            <h6 class="dropdown-header"><?php etranslate('My Reports'); ?></h6>
            <?php for ($i = 0; $i < $reports_linkcnt; $i++) { ?>
              <a class="dropdown-item" href="<?php echo $reports_link[$i]['url']; ?>"><?php echo $reports_link[$i]['name']; ?></a>
            <?php } ?>
          <?php } ?>
          <?php if (
            $login != '__public__' && !$is_nonuser && $REPORTS_ENABLED == 'Y' && $readonly != 'Y' &&
            (!access_is_enabled() || access_can_access_function(ACCESS_REPORT, $user))
          ) { ?>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="report.php"><?php etranslate('Manage Reports'); ?></a>
          <?php } ?>
        </div>
      </li>

      <?php if ($login != '__public__' && !$is_nonuser && $readonly != 'Y') { ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php etranslate('Settings'); ?>
          </a>
          <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <?php
            // Normal User Settings.
            echo '<h6 class="dropdown-header">' . translate('Your Settings') . '</h6>';
            if (!$is_admin)
              print_menu_item(translate('My Profile'), 'user_mgmt.php');

            if (
              $single_user != 'Y' &&
              (!access_is_enabled() ||
                access_can_access_function(ACCESS_ASSISTANTS, $user))
            )
              print_menu_item(translate('Assistants'), 'assistant_edit.php');

            if ($CATEGORIES_ENABLED == 'Y' && (!access_is_enabled() ||
              access_can_access_function(ACCESS_CATEGORY_MANAGEMENT, $user)))
              print_menu_item(translate('Categories'), 'category.php');

            if (
              !access_is_enabled() ||
              access_can_access_function(ACCESS_LAYERS, $user)
            )
              print_menu_item(translate('Layers'), 'layers.php');

            if (
              !access_is_enabled() ||
              access_can_access_function(ACCESS_PREFERENCES, $user)
            )
              print_menu_item(translate('Preferences'), 'pref.php');

            if ($NONUSER_ENABLED == 'Y' || (access_is_enabled()
              && access_can_access_function(ACCESS_IMPORT))) {
              print_menu_item(translate('Resource Calendars'), 'resourcecal_mgmt.php');
            }
            if ($REMOTES_ENABLED == 'Y' && (!access_is_enabled() ||
              access_can_access_function(ACCESS_IMPORT))) {
              print_menu_item(translate('Remote Calendars'), 'remotecal_mgmt.php');
            }

            // Admin-only settings
            if (($is_admin && !access_is_enabled()) || (access_is_enabled() &&
              access_can_access_function(ACCESS_SYSTEM_SETTINGS, $user))) {
              echo '<div class="dropdown-divider"></div>';
              echo '<h6 class="dropdown-header">' . translate('Admin Settings') . '</h6>';
              print_menu_item(translate('System Settings'), 'admin.php');
              if (access_is_enabled() &&
                access_can_access_function(ACCESS_SYSTEM_SETTINGS, $user))
                print_menu_item(translate('User Access Control'), 'access.php');
              print_menu_item(translate('Users'), 'user_mgmt.php');
              if (!empty($GROUPS_ENABLED) && $GROUPS_ENABLED == 'Y') {
                print_menu_item(translate('Groups'), 'groups.php');
              }
            }

            // Nonuser Admin Settings
            if ($is_nonuser_admin) {
              echo '<div class="dropdown-divider"></div>';
              echo '<h6 class="dropdown-header">' . translate('Settings for') . ' ' . $user_fullname . '</h6>';
              if ($single_user != 'Y' && $readonly != 'Y') {
                if (
                  !access_is_enabled() ||
                  access_can_access_function(ACCESS_ASSISTANTS, $user)
                )
                  print_menu_item(translate('Assistants'), 'assistant_edit.php?user=' . $user);
              }
              if (
                !access_is_enabled() ||
                access_can_access_function(ACCESS_PREFERENCES, $user)
              )
                print_menu_item(translate('Preferences'), 'pref.php?user=' . $user);
            }

            if ($is_admin && !empty($PUBLIC_ACCESS) && $PUBLIC_ACCESS == 'Y') {
            ?><div class="dropdown-divider"></div>
              <h6 class="dropdown-header"><?php etranslate('Public Calendar'); ?></h6><?php
                                                                                      print_menu_item(translate('Preferences'), 'pref.php?public=1');

                                                                                      if ($PUBLIC_ACCESS_CAN_ADD == 'Y' && $PUBLIC_ACCESS_ADD_NEEDS_APPROVAL == 'Y')
                                                                                        print_menu_item(translate('Unapproved Events'), 'list_unapproved.php?user=__public__');
                                                                                    }
                                                                                      ?>
          </div>
        </li>
      <?php } ?>

      <?php if ($search_url != '' && ($login != '__public__' || $OVERRIDE_PUBLIC != 'Y')) { ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php etranslate('Search'); ?>
          </a>
          <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <?php
            echo '<a class="form-check"><form class="form-inline" action="search_handler.php' . (!empty($user) ? '?users[]=' . $user : '') . '" ' .
              'method="GET"><div class="input-group"><input class="form-control" type="text" name="keywords" size="25" />' .
              '<button class="btn btn-primary mr-2 pr-0 pl-2"><img class="button-icon" src="images/bootstrap-icons/search.svg" /></button></div></form></a>';
            $doAdv = false;
            // Use UAC if enabled...
            if (access_is_enabled() && access_can_access_function(ACCESS_ADVANCED_SEARCH)) {
              $doAdv = true;
            } else if (!access_is_enabled() && !$is_nonuser && $login != '__public__') {
              $doAdv = true;
            }
            if ($doAdv) {
            ?><div class="dropdown-divider"></div><?php
                                                  print_menu_item(translate('Advanced Search'), 'search.php?adv=1');
                                                }
                                                  ?>
          </div>
        </li>
      <?php } ?>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Help'); ?>
        </a>
        <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <a class="dropdown-item" href="#" onclick="javascript:openHelp()"><?php etranslate('Help Contents'); ?></a>
          <a class="dropdown-item" href="#" onclick="javascript:openAbout()"><?php etranslate('About WebCalendar'); ?></a>
        </div>
      </li>

      <?php
      // Unapproved Icon if any exist.
      $unapprovedStr = display_unapproved_events($is_assistant || $is_nonuser_admin ? $user : $login);
      if (!empty($unapprovedStr) && $unapproved_url != '') { ?>
        <li class="nav-item active">
          <a class="nav-link" href="<?php echo $unapproved_url; ?>"><?php etranslate('Unapproved Events'); ?></a>
        </li>
      <?php } ?>

      <?php if ($show_printer) { ?>
        <li class="nav-item active">
          <a class="nav-link" href="<?php echo generate_printer_friendly(); ?>" target="cal_printer_friendly" class="btn btn-primary mr-2 pr-0 pl-2">
            <img class="button-icon-inverse" src="images/bootstrap-icons/printer.svg" /></a>
        </li>
      <?php } ?>

    </ul>
  </div>

  <div class="mx-auto order-0 w-30">
    <ul class="navbar-nav mxr-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Month'); ?>
        </a>
        <ul class="dropdown-menu" aria-labelledby="navbarDropdownLink">
          <?php
          /** I really like the submenus that allow us to add more years in here, but it does not display
           *  the submenu correctly, so I am commenting it out for now... :-(
          <!-- All 12 months for next 2 years in submenu -->
          <li class="dropdown-submenu">
            <a class="dropdown-item dropdown-toggle" href="#"><?php echo ($thisyear+2);?></a>
            <ul class="dropdown-menu">
            <h6 class="dropdown-header"><?php echo ($thisyear + 2); ?></h6>
            <?php
              for ( $i = 1; $i <= 12; $i++ ) {
                $date = sprintf ("%04d%02d01", $thisyear + 2, $i);
                $name = month_name($i - 1);
                print_month_menu_item($name, $date);
              }
            ?>
            </ul>
          </li>
          <!-- next year -->
          <li class="dropdown-submenu">
              <a class="dropdown-item dropdown-toggle" href="#"><?php echo ($thisyear+1);?></a>
              <ul class="dropdown-menu">
                <h6 class="dropdown-header"><?php echo ($thisyear + 1); ?></h6>
                <?php
                for ( $i = 1; $i <= 12; $i++ ) {
                  $date = sprintf ("%04d%02d01", $thisyear + 1, $i);
                  $name = month_name($i - 1);
                  print_month_menu_item($name, $date);
                }
              ?>
            </ul>
          </li>
           **/ ?>
          <!-- 3 months of prior year -->
          <h6 class="dropdown-header"><?php echo ($thisyear - 1); ?></h6>
          <?php
          for ($i = 9; $i <= 12; $i++) {
            $date = sprintf("%04d%02d01", $thisyear - 1, $i);
            $name = month_name($i - 1);
            print_month_menu_item($name, $date);
          } ?>
          <!-- this year -->
          <div class="dropdown-divider"></div>
          <h6 class="dropdown-header"><?php echo $thisyear; ?></h6>
          <?php for ($i = 1; $i <= 12; $i++) {
            $date = sprintf("%04d%02d01", $thisyear, $i);
            $name = month_name($i - 1);
            if ($i == $thismonth)
              $name = '<b>' . $name . '</b>';
            print_month_menu_item($name, $date);
          } ?>
          <!-- 3 months next year -->
          <div class="dropdown-divider"></div>
          <h6 class="dropdown-header"><?php echo ($thisyear + 1); ?></h6>
          <?php for ($i = 1; $i <= 3; $i++) {
            $date = sprintf("%04d%02d01", $thisyear + 1, $i);
            $name = month_name($i - 1);
            print_month_menu_item($name, $date);
          } ?>
          <?php
          /* Commenting out submenu for now... :-(
        <!-- year before -->
        <div class="dropdown-divider"></div>
        <li class="dropdown-submenu">
          <a class="dropdown-item dropdown-toggle" href="#"><?php echo ($thisyear-1);?></a>
          <ul class="dropdown-menu">
            <h6 class="dropdown-header"><?php echo ($thisyear - 1); ?></h6>
            <?php
              for ( $i = 1; $i <= 12; $i++ ) {
                $date = sprintf ("%04d%02d01", $thisyear - 1, $i);
                $name = month_name($i - 1);
                print_month_menu_item($name, $date);
              }
            ?>
          </ul>
        </li> 
        <!-- 2 years before -->
        <li class="dropdown-submenu">
              <a class="dropdown-item dropdown-toggle" href="#"><?php echo ($thisyear-2);?></a>
              <ul class="dropdown-menu">
                <h6 class="dropdown-header"><?php echo ($thisyear - 2); ?></h6>
                <?php
                for ( $i = 1; $i <= 12; $i++ ) {
                  $date = sprintf ("%04d%02d01", $thisyear - 2, $i);
                  $name = month_name($i - 1, 'M');
                  print_month_menu_item($name, $date);
                }
              ?>
            </ul>
          </li> 
        */ ?>
        </ul>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Week'); ?>
        </a>
        <ul class="dropdown-menu" aria-labelledby="navbarDropdownLink">
          <!-- 6 weeks prior and 8 weeks after -->
          <?php
          $d = (empty($thisday) ? date('d') : $thisday);
          $m = (empty($thismonth) ? date('m') : $thismonth);
          $y = (empty($thisyear) ? date('Y') : $thisyear);
          $lastDay = ($DISPLAY_WEEKENDS == 'N' ? 4 : 6);
          $thisdate = date('Ymd', mktime(0, 0, 0, $m, $d, $y));
          $thisweek = date('W', mktime(0, 0, 0, $m, $d, $y));
          $wkstart = get_weekday_before($y, $m, $d);
          $y = (empty($thisyear) ? date('Y') : $thisyear);
          for ($i = -5; $i <= 9; $i++) {
            $twkstart = bump_local_timestamp($wkstart, 0, 0, 0, 0, 7 * $i, 0);
            $twkend = bump_local_timestamp($twkstart, 0, 0, 0, 0, $lastDay, 0);
            $dateSYmd = date('Ymd', $twkstart);
            $dateEYmd = date('Ymd', $twkend);
            $dateW = date('W', $twkstart + 86400);
            if ($twkstart > 0 && $twkend < 2146021200) {
              $name = (!empty($GLOBALS['PULLDOWN_WEEKNUMBER'])
                && $GLOBALS['PULLDOWN_WEEKNUMBER'] == 'Y'
                ? '(' . $dateW . ')&nbsp;&nbsp;' : '') .
                sprintf(
                  '%s - %s',
                  date_to_str($dateSYmd, '__mon__ __dd__', false, true),
                  date_to_str($dateEYmd, '__mon__ __dd__', false, true)
                );
              if ($thisdate >= $dateSYmd && $thisdate <= $dateEYmd)
                $name = '<b>' . $name . '</b>';
              print_week_menu_item($name, $dateSYmd);
            }
          }
          ?>
        </ul>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php etranslate('Year'); ?>
        </a>
        <div id="nav-project-menu" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <!-- 5 years before, 5 years after -->
          <?php for ($i = -5; $i <= 5; $i++) {
            $date = sprintf("%04d%02d01", $thisyear + $i, 1, 1);
            $name = ($thisyear + $i);
            if ($i == 0)
              $name = '<b>' . $name . '</b>';
            print_year_menu_item($name, $date);
          } ?>
        </div>
      </li>

    </ul>
  </div>

  <?php if (!$use_http_auth && $single_user != 'Y') { ?>
    <div class="navbar-collapse collapse w-20 order-3 dual-collapse2">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown-menu-right">
          <a class="nav-link" href="<?php echo $logout_url; ?>">Logout</a>
        </li>
      </ul>
    </div>
  <?php } ?>

  </div>
</nav>

<?php

function print_year_menu_item($name, $date)
{
  global $user, $login;
  echo '<a class="dropdown-item" href="year.php?date=' . $date .
    ((empty($user) || $user != $login) ? "&user=$user" : "") . '">' . $name . "</a>\n";
}

function print_month_menu_item($name, $date)
{
  global $user, $login;
  echo '<li><a class="dropdown-item" href="month.php?date=' . $date . ((empty($user) || $user != $login) ? "&user=$user" : "") . '">' . $name . "</a></li>\n";
}

function print_week_menu_item($name, $date)
{
  global $user, $login;
  echo '<li><a class="dropdown-item" href="week.php?date=' . $date . ((empty($user) || $user != $login) ? "&user=$user" : "") . '">' . $name . "</a></li>\n";
}

function print_menu_item($name, $url, $testCondition = true, $target = '')
{
  if ($testCondition) {
    echo '<a class="dropdown-item" href="' . $url . '"';
    if (!empty($target)) {
      echo ' target="' . $target . '"';
    }
    echo '>' . $name . '</a>' . "\n";
  }
}
?>
