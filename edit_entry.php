<?php
/**
 * Description:
 * Presents page to edit/add an event/task/journal
 *
 * Notes:
 * A SysAdmin can enable HTML for event full descriptions. If one of the
 * supported HTML edit widgets is also installed, users can use WYSIWYG editing.
 * See the WebCalendar page at
 * http://www.k5n.us/webcalendar.php?topic=Add-Ons
 * for download and install instructions for these packages.
 */
include_once 'includes/init.php';

/**
 * Generate HTML for a time selection for use in a form.
 *
 * @param string  $prefix   Prefix to use in front of form element names
 * @param string  $time     Currently selected time in HHMMSS
 * @param bool    $trigger  Add onchange event trigger that
 *                          calls javascript function $prefix_timechanged()
 *
 * @return string HTML for the selection box
 */
function time_selection($prefix, $time = '', $trigger = false)
{
  global $checked, $ENTRY_SLOTS, $selected, $TIME_FORMAT, $WORK_DAY_START_HOUR;

  $amsel = $pmsel = $ret = '';
  $trigger_str = ($trigger ? 'onchange="' . $prefix . 'timechanged() ' : '');

  if (!isset($time) && $time != 0) {
    $hour = $WORK_DAY_START_HOUR;
    $minute = 0;
  } else {
    $hour = floor($time / 10000);
    $minute = (($time / 100) % 100) % 60;
  }
  if ($TIME_FORMAT == '12') {
    $maxhour = 12;
    if ($hour < 12 || $hour == 24)
      $amsel = $checked;
    else
      $pmsel = $checked;

    $hour %= 12;
    if ($hour == 0)
      $hour = 12;
  } else {
    $maxhour = 24;
    $hour = sprintf("%02d", $hour);
  }
  $minute = sprintf("%02d", $minute);
  $ret .= '<select class="form-control col-auto" ' . 'name="' . $prefix . 'hour" id="' . $prefix . 'hour" '
    . $trigger_str . '>';
  for ($i = 0; $i < $maxhour; $i++) {
    $ihour = ($TIME_FORMAT == '24' ? sprintf("%02d", $i) : $i);
    if ($i == 0 && $TIME_FORMAT == '12')
      $ihour = 12;

    $ret .= '<option value="' . "$i\"" . ($ihour == $hour ? $selected : '')
      . ">$ihour" . '</option>';
  }
  $ret .= '</select>:
            <select class="form-control col-auto" ' . 'name="' . $prefix . 'minute" id="' . $prefix
    . 'minute" ' . $trigger_str . '>';
  // We use $TIME_SLOTS to populate the minutes pulldown.
  $found = false;
  for ($i = 0; $i < 60;) {
    $imin = sprintf("%02d", $i);
    $isselected = '';
    if ($imin == $minute) {
      $found = true;
      $isselected = $selected;
    }
    $ret .= '
              <option value="' . "$i\"$isselected>$imin" . '</option>';
    $i += (1440 / $ENTRY_SLOTS);
  }
  // We'll add an option with the exact time if not found above.
  return $ret . ($found ? '' : '<option value="' . "$minute\" $selected>$minute" . '</option>') .
    '</select>' . ($TIME_FORMAT == '12' ?
      '<label><input class="form-control" type="radio" name="' . $prefix . 'ampm" id="'
      . $prefix . 'ampmA" value="0" ' . $amsel . ' />&nbsp;' . translate('am')
      . '&nbsp;</label><label><input class="form-control" type="radio" name="' . $prefix . 'ampm" id="'
      . $prefix . 'ampmP" value="12" ' . $pmsel . ' />&nbsp;' . translate('pm')
      . '</label>&nbsp;' : '<input type="hidden" name="' . $prefix . 'ampm" value="0" />');
}

$daysStr = translate('days');
$hoursStr = translate('hours');
$minutStr = translate('minutes');
$saveStr = translate('Save');

load_user_categories();

// Make sure this is not a read-only calendar.
$can_edit = false;
$others_complete = 'yes';
$checked = ' checked="checked"';
$selected = ' selected="selected"';

$eType = getGetValue('eType','event',true);
$id    = getGetValue('id');

$copy  = getValue('copy', '[01]');
$date  = getValue('date', '-?[0-9]+');
$day   = getValue('day', '-?[0-9]+');
$month = getValue('month', '-?[0-9]+');
$year  = getValue('year', '-?[0-9]+');
$name  = getValue('name');
$description  = getValue('desc');

// Public access can only add events, not edit.
if (empty($login) || ($login == '__public__' && $id > 0))
  $id = 0;

if (!in_array($eType, ['event', 'task', 'journal']))
  $eType = 'event';

if (empty($date) && empty($month)) {
  if (empty($year))
    $year = date('Y');

  $month = date('m');

  if (empty($day))
    $day = date('d');

  $date = sprintf("%04d%02d%02d", $year, $month, $day);
}

$BodyX = 'onload="onLoad();"';
$INC = array('js/translate.js.php', 'js/edit_entry.php/false/' . $user, 'js/visible.php');
$textareasize = ($ALLOW_HTML_DESCRIPTION === 'Y' ? '20' : '15');

// Add Modal Dialog javascript/CSS
$HEAD = '';

$byday = $bymonth = $bymonthday = $bysetpos = $participants =
  $exceptions = $inclusions = $reminder = [];
$byweekno = $byyearday = $catList = $catNames = $external_users = $rpt_count = '';

$create_by = $login;

// This is the default per RFC2445.
// We could override it and use $byday_names[$WEEK_START'].
$wkst = 'MO';

$real_user = ((!empty($user) && strlen($user)) &&
  ($is_assistant || $is_admin)) ? $user : $login;

print_header($INC, $HEAD, $BodyX);

if ($readonly == 'Y' || $is_nonuser)
  $can_edit = false;
else if (!empty($id) && $id > 0) {
  // First see who has access to edit this entry.
  if ($is_admin)
    $can_edit = true;

  $res = dbi_execute('SELECT cal_create_by, cal_date, cal_time, cal_mod_date, ' .
    'cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, cal_name, ' .
    'cal_description, cal_group_id, cal_location, cal_due_date, cal_due_time, ' .
    'cal_completed, cal_url FROM webcal_entry ' .
    'WHERE cal_id = ?', [$id]);
  if ($res) {
    $row = dbi_fetch_row($res);
    // If current user is creator of event, then they can edit.
    if ($row[0] == $login)
      $can_edit = true;

    $cal_date = (!empty($override) && !empty($date)
      ? $date // Leave $cal_date to what was set in URL with date=YYYYMMDD.
      : $row[1]);

    $create_by = $row[0];
    if (($user == $create_by) && ($is_assistant || $is_nonuser_admin))
      $can_edit = true;

    $cal_time = sprintf("%06d", $row[2]);
    $due_date = $row[13];
    $due_time = $row[14];

    $calTS = date_to_epoch($cal_date . $cal_time);
    // Don't adjust for All Day entries.
    if ($cal_time > 0 || ($cal_time == 0 && $row[5] != 1440)) {
      $cal_date = date('Ymd', $calTS);
      $cal_time = date('His', $calTS);
    }
    $hour = floor($cal_time / 10000);
    $minute = ($cal_time / 100) % 100;

    $dueTS = date_to_epoch($due_date . $due_time);
    $due_date = date('Ymd', $dueTS);
    $due_time = date('His', $dueTS);
    $due_hour = floor($due_time / 10000);
    $due_minute = ($due_time / 100) % 100;

    $priority = $row[6];
    $type = $row[7];
    $access = $row[8];
    $name = $row[9];
    $description = $row[10];
    $parent = $row[11];
    $location = $row[12];
    $completed = (empty($row[15]) ? date('Ymd') : $row[15]);
    $cal_url = $row[16];

    // What kind of entry are we dealing with?
    if (strpos('EM', $type) !== false)
      $eType = 'event';
    elseif (strpos('JO', $type) !== false)
      $eType = 'journal';
    elseif (strpos('NT', $type) !== false)
      $eType = 'task';

    // Public access has no access to tasks.
    if ($login == '__public__' && $eType == 'task')
      etranslate('You are not authorized to edit this task.');

    // Check UAC.
    if (access_is_enabled())
      $can_edit =
        access_user_calendar('edit', $create_by, $login, $type, $access);

    $day = $cal_date % 100;
    $month = ($cal_date / 100) % 100;
    $year = intval($cal_date / 10000);

    $time = $row[2];

    if ($time >= 0)
      $duration = $row[5];
    else {
      $duration = '';
      $hour = -1;
    }

    // Check for repeating event info...
    // but not if we're overriding a single entry of an already repeating event...
    // confusing, eh?
    if (!empty($override)) {
      $rpt_end = 0;
      $rpt_end_date = $cal_date;
      $rpt_freq = 1;
      $rpt_type = 'none';
    } else {
      $res = dbi_execute('SELECT cal_id, cal_type, cal_end, cal_endtime, ' .
        'cal_frequency, cal_byday, cal_bymonth, cal_bymonthday, cal_bysetpos, ' .
        'cal_byweekno, cal_byyearday, cal_wkst, cal_count ' .
        'FROM webcal_entry_repeats WHERE cal_id = ?', [$id]);
      if ($res) {
        if ($row = dbi_fetch_row($res)) {
          $rpt_type = $row[1];
          $rpt_end = ($row[2] > 0 ? date_to_epoch($row[2] . $row[3]) : 0);

          if (empty($row[2])) {
            $rpt_end_date = $cal_date;
            $rpt_end_time = $cal_time;
          } else {
            $rpt_endTS = date_to_epoch($row[2] . $row[3]);
            $rpt_end_date = date('Ymd', $rpt_endTS);
            $rpt_end_time = date('His', $rpt_endTS);
          }
          $rpt_freq = $row[4];
          if (!empty($row[5]))
            $byday = explode(',', $row[5]);

          $bydayStr = $row[5];
          if (!empty($row[6]))
            $bymonth = explode(',', $row[6]);

          if (!empty($row[7]))
            $bymonthday = explode(',', $row[7]);

          $bymonthdayStr = $row[7];
          if (!empty($row[8]))
            $bysetpos = explode(',', $row[8]);

          $bysetposStr = $row[8];
          $byweekno = $row[9];
          $byyearday = $row[10];
          $wkst = $row[11];
          $rpt_count = $row[12];

          // Check to see if Weekends Only is applicable.
          $weekdays_only = ($rpt_type == 'daily' && $byday == 'MO,TU,WE,TH,FR');
        }
        dbi_free_result($res);
      }
    }

    $res = dbi_execute('SELECT cal_login, cal_percent, cal_status ' .
      'FROM webcal_entry_user WHERE cal_id = ?', [$id]);
    if ($res) {
      while ($row = dbi_fetch_row($res)) {
        $overall_percent[] = $row;
        if ($login == $row[0] || ($is_admin && $user == $row[0])) {
          $task_percent = $row[1];
          $task_status = $row[2];
        }
      }
      dbi_free_result($res);
    }

    // Determine if Expert mode needs to be set.
    $expert_mode = (count($byday) || count($bymonth) ||
      count($bymonthday) || count($bysetpos) ||
      isset($byweekno) || isset($byyearday) || isset($rpt_count));

    // Get Repeat Exceptions.
    $res = dbi_execute('SELECT cal_date, cal_exdate ' .
      'FROM webcal_entry_repeats_not WHERE cal_id = ?', [$id]);
    if ($res) {
      while ($row = dbi_fetch_row($res)) {
        if ($row[1] == 1)
          $exceptions[] = $row[0];
        else
          $inclusions[] = $row[0];
      }
      dbi_free_result($res);
    }
  }
  if ($CATEGORIES_ENABLED == 'Y') {
    $catById = get_categories_by_id($id, $real_user, true);
    if (!empty($catById)) {
      $catNames = implode(', ', $catById);
      $catList = implode(',', array_keys($catById));
    }
  } //end CATEGORIES_ENABLED test

  // Get reminders.
  $reminder = getReminders($id);
  $reminder_offset = (empty($reminder) ? 0 : $reminder['offset']);

  $rem_status = (count($reminder));
  $rem_use_date = (!empty($reminder['date']));

  // Get participants.
  $res = dbi_execute("SELECT cal_login, cal_status FROM webcal_entry_user WHERE cal_id = ? " .
    "AND cal_status IN ( 'A', 'W' )", [$id]);
  if ($res) {
    while ($row = dbi_fetch_row($res)) {
      $participants[$row[0]] = 1;
      $selectedStatus[$row[0]] = $row[1];
    }
    dbi_free_result($res);
  }
  // Not allowed for tasks or journals.
  if ($eType == 'event' && !empty($ALLOW_EXTERNAL_USERS) && $ALLOW_EXTERNAL_USERS == 'Y')
    $external_users = event_get_external_users($id);
} else {
  // ##########   New entry   ################
  $id = 0; // To avoid warnings below about use of undefined var.

  // We'll use $WORK_DAY_START_HOUR and $WORK_DAY_END_HOUR
  // as our starting and due times.
  $cal_time = $WORK_DAY_START_HOUR . '0000';
  $completed = '';
  $due_hour = $WORK_DAY_END_HOUR;
  $due_minute = $task_percent = 0;
  $due_time = $WORK_DAY_END_HOUR . '0000';
  $overall_percent = [];

  // Get category if passed in URL as cat_id.
  $cat_id = getValue('cat_id', '-?[0-9,\-]*', true);
  if (!empty($cat_id)) {
    $res = dbi_execute(
      'SELECT cat_name FROM webcal_categories ' .
        'WHERE cat_id = ? AND ( cat_owner = ? OR cat_owner IS NULL )',
      [$cat_id, $real_user]
    );
    if ($res) {
      $row = dbi_fetch_row($res);
      $catNames = $row[0];
      $catList = $cat_id;
    }
  }

  // Reminder settings.
  $reminder_offset = ($REMINDER_WITH_DATE == 'N' ? $REMINDER_OFFSET : 0);

  $rem_status = ($REMINDER_DEFAULT == 'Y');
  $rem_use_date = ($reminder_offset == 0 && $REMINDER_WITH_DATE == 'Y');

  if ($eType == 'task')
    $hour = $WORK_DAY_START_HOUR;

  // Anything other then testing for strlen breaks either hour=0 or no hour in URL.
  if (strlen($hour))
    $time = $hour * 100;
  else
    $hour = $time = -1;

  $defusers = getGetValue('defusers');
  $defusers_ar = array();
  if (!empty($defusers)) {
    $defusers_ar = explode(',', $defusers);
    for ($i = 0, $cnt = count($defusers_ar); $i < $cnt; $i++) {
      $participants[$defusers_ar[$i]] = 1;
    }
  }

  //Add the logged in user if none other supplied
  if (empty($participants))
    $participants[$login] = 1;

  if ($readonly == 'N') {
    // Is public allowed to add events?
    if ($login == '__public__' && $PUBLIC_ACCESS_CAN_ADD != 'Y')
      $can_edit = false;
    else
      $can_edit = true;
  }
}
$dateYmd = date('Ymd');
$thisday = $day;
$thismonth = $month;
$thisyear = $year;
if (empty($rpt_type) || !$rpt_type)
  $rpt_type = 'none';

// Avoid error for using undefined vars.
if (!isset($hour) || !is_numeric($hour))
  $hour = 0;
if (!isset($minute) || !is_numeric($minute))
  $minute = 0;
else if (isset($hour) && is_numeric($hour) && $hour >= 0) {
  $cal_time = ($hour * 10000) + ($minute * 100);
}
$cal_time = ($hour * 10000) + (isset($minute) ? $minute * 100 : 0);

if (empty($access))
  $access = '';

if (empty($cal_url))
  $cal_url = '';

if (empty($description) || $description == '<br />')
  $description = '';

if (empty($duration))
  $duration = 0;

if ($duration == 1440 && $time == 0) {
  $duration = $hour = $minute = '';
  $allday = 'Y';
} else
  $allday = 'N';

if (empty($location))
  $location = '';

if (empty($name))
  $name = '';

if (empty($priority))
  $priority = 5;

if (empty($rpt_end_date))
  $rpt_end_date = 0;

if (empty($rpt_end_time))
  $rpt_end_time = 0;

if (empty($rpt_freq))
  $rpt_freq = 0;

if (empty($cal_date)) {
  $cal_date = (!empty($date) && $eType != 'task' ? $date : $dateYmd);

  if (empty($due_date))
    $due_date = $dateYmd;
}
if (empty($thisyear))
  $thisdate = $dateYmd;
else {
  $thisdate = sprintf(
    "%04d%02d%02d",
    $thisyear,
    empty($thismonth) ? date('m') : $thismonth,
    empty($thisday) ? date('d') : $thisday
  );
}

if (empty($cal_date) || !$cal_date)
  $cal_date = $thisdate;

if (empty($due_date) || !$due_date)
  $due_date = $thisdate;

// Setup to display user's timezone difference if Admin or Assistant.
// Even though event is stored in GMT,
// an Assistant may need to know that the boss is in a different Timezone.
if ($is_assistant || $is_admin && !empty($user)) {
  $tz_offset = date('Z', date_to_epoch($cal_date . $cal_time));
  $user_TIMEZONE = get_pref_setting($user, 'TIMEZONE');
  set_env('TZ', $user_TIMEZONE);
  $user_tz_offset = date('Z', date_to_epoch($cal_date . $cal_time));
  if ($tz_offset != $user_tz_offset) { // Different TZ_Offset.
    user_load_variables($user, 'temp');
    $tz_diff = ($user_tz_offset - $tz_offset) / 3600;
    $abs_diff = abs($tz_diff);
    // translate ( 'is in a different timezone than you are. Currently' )
    // translate ( 'hour ahead of you' ) translate ( 'hour behind you' )
    // translate ( 'hours ahead of you' ) translate ( 'hours behind you' )
    // translate ( 'XXX is in a different timezone (ahead)' )
    // translate ( 'XXX is in a different timezone (behind)' )
    // Line breaks in translates below are to bypass update_translation.pl.
    $TZ_notice = str_replace(
      'XXX',
      [
        $tempfullname,
        // TODO show hh:mm instead of abs.
        $abs_diff . ' ' . translate('hour'
          . ($abs_diff == 1 ? '' : 's')),
        translate('Time entered here is based on your Timezone.')
      ],
      translate('XXX is in a different timezone ('
        . ($tz_diff > 0 ? 'ahead)' : 'behind)'))
    );
  }
  // Return to $login TIMEZONE.
  set_env('TZ', $TIMEZONE);
}

$eType_label = ' ( ' . translate($eType) . ' )';

?>
<h2><?php echo ($id ? translate('Edit Entry') : translate('Add Entry')) . $eType_label; ?>
  &nbsp;<img src="images/bootstrap-icons/question-circle-fill.svg" alt="<?php etranslate('Help'); ?>" class="help" onclick="window.open('help_edit_entry.php<?php echo empty($id) ? '?add=1' : ''; ?>',
  'cal_help', 'dependent,menubar,scrollbars,height=400,width=400,innerHeight=420,outerWidth=420');" /></h2>

<?php

if (!$can_edit) {
  // User is not authorized
  etranslate('You are not authorized to edit this entry.');
  echo print_trailer();
  exit;
}

$tabs_name = ['details'];
$tabs_title = [translate('Details')];
if ($DISABLE_PARTICIPANTS_FIELD != 'Y' || $is_admin) {
  $tabs_name[] = 'participants';
  $tabs_title[] = translate('Participants');
}
if ($DISABLE_REPEATING_FIELD != 'Y') {
  $tabs_name[] = 'pete';
  $tabs_title[] = translate('Repeat');
}
if ($DISABLE_REMINDER_FIELD != 'Y') {
  $tabs_name[] = 'reminder';
  $tabs_title[] = translate('Reminders');
}

$tabs = '<ul class="nav nav-tabs">';
for ($i = 0, $cnt = count($tabs_name); $i < $cnt; $i++) {
  $tabs .= '<li class="nav-item"><a class="nav-link ' .
    ($i == 0 ? ' active' : '') . '" data-toggle="tab" href="#' . $tabs_name[$i] . '">' . $tabs_title[$i] . '</a></li>';
}
$tabs .= "</ul>\n";
$tabI = 0;

?>
<form action="edit_entry_handler.php" method="post" name="editentryform" id="editentryform">
  <?php print_form_key(); ?>
  <input type="hidden" name="eType" value="<?php echo $eType; ?>" />
  <?php if (!empty($id) && (empty($copy) || $copy != '1')) { ?>
    <input type="hidden" name="cal_id" value="<?php echo $id; ?>" />
  <?php } ?>
  <input type="hidden" name="entry_changed" value="" />
  <!-- Are we overriding an entry from a repeating event... -->
  <?php if (!empty($override)) { ?>
    <input type="hidden" name="override" value="1" />
    <input type="hidden" name="override_date" value="<?php echo $cal_date; ?>" />
  <?php } ?>
  <!-- // If assistant, need to remember boss = user. -->
  <?php if ($is_assistant || $is_nonuser_admin || !empty($user)) { ?>
    <input type="hidden" name="user" value="<?php echo $user; ?>" />
  <?php } ?>
  <!-- If has cal_group_id was set, need to set parent = $parent -->
  <?php if (!empty($parent)) { ?>
    <input type="hidden" name="parent" value="<?php echo $parent; ?>" />
  <?php } ?>



  <!-- TABS -->
  <?php echo $tabs; ?>

  <!-- TABS BODY -->
  <div class="tab-content mb-12">
    <!-- SETTINGS -->
    <div class="tab-pane container active" id="<?php echo $tabs_name[$tabI++]; ?>">
      <div class="form-group">

        <label for="entry_brief" data-toggle="tooltip" data-placement="top" title="<?php etooltip('brief-description-help'); ?>">
          <?php etranslate('Brief Description'); ?>:</label>
        <input class="form-control" type="text" name="name" id="entry_brief" size="25" value="<?php echo htmlspecialchars($name); ?>" />
        <label for="description" data-toggle="tooltip" data-placement="top" title="<?php etooltip('full-description-help'); ?>">
          <?php etranslate('Full Description'); ?>:</label>
        <textarea class="form-control" rows="5" name="description" id="description"><?php echo htmlspecialchars($description); ?></textarea>

        <?php if ($DISABLE_ACCESS_FIELD != 'Y') { ?>
          <label for="access" data-toggle="tooltip" data-placement="top" data-html="true" title="<?php etooltip('access-help', '', true); ?>">
            <?php etranslate('Access'); ?>:</label>
          <select class="form-control" name="access" id="entry_access" value="<?php echo htmlspecialchars($name); ?>">
            <option value="P" <?php echo ($access == 'P' || !strlen($access) ? $selected : ''); ?>>
              <?php etranslate('Public'); ?></option>
            <option value="R" <?php echo ($access == 'R' || !strlen($access) ? $selected : ''); ?>>
              <?php etranslate('Private'); ?></option>
            <option value="C" <?php echo ($access == 'C' || !strlen($access) ? $selected : ''); ?>>
              <?php etranslate('Confidential'); ?></option>
          </select>
        <?php } ?>

        <?php if ($DISABLE_PRIORITY_FIELD != 'Y') { ?>
          <label for="priority" data-toggle="tooltip" data-placement="top" title="<?php etooltip('priority-help'); ?>">
            <?php etranslate('Priority'); ?> :</label>
          <select class="form-control" name="priority" id="entry_prio">
            <?php
            $pri = [
              '',
              translate('High'),
              translate('Medium'),
              translate('Low')
            ];
            for ($i = 1; $i <= 9; $i++) {
              echo '<option value="' . $i . '"'
                . ($priority == $i ? $selected : '')
                . '>' . $i . '-' . $pri[ceil($i / 3)] . '</option>';
            }
            ?>
          </select>
        <?php } ?>

        <?php if (!empty($categories) && $CATEGORIES_ENABLED == 'Y') { ?>
          <!-- CATEGORIES -->
          <label for="category" data-toggle="tooltip" data-placement="top" title="<?php etooltip('category-help'); ?>">
            <?php etranslate('Category'); ?>:</label>
          <div>
            <!-- <button class="btn" type="button" onclick="editCats( event )" /><?php etranslate('Edit');?></button> -->
            <input class="form-control" type="text" readonly name="catnames" id="entry_categories" onclick="editCats(event)" style="cursor: pointer;" value="<?php echo $catNames . (empty($catNames) ? 'None' : ''); ?>" />
            <input class="form-control" type="hidden" id="cat_id" name="cat_id" value="<?php echo $catList;?>" />
          </div>
        <?php } ?>

        <?php if ($eType == 'task') { // Only for tasks.
          $completed_visible = (strlen($completed) ? 'visible' : 'hidden'); ?>
          <br />
          <table>
            <tr id="completed">
              <td class="tooltip" title="<?php etooltip('completed-help'); ?>">
                <label for="task_percent"><?php etranslate('Date Completed'); ?>:&nbsp;</label>
              </td>
              <td><?php echo date_selection('completed_', $completed); ?></td>
            </tr>
            <tr>
              <td class="tooltip" title="<?php etooltip('percent-help'); ?>">
                <label for="task_percent"><?php etranslate('Percent Complete'); ?>:&nbsp;</label>
              </td>
              <td>
                <select name="percent" id="task_percent" onchange="completed_handler()">
                  <?php
                  for ($i = 0; $i < 101; $i += 10) {
                    echo '<option value="' . "$i\" " . ($task_percent == $i ? $selected : '')
                      . '>' . $i . '</option>';
                  }
                  ?>
                </select>
              </td>
            </tr>
            <?php if (!empty($overall_percent)) { ?>
              <tr>
                <td colspan="2">
                  <table cellpadding="2" cellspacing="5">
                    <tr>
                      <td colspan="2"><?php etranslate('All Percentages'); ?></td>
                    </tr>
                    <?php
                    $others_complete = 'yes';
                    for ($i = 0, $cnt = count($overall_percent); $i < $cnt; $i++) {
                      user_load_variables($overall_percent[$i][0], 'percent');
                    ?>
                      <tr>
                        <td><?php echo $percentfullname; ?></td>
                        <td><?php echo $overall_percent[$i][1]; ?></td>
                      </tr>
                      <?php
                      if (
                        $overall_percent[$i][0] != $real_user &&
                        $overall_percent[$i][1] < 100
                      ) {
                        $others_complete = 'no';
                      }
                      ?>
                    <?php } ?>
                  </table>
                </td>
              </tr>
            <?php } ?>
          </table>
          <input type="hidden" name="others_complete" value="<?php echo $others_complete; ?>" />
        <?php } /* end tasks only */ ?>

        <?php if ($DISABLE_LOCATION_FIELD != 'Y') { ?>
          <label for="location" data-toggle="tooltip" data-placement="top" title="<?php etooltip('location-help'); ?>">
            <?php etranslate('Location'); ?>:</label>
          <input class="form-control" type="text" name="location" id="entry_location" size="55" value="<?php echo htmlspecialchars($location); ?>" />
        <?php } ?>

        <?php if ($DISABLE_URL_FIELD != 'Y') { ?>
          <label for="url" data-toggle="tooltip" data-placement="top" title="<?php etooltip('url-help'); ?>">
            <?php etranslate('URL'); ?>:</label>
          <input class="form-control" type="text" name="entry_url" id="entry_url" size="100" value="<?php echo htmlspecialchars($location); ?>" />
        <?php } ?>

        <label for="date" data-toggle="tooltip" data-placement="top" title="<?php etooltip('date-help'); ?>">
          <?php echo $eType == 'task' ? translate('Start Date') : translate('Date'); ?>:</label>
        <?php echo date_selection('', $cal_date); ?>

        <?php if ($eType != 'task') {
          if (!isset($duration) || !is_numeric($duration))
            $duration = 0;
          $dur_h = intval($duration / 60); ?>
          <label for="timetype" data-toggle="tooltip" data-placement="top" data-html="true" title="<?php etooltip('time-help', '', true); ?>">
            <?php etranslate('Type'); ?>:</label>
          <select class="form-control" name="timetype" id="timetype" onchange="timetype_handler()">
            <option value="U" <?php echo $allday != 'Y' && $hour == -1 ? $selected : ''; ?>>
              <?php etranslate('Untimed event'); ?></option>
            <option value="T" <?php echo $allday != 'Y' && $hour >= 0 ? $selected : ''; ?>>
              <?php etranslate('Timed event'); ?></option>
            <option value="A" <?php echo $allday == 'Y' ? $selected : ''; ?>>
              <?php etranslate('All day event'); ?></option>
          </select>
          <?php if (empty($TZ_notice)) { ?>
            <span id="timezonenotice" style="visibility:hidden;">
              <label for="timezonenoticetext" data-toggle="tooltip" data-placement="top" title="<?php etooltip('Time entered here is based on your Timezone'); ?>">
                <?php etranslate('Timezone Offset'); ?>:</label></span>
          <?php } else { ?>
            <div id="timezonenoticetext"><?php echo $TZ_notice; ?></div>
          <?php } ?>

          <div class="form-inline mb-2" id="timeentrystart" style="visibility:hidden;">
            <label for="entry_hour" data-toggle="tooltip" data-placement="top" title="<?php etooltip('time-help'); ?>">
              <?php etranslate('Time'); ?>:</label>
            <?php echo time_selection('entry_', $cal_time); ?>
          </div>
          <?php if ($TIMED_EVT_LEN != 'E') { ?>
            <div class="form-inline" id="timeentryduration" style="visibility:hidden;">
              <label for="duration" data-toggle="tooltip" data-placement="top" title="<?php etooltip('duration-help'); ?>">
                <?php etranslate("Duration"); ?>:</label>
              <input class="form-control" type="text" name="duration_h" id="duration_h" size="2" maxlength="2" value="<?php if ($allday != 'Y') {
                                                                                                                          printf("%d", $dur_h);
                                                                                                                        } ?>" />:
              <input class="form-control" type="text" name="duration_m" id="duration_m" size="2" maxlength="2" value="<?php if ($allday != 'Y') {
                                                                                                    printf("%02d", $duration - ($dur_h * 60));
                                                                                                  } ?>" />
              &nbsp;(<label for="duration_h"><?php echo $hoursStr; ?></label>: <label for="duration_m"><?php echo $minutStr; ?></label>)
            </div>
          <?php } else { ?>
            <div id="timeentryend"><label data-toggle="tooltip" data-placement="top" title="<?php etooltip('end-time-help'); ?>">
                &nbsp;-&nbsp;
                <?php echo time_selection('end_', ($id ? add_duration($cal_time, $duration) : $cal_time)); ?>
            </div>
          <?php } ?>

        <?php } else { /* eType == task */ ?>
          <label for="entry_hour" data-toggle="tooltip" data-placement="top" data-html="true" title="<?php etooltip('time-help', '', true); ?>">
            <?php etranslate('Start Time'); ?>:</label>
          <?php echo time_selection('entry_', $cal_time); ?>

          <label for="due_YMD" data-toggle="tooltip" data-placement="top" data-html="true" title="<?php etooltip('date-help'); ?>">
            <?php etranslate('Due Date'); ?>:</label>
          <?php echo time_selection('due_', $due_date); ?>

          <label for="due_hour" data-toggle="tooltip" data-placement="top" data-html="true" title="<?php etooltip('date-help'); ?>">
            <?php etranslate('Due Time'); ?>:</label>
          <?php echo time_selection('due_', $due_time); ?>
        <?php } ?>

        <?php
        // Site-specific extra fields (see site_extras.php).
        // load and display any site-specific fields.
        if ($id > 0)
          $extras = get_site_extra_fields($id);

        $site_extracnt = count($site_extras);
        if ($site_extracnt && !empty($site_extras[0]['FIELDSET'])) {
          echo "<div><fieldset><legend>" . translate("Site Extas") . "</legend>\n<table>\n";
        }

        for ($i = 0; $i < $site_extracnt; $i++) {
          if ($site_extras[$i] == 'FIELDSET')
            continue;

          $extra_name = $site_extras[$i][0];
          $extra_descr = $site_extras[$i][1];
          $extra_type = $site_extras[$i][2];
          $extra_arg1 = $site_extras[$i][3];
          $extra_arg2 = $site_extras[$i][4];
          // Default value if needed.
          $defIdx = (empty($extras[$extra_name]['cal_data'])
            ? $extra_arg2 : $extras[$extra_name]['cal_data']);

          echo '<tr><td class="aligntop bold">'
            . ($extra_type == EXTRA_MULTILINETEXT ? '<br />' : '')
            . translate($extra_descr) . ':</td>
                      <td>';

          if ($extra_type == EXTRA_URL)
            echo '<input class="form-control" type="text" size="50" name="' . $extra_name . '" value="'
              . (empty($extras[$extra_name]['cal_data'])
                ? '' : htmlspecialchars($extras[$extra_name]['cal_data'])) . '" />';
          elseif ($extra_type == EXTRA_EMAIL)
            echo '<input class="form-control" type="email" size="30" name="' . $extra_name . '" value="'
              . (empty($extras[$extra_name]['cal_data'])
                ? '' : htmlspecialchars($extras[$extra_name]['cal_data'])) . '" />';
          elseif ($extra_type == EXTRA_DATE)
            echo date_selection(
              $extra_name,
              (empty($extras[$extra_name]['cal_date'])
                ? $cal_date : $extras[$extra_name]['cal_date'])
            );
          elseif ($extra_type == EXTRA_TEXT) {
            $size = ($extra_arg1 > 0 ? $extra_arg1 : 50);
            echo '<input class="form-control" type="text" size="' . $size . '" name="' . $extra_name
              . '" value="' . (empty($extras[$extra_name]['cal_data'])
                ? '' : htmlspecialchars($extras[$extra_name]['cal_data'])) . '" />';
          } elseif ($extra_type == EXTRA_MULTILINETEXT) {
            echo '<textarea class="form-control" rows="' . ($extra_arg2 > 0 ? $extra_arg2 : 5)
              . '" cols="' . ($extra_arg1 > 0 ? $extra_arg1 : 50) . '" name="'
              . $extra_name . '">' . (empty($extras[$extra_name]['cal_data'])
                ? '' : htmlspecialchars($extras[$extra_name]['cal_data']))
              . '</textarea>';
          } elseif ($extra_type == EXTRA_USER) {
            // Show list of calendar users...
            echo '<select class="form-control" name="' . $extra_name . '">' .
              '<option value="">None</option>';
            $userlist = get_my_users();
            $usercnt = count($userlist);
            for ($j = 0; $j < $usercnt; $j++) {
              if (access_is_enabled() && !access_user_calendar('view', $userlist[$j]['cal_login']))
                continue; // Cannot view calendar so cannot add to their cal.
              echo '<option value="' . $userlist[$j]['cal_login'] . '"'
                . (!empty($extras[$extra_name]['cal_data']) &&
                  ($userlist[$j]['cal_login'] == $extras[$extra_name]['cal_data'])
                  ? $selected : '')
                . '>' . $userlist[$j]['cal_fullname'] . '</option>';
            }
            echo '</select>';
          } elseif ($extra_type == EXTRA_SELECTLIST) {
            // Show custom select list.
            $extraSelectArr = $isMultiple = $multiselect = '';
            if (is_array($extra_arg1)) {
              $extra_arg1cnt = count($extra_arg1);
              if ($extra_arg2 > 0) {
                $multiselect = ' multiple="multiple" size="'
                  . min($extra_arg2, $extra_arg1cnt) . '" ';
                $isMultiple = '[]';
                if (!empty($extras))
                  $extraSelectArr = explode(',', $extras[$extra_name]['cal_data']);
              }
              echo '<select class="form-control" name="' . $extra_name . $isMultiple . '"'
                . $multiselect . '>';
              for ($j = 0; $j < $extra_arg1cnt; $j++) {
                echo '<option value="' . $extra_arg1[$j] . '" ';
                if (!empty($extras[$extra_name]['cal_data'])) {
                  if (
                    $extra_arg2 == 0 &&
                    $extra_arg1[$j] == $extras[$extra_name]['cal_data']
                  ) {
                    echo $selected;
                  } else if ($extra_arg2 > 0 && in_array($extra_arg1[$j], $extraSelectArr)) {
                    echo $selected;
                  }
                } else {
                  echo $j == 0 ? $selected : '';
                }
                echo '>' . $extra_arg1[$j] . '</option>';
              }
            }
            echo '</select>';
          } elseif ($extra_type == EXTRA_RADIO) {
            // Show custom radio selections.
            echo print_radio($extra_name, $extra_arg1, '', $defIdx);
          } elseif ($extra_type == EXTRA_CHECKBOX) {
            // Show custom checkbox option.
            echo print_checkbox([$extra_name, $extra_arg1, '', $defIdx]);
          }
          echo '</td></tr>';
        }
        if ($site_extracnt && !empty($site_extras[0]['FIELDSET'])) {
          echo "</fieldset></div>\n";
        }
        // end site-specific extra fields
        ?>

      </div>
    </div>

    <?php
    // Participants
    // Only ask for participants if we are multi-user.
    $show_participants = ($DISABLE_PARTICIPANTS_FIELD != 'Y');
    if ($is_admin)
      $show_participants = true;

    if ($login == '__public__' && $PUBLIC_ACCESS_OTHERS != 'Y')
      $show_participants = false;

    if ($single_user == 'N' && $show_participants) {
      $groups = get_groups($real_user);
      $userlist = get_my_users($create_by, 'invite');
      $num_users = $size = 0;
      $usercnt = count($userlist);
      $myusers = $nonusers = $users = $grouplist = '';

      for ($i = 0; $i < $usercnt; $i++) {
        $f = $userlist[$i]['cal_fullname'];
        $l = $userlist[$i]['cal_login'];
        $q = (!empty($selectedStatus[$l]) && $selectedStatus[$l] == 'W'
          ? ' (?)' : '');
        $size++;
        $users .= '<option value="' . $l . '">' . $f . '</option>';

        if ($id > 0) {
          if (!empty($participants[$l])) {
            $myusers .= '
                <option value="' . $l . '">'
              . $f . $q . '</option>';
          }
        } else {
          if (empty($defusers) && !empty($user) && !empty($userlist[$l])) {
            // Default selection of participants was in the URL as 'user=XXX'
            $myusers .= '<option value="' . $l . '">'
              . $f . $q . '</option>';
          } else if (!empty($defusers)) {
            // Default selection of participants was in the URL as 'defusers=user1,user2'
            if (!empty($participants[$l]))
              $myusers .= '<option value="' . $l . '">'
                . $f . $q . '</option>';
          } else if (($l == $login && !$is_assistant && !$is_nonuser_admin) ||
            (!empty($user) && $l == $user)
          )
            // Default selection of participants is logged in user.
            $myusers .= ' <option value="' . $l . '">' . $f . '</option>';

          if (
            $l == '__public__' && !empty($PUBLIC_ACCESS_DEFAULT_SELECTED) &&
            $PUBLIC_ACCESS_DEFAULT_SELECTED == 'Y'
          )
            $myusers .= '<option value="' . $l . '">'
              . $f . $q . '</option>';
        }
      }

      if ($NONUSER_ENABLED == 'Y') {
        // Include Public NUCs
        $mynonusers = get_my_nonusers($real_user, true);
        for ($i = 0, $cnt = count($mynonusers); $i < $cnt; $i++) {
          $l = $mynonusers[$i]['cal_login'];
          $n = $mynonusers[$i]['cal_fullname'];
          $q = (!empty($selectedStatus[$l]) && $selectedStatus[$l] == 'W'
            ? ' (?)' : '');
          $nonusers .= '<option value="' . $l . '"> ' . $n . '</option>';
          if (!empty($participants[$l])) {
            $myusers .= '<option value="' . $l . '">'
              . $n . $q . '</option>';
          } else if (!empty($user) && !empty($mynonusers[$l])) {
            // Default selection of participants was in the URL.
            $myusers .= '
                  <option value="' . $l . '">'
              . $n . $q . '</option>';
          }
        }
      }

      if ($GROUPS_ENABLED == 'Y') {
        for ($i = 0, $cnt = count($groups); $i < $cnt; $i++) {
          $grouplist .= '<option value="' . $groups[$i]['cal_group_id'] . '">'
            . $groups[$i]['cal_name'] . '</option>';
        }
      }
      $addStr = translate('Add');

      if ($size > 50)
        $size = 15;
      else if ($size > 10)
        $size = 10;
      else if ($size > 5)
        $size = $size;
      else
        $size = 4;
    ?>
      <!-- PARTICIPANTS -->
      <div class="tab-pane container fade" id="<?php echo $tabs_name[$tabI++]; ?>">
        <table width="100%" id="participantTable">
          <tr>
            <td colspan="3">
              <fieldset class="form-group border p-2">
                <legend class="w-auto px-2"><?php etranslate('Find Name'); ?></legend>

                <input class="form-control" type="text" size="20" name="lookup" id="lookup" onkeyup="lookupName()" />
              </fieldset>
            </td>
          </tr>
          <tr>
            <td>
              <fieldset class="form-group border p-2">
                <legend class="w-auto px-2"><?php etranslate('Users'); ?></legend>
                <select class="form-control m-1" name="participants[]" id="entry_part" size="<?php echo $size; ?>" multiple="multiple"><?php echo $users; ?>
                </select>
                <input class="btn btn-secondary m-1" name="movert" type="button" value="<?php echo $addStr; ?>" onclick=" selAdd(this);" />
              </fieldset>
            </td>
            <?php if (!empty($nonusers)) { ?>
              <td>
                <fieldset class="form-group border p-2">
                  <legend class="w-auto px-2"><?php etranslate('Resources'); ?></legend>
                  <select class="form-control m-1" name="nonuserPart[]" id="res_part" size="<?php echo $size; ?>" multiple="multiple">
                    <?php echo $nonusers; ?>
                  </select>
                  <input class="btn btn-secondary m-1" name="movert" type="button" value="<?php echo $addStr; ?>" onclick="selResource(this);" />
                </fieldset>
              </td>
            <?php } ?>
            <?php if ($GROUPS_ENABLED == 'Y') { ?>
            <td>
              <fieldset class="form-group border p-2">
                <legend class="w-auto px-2"><?php etranslate('Groups'); ?></legend>
                <select class="form-control m-1" name="groups" id="groups" size="<?php echo $size; ?>" onclick="addGroup()">
                  <?php echo $grouplist; ?>
                </select>
                <input class="btn btn-secondary m-1" name="movert" type="button" value="<?php echo $addStr; ?>" onclick="selAdd(this);" />
              </fieldset>
            </td>
            <?php } ?>
          <?php } ?>
          </td>
          </tr>
          <tr>
            <td colspan="2">
              <fieldset class="form-group border p-2">
                <legend class="w-auto px-2" data-toggle="tooltip" data-placement="top" title="<?php etooltip('participants-help'); ?>"><?php etranslate('Selected Participants'); ?></legend>

                <select class="form-control" name="selectedPart[]" id="sel_part" size="7" multiple="multiple">'
                  <?php echo $myusers; ?>
                </select>
                <input class="btn btn-secondary m-1" name="movelt" type="button" value="<?php etranslate('Remove'); ?>" onclick="selRemove( this );" />
            <input class="btn btn-secondary m-1" type="button" onclick="showSchedule()" value="<?php etranslate('Availability'); ?>..." />
              </fieldset>
            </td>
          </tr>
          <?php if (!empty($ALLOW_EXTERNAL_USERS) && $ALLOW_EXTERNAL_USERS == 'Y') { ?>
            <!-- External Users -->
            <tr>
              <td class="aligntop"><label data-toggle="tooltip" data-placement="top" title="<?php etooltip('external-participants-help'); ?>">
                  <?php etranslate('External Participants'); ?>:</label></td>
              <td colspan="1"><textarea name="externalparticipants" id="entry_extpart" rows="5" cols="75"><?php echo $external_users; ?></textarea></td>
            </tr>
          <?php } ?>
        </table>
      </div>
      <?php /* // End Participants */ ?>

      <?php

      if ($DISABLE_REPEATING_FIELD != 'Y') {
        echo "\n" . '<!-- REPEAT -->
      <div class="tab-pane container fade" id="' . $tabs_name[$tabI++] . '">
      <table cellpadding="3">
        <tr>
          <td class="align-top">
          <label for="rpttype" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-type-help') . '">'
          . translate('Type') . ':</label></td>
          <td colspan="2">
            <select class="form-control sm-auto" name="rpt_type" id="rpttype" '
          . 'onchange="rpttype_handler(); rpttype_weekly()">
              <option value="none"' . (strcmp($rpt_type, 'none') == 0
            ? $selected : '') . '>' . translate('None') . '</option>
              <option value="daily"' . (strcmp($rpt_type, 'daily') == 0
            ? $selected : '') . '>' . translate('Daily') . '</option>
              <option value="weekly"' . (strcmp($rpt_type, 'weekly') == 0
            ? $selected : '') . '>' . translate('Weekly') . '</option>
              <option value="monthlyByDay"'
          . (strcmp($rpt_type, 'monthlyByDay') == 0 ? $selected : '')
          // translate ( 'Monthly' ) translate ( 'by day' ) translate ( 'by date' )
          // translate ( 'by position' )
          . '>' . translate('Monthly (by day)') . '</option>
              <option value="monthlyByDate"'
          . (strcmp($rpt_type, 'monthlyByDate') == 0 ? $selected : '')
          . '>' . translate('Monthly (by date)') . '</option>
              <option value="monthlyBySetPos"'
          . (strcmp($rpt_type, 'monthlyBySetPos') == 0 ? $selected : '')
          . '>' . translate('Monthly (by position)') . '</option>
              <option value="yearly"' . (strcmp($rpt_type, 'yearly') == 0
            ? $selected : '') . '>' . translate('Yearly') . '</option>
              <option value="manual"'
          . (strcmp($rpt_type, 'manual') == 0 ? $selected : '')
          . '>' . translate('Manual') . '</option>
            </select>&nbsp;&nbsp;&nbsp;<label id="rpt_mode"><input '
          . 'type="checkbox" name="rptmode" id="rptmode" value="y" '
          . 'onclick="rpttype_handler()" '
          . (empty($expert_mode) ? '' : $checked)
          . ' />' . translate('Expert Mode') . '</label>
          </td>
        </tr>
        <tr id="rptenddate1" style="visibility:hidden;">
          <td rowspan="3">
         <label for="rpt_day" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-end-date-help') . '">'
          . translate('Ending')
          . ':</label></td>
          <td colspan="2" class="boxtop boxright boxleft"><input type="radio" '
          . 'name="rpt_end_use" id="rpt_untilf" value="f" '
          . (empty($rpt_end) && empty($rpt_count) ? $checked : '')
          . ' onclick="toggle_until()" /><label for="rpt_untilf">'
          . translate('Forever') . '</label></td>
        </tr>
        <tr id="rptenddate2" style="visibility:hidden;">
          <td class="boxleft"><input type="radio" name="rpt_end_use" '
          . 'id="rpt_untilu" value="u" ' . (empty($rpt_end) ? '' : $checked)
          . ' onclick="toggle_until()" />&nbsp;<label for="rpt_untilu">'
          . translate('Use end date') . '</label></td>
          <td class="boxright"><span class="end_day_selection" '
          . 'id="rpt_end_day_select">'
          . date_selection('rpt_', ($rpt_end_date ? $rpt_end_date : $cal_date))
          . '</span><span id="rpt_until_time_date"><br />' . time_selection('rpt_', $rpt_end_time) . '</span></td>
        </tr>
        <tr id="rptenddate3" style="visibility:hidden;">
          <td class="boxbottom boxleft"><input type="radio" name="rpt_end_use" '
          . 'id="rpt_untilc" value="c" ' . (empty($rpt_count) ? '' : $checked)
          . ' onclick="toggle_until()" />&nbsp;<label for="rpt_untilc">'
          . translate('Number of times') . '</label></td>
          <td class="boxright boxbottom"><input type="text" name="rpt_count" '
          . 'id="rpt_count" size="4" maxlength="4" value="' . $rpt_count . '" /></td>
        </tr>
        <tr id="rptfreq" style="visibility:hidden;">
          <td><label for="entry_freq" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-frequency-help') . '">'
          . translate('Frequency') . ':</label></td>
          <td colspan="2">
            <input type="text" name="rpt_freq" id="entry_freq" size="4" '
          . 'maxlength="4" value="' . $rpt_freq . '" />&nbsp;&nbsp;&nbsp;&nbsp;
            <label id="weekdays_only"><input type="checkbox" '
          . 'name="weekdays_only" value="y" '
          . (empty($weekdays_only) ? '' : $checked) . ' />'
          . translate('Weekdays Only')
          . '</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span id="rptwkst">
              <select name="wkst">';
        for ($i = 0; $i < 7; $i++) {
          echo '
                <option value="' . $byday_names[$i] . '" '
            . ($wkst == $byday_names[$i] ? $selected : '')
            . '>' . translate($byday_names[$i]) . '</option>';
        }
        echo '
              </select>&nbsp;&nbsp;<label for="rptwkst">'
          . translate('Week Start') . '</label>
            </span>
          </td>
        </tr>
        <tr>
          <td colspan="4"></td>
        </tr>
        <tr id="rptbydayextended" style="visibility:hidden;">
          <td>
          <label for="entry_freq" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-bydayextended-help') . '">'
          . translate('ByDay') . ':</label></td>
          <td colspan="2" class="boxall">
            <input type="hidden" name="bydayList" value="'
          . (empty($bydayStr) ? '' : $bydayStr) . '" />
            <input type="hidden" name="bymonthdayList" value="'
          . (empty($bymonthdayStr) ? '' : $bymonthdayStr) . '" />
            <input type="hidden" name="bysetposList" value="'
          . (empty($bysetposStr) ? '' : $bysetposStr) . '" />
            <table class="byxxx" cellpadding="2" cellspacing="2" '
          . 'border="1">
              <tr>
                <td></td>';
        // Display byday extended selection.
        // We use BUTTONS in a triple state configuration, and store the values in
        // a javascript array until form submission. We then set the hidden field
        // bydayList to the string value of the array.
        for (
          $rpt_byday_label = $WEEK_START;
          $rpt_byday_label <= ($WEEK_START + 6);
          $rpt_byday_label++
        ) {
          $rpt_byday_mod = $rpt_byday_label % 7;
          $class = (is_weekend($rpt_byday_mod) ? ' class="weekend" ' : '');
          echo '
                <th width="50px"' . $class . '><label>'
            . translate($weekday_names[$rpt_byday_mod]) . '</label></th>';
        }
        echo '
              </tr>
              <tr>
                <th>' . translate('All') . '</th>';
        for (
          $rpt_byday_single = $WEEK_START;
          $rpt_byday_single <= ($WEEK_START + 6);
          $rpt_byday_single++
        ) {
          $rpt_byday_mod = $rpt_byday_single % 7;
          echo '
                <td><input type="checkbox" name="bydayAll[]" id="'
            . $byday_names[$rpt_byday_mod] . '" value="'
            . "$byday_names[$rpt_byday_mod]\""
            . (in_array($byday_names[$rpt_byday_mod], $byday) ? $checked : '')
            . ' /></td>';
        }
        echo '
              </tr>
              <tr id="rptbydayln" style="visibility:hidden;">';
        for ($loop_ctr = 1; $loop_ctr < 6; $loop_ctr++) {
          echo '
                <th><label>' . $loop_ctr . '/' . ($loop_ctr - 6)
            . '</label></th>';
          for (
            $rpt_byday = $WEEK_START;
            $rpt_byday <= ($WEEK_START + 6);
            $rpt_byday++
          ) {
            $rpt_byday_mod = $rpt_byday % 7;
            $buttonvalue = (in_array($loop_ctr
              . $byday_names[$rpt_byday_mod], $byday)
              ? $loop_ctr . translate($byday_names[$rpt_byday_mod])
              : (in_array(($loop_ctr - 6)
                . $byday_names[$rpt_byday_mod], $byday)
                ? ($loop_ctr - 6)
                . translate($byday_names[$rpt_byday_mod]) : '        '));

            echo '
                <td><input type="button" name="byday" id="_' . $loop_ctr
              . $rpt_byday_mod . '" value="' . $buttonvalue
              . '" onclick="toggle_byday( this )" /></td>';
          }
          echo '
              </tr>';
          if ($loop_ctr < 5)
            echo '
              <tr id="rptbydayln' . $loop_ctr . '" style="visibility:hidden;">';
        }
        echo '
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="4"></td>
        </tr>
        <tr id="rptbymonth" style="visibility:hidden;">
          <td class="align-top">
          <label for="entry_freq" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-month-help') . '">'
          . translate('ByMonth') . '</label>:&nbsp;</td>
          <td colspan="2" class="boxall">'
          /* Display bymonth selection. */ . '
            <table cellpadding="5">
              <tr>';
        for ($rpt_month = 1; $rpt_month < 13; $rpt_month++) {
          echo '
                <td><label><input type="checkbox" name="bymonth[]" value="'
            . $rpt_month . '"' . (in_array($rpt_month, $bymonth) ? $checked : '')
            . ' />&nbsp;' . translate(
              date('M', mktime(0, 0, 0, $rpt_month, 1))
            )
            . '</label></td>' . ($rpt_month == 6 ? '
              </tr>
              <tr>' : '');
        }
        echo '
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="4"></td>
        </tr>
        <tr id="rptbysetpos" style="visibility:hidden;">
          <td id="BySetPoslabel">
          <label for="entry_freq" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-bysetpos-help') . '">' . translate('BySetPos')
          . ':&nbsp;</td>
          <td colspan="2" class="boxall">'
          /* Display bysetpos selection. */ . '
            <table class="byxxx" cellpadding="2" '
          . 'border="1">
              <tr>
                <td></td>';
        for (
          $rpt_bysetpos_label = 1;
          $rpt_bysetpos_label < 11;
          $rpt_bysetpos_label++
        ) {
          echo '
                <th width="37px"><label>' . $rpt_bysetpos_label
            . '</label></th>';
        }
        echo '
              </tr>
              <tr>';
        for ($loop_ctr = 1; $loop_ctr < 32; $loop_ctr++) {
          $buttonvalue = (in_array($loop_ctr, $bysetpos)
            ? ($loop_ctr) : (in_array(($loop_ctr - 32), $bysetpos)
              ? ($loop_ctr - 32) : '      '));
          echo ($loop_ctr == 1 || $loop_ctr == 11 || $loop_ctr == 21 ? '
                <th><label>' . $loop_ctr . '-' . ($loop_ctr + 9)
            . '</label></th>' : '') . ($loop_ctr == 31 ? '
                <th><label>31</label></th>' : '') . '
                <td><input type="button" name="bysetpos" id="bysetpos'
            . $loop_ctr . '" value="' . $buttonvalue
            . '" onclick="toggle_bysetpos( this )" /></td>'
            . ($loop_ctr % 10 == 0 ? '
              </tr>
            <tr>' : '');
        }
        echo '
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td colspan="4"></td>
      </tr>
      <tr id="rptbymonthdayextended" style="visibility:hidden;">
        <td class="xtooltip" id="ByMonthDaylabel">
        <label for="entry_freq" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-bymonthdayextended-help') . '">'  . translate('ByMonthDay')
          . ':&nbsp;</td>
        <td colspan="2" class="boxall">'
          /* Display bymonthday extended selection. */ . '
          <table class="byxxx" cellpadding="2" '
          . 'border="1">
            <tr>
              <td></td>';
        for (
          $rpt_bymonthday_label = 1;
          $rpt_bymonthday_label < 11;
          $rpt_bymonthday_label++
        ) {
          echo '
              <th width="37px"><label>' . $rpt_bymonthday_label
            . '</label></th>';
        }
        echo '
            </tr>
            <tr>';
        for ($loop_ctr = 1; $loop_ctr < 32; $loop_ctr++) {
          $buttonvalue = (in_array($loop_ctr, $bymonthday)
            ? ($loop_ctr) : (in_array(($loop_ctr - 32), $bymonthday)
              ? ($loop_ctr - 32) : '      '));
          echo ($loop_ctr == 1 || $loop_ctr == 11 || $loop_ctr == 21 ? '
            <th><label>' . $loop_ctr . '-' . ($loop_ctr + 9)
            . '</label></th>' : '')
            . ($loop_ctr == 31 ? '
            <th><label>31</label></th>' : '') . '
            <td><input type="button" name="bymonthday" id="bymonthday'
            . $loop_ctr . '" value="' . $buttonvalue
            . '" onclick="toggle_bymonthday( this )" /></td>'
            . ($loop_ctr % 10 == 0 ? '
          </tr>
          <tr>' : '');
        }
        echo '
          </tr>
        </table>';

        // Populate Repeat Exceptions data for later use.
        $excepts = '';
        $exceptcnt = count($exceptions);
        for ($i = 0; $i < $exceptcnt; $i++) {
          $excepts .= '
                  <option value="-' . $exceptions[$i] . '">-' . $exceptions[$i]
            . '</option>';
        }
        // Populate Repeat Inclusions data for later use
        $includecnt = count($inclusions);
        for ($i = 0; $i < $includecnt; $i++) {
          $excepts .= '
                  <option value="+' . $inclusions[$i] . '">+' . $inclusions[$i]
            . '</option>';
        }

        echo '
       </td>
      </tr>
      <tr id="rptbyweekno" style="visibility:hidden;">
        <td><label data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-byweekno-help') . '">' . translate('ByWeekNo') . ':</td>
        <td colspan="2"><input type="text" name="byweekno" id="byweekno" '
          . 'size="50" maxlength="100" value="' . $byweekno . '" /></td>
      </tr>
      <tr id="rptbyyearday" style="visibility:hidden;" title="'
          . tooltip('repeat-byyearday-help') . '">
        <td class="xtooltip">' . translate('ByYearDay') . ':</td>
        <td colspan="2"><input type="text" name="byyearday" id="byyearday" '
          . 'size="50" maxlength="100" value="' . $byyearday . '" /></td>
      </tr>
      <tr id="rptexceptions" style="visibility:visible;">
        <td><label class="align-top" data-toggle="tooltip" data-placement="top" title="' . tooltip('repeat-exceptions-help') . '">'
          . translate('Exclusions') . '/<br />'
          . translate('Inclusions') . ':</label></td>
        <td colspan="2" class="boxtop boxright boxbottom boxleft">
          <table width="250px">
            <tr>
              <td colspan="2">'
          . date_selection('except_', $rpt_end_date ? $rpt_end_date : $cal_date)
          . '</td>
            </tr>
            <tr>
              <td class="alignright aligntop" width="100">
                <label id="select_exceptions_not" style="visibility:'
          . (empty($excepts) ? 'visible' : 'hidden') . ';"></label>
                <select id="select_exceptions" name="exceptions[]" '
          . 'multiple="multiple" style="visibility:'
          . (empty($excepts) ? 'hidden' : 'visible')
          . ';" size="4">' . $excepts . '
                </select>
              </td>
              <td class="aligntop">
                <input class="alignleft btn" type="button" name="addException" value="'
          . translate('Add Exception') . '..." onclick="add_exception(0)" /><br />
                <input class="alignleft btn" type="button" name="addInclusion" value="'
          . translate('Add Inclusion') . '..." onclick="add_exception(1)" /><br />
                <input class="alignleft btn" type="button" name="delSelected" value="'
          . translate('Delete Selected') . '" onclick="del_selected()" />
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    </div>';
      }

      echo '

<!-- REMINDER INFO -->';
      if ($DISABLE_REMINDER_FIELD != 'Y') {
        $rem_minutes = $reminder_offset;
        // Will be specified in total minutes.
        $rem_days = intval($rem_minutes / 1440);
        $rem_minutes -= ($rem_days * 1440);
        $rem_hours = intval($rem_minutes / 60);
        $rem_minutes -= ($rem_hours * 60);

        $rem_before =
          (empty($reminder['before']) || $reminder['before'] == 'Y');
        $rem_related =
          (empty($reminder['related']) || $reminder['related'] == 'S');

        // Reminder Repeats.
        $rem_rep_count =
          (isset($reminder['repeats']) ? $reminder['repeats'] : 0);
        $rem_rep_minutes =
          (isset($reminder['duration']) ? $reminder['duration'] : 0);

        // Will be specified in total minutes.
        $rem_rep_days = intval($rem_rep_minutes / 1440);
        $rem_rep_minutes -= ($rem_rep_days * 1440);
        $rem_rep_hours = intval($rem_rep_minutes / 60);
        $rem_rep_minutes -= ($rem_rep_hours * 60);

        echo '<div class="tab-pane container fade" id="' . $tabs_name[$tabI++] . '">' . '
      <table cellpadding="3">
        <thead>
          <tr>
            <td class="xtooltip"><label>' . translate('Send Reminder')
          . ':</label></td>
            <td colspan="3">
              <input type="hidden" name="rem_action" value="'
          . (empty($reminder['action']) ? 'EMAIL' : $reminder['action']) . '" />
              <input type="hidden" name="rem_last_sent" value="'
          . (empty($reminder['last_sent']) ? 0 : $reminder['last_sent']) . '" />
              <input type="hidden" name="rem_times_sent" value="'
          . (empty($reminder['times_sent']) ? 0 : $reminder['times_sent'])
          . '" />
                <label><input type="radio" name="reminder" '
          . 'id="reminderYes" value="1"'
          . ($rem_status ? $checked : '') . ' onclick="toggle_reminders()" />'
          . translate('Yes') . '</label>&nbsp;
                <label><input type="radio" name="reminder" '
          . 'id="reminderNo" value="0"'
          . ($rem_status ? '' : $checked) . ' onclick="toggle_reminders()" />'
          . translate('No') . '</label>
            </td>
          </tr>
        </thead>
        <tbody id="reminder_when">
          <tr>
            <td class="xtooltip" rowspan="6"><label>' . translate('When')
          . ':</label></td>
            <td class="boxtop boxleft" width="20%"><label><input type="radio" '
          . 'name="rem_when" id="rem_when_date" value="Y" '
          . ($rem_use_date ? $checked : '') . ' onclick="toggle_rem_when()" />'
          . translate('Use Date/Time') . '&nbsp;</label></td>
            <td class="boxtop boxright" nowrap="nowrap" colspan="2">'
          . date_selection('reminder_', (empty($reminder['date'])
            ? $cal_date : $reminder['date'])) . '</td>
          </tr>
          <tr>
            <td class="boxleft">&nbsp;</td>
            <td class="boxright" colspan="2" nowrap="nowrap">'
          . time_selection('reminder_', (empty($reminder['time'])
            ? $cal_time : $reminder['time'])) . '</td>
          </tr>
          <tr>
            <td class="boxright boxleft" height="20px" colspan="3">&nbsp;</td>
          </tr>
          <tr>
            <td class="boxleft"><label><input type="radio" name="rem_when" '
          . 'id="rem_when_offset" value="N" ' . ($rem_use_date ? '' : $checked)
          . ' onclick="toggle_rem_when()" />' . translate('Use Offset')
          . '&nbsp;</label></td>
            <td class="boxright" nowrap="nowrap" colspan="2">
              <label><input type="text" size="2" name="rem_days" value="'
          . $rem_days . '" />' . $daysStr . '</label>&nbsp;
              <label><input type="text" size="2" name="rem_hours" '
          . 'value="' . $rem_hours . '" />' . $hoursStr . '</label>&nbsp;
              <label><input type="text" size="2" name="rem_minutes" value="'
          . $rem_minutes . '" />' . $minutStr . '</label>
            </td>
          </tr>
          <tr>
            <td class="boxleft">&nbsp;</td>
            <td><label><input type="radio" name="rem_before" '
          . 'id="rem_beforeY" value="Y"'
          . ($rem_before ? $checked : '') . ' />' . translate('Before')
          . '</label>&nbsp;</td>
            <td class="boxright"><label><input type="radio" name="rem_before" '
          . 'id="rem_beforeN" value="N"' . ($rem_before ? '' : $checked) . ' />'
          . translate('After') . '</label></td>
          </tr>
          <tr>
            <td class="boxbottom boxleft">&nbsp;</td>
            <td class="boxbottom"><label><input type="radio" '
          . 'name="rem_related" id="rem_relatedS" value="S"'
          . ($rem_related ? $checked : '') . ' />'
          . translate('Start') . '</label>&nbsp;</td>
            <td class="boxright boxbottom"><label><input type="radio" '
          . 'name="rem_related" id="rem_relatedE" value="E"'
          . ($rem_related ? '' : $checked) . ' />' . translate('End/Due')
          . '</label></td>
          </tr>
          <tr>
            <td colspan="4"></td>
          </tr>
        </tbody>
        <tbody id="reminder_repeat">
          <tr>
            <td class="xtooltip" rowspan="2"><label>' . translate('Repeat')
          . ':</label></td>
            <td class="boxtop boxleft">&nbsp;&nbsp;&nbsp;<label>'
          . translate('Times') . '</label></td>
            <td class="boxtop boxright" colspan="2"><input type="text" '
          . 'size="2" name="rem_rep_count" value="' . $rem_rep_count
          . '" onchange="toggle_rem_rep();" /></td>
          </tr>
          <tr id="rem_repeats">
            <td class="boxbottom boxleft">&nbsp;&nbsp;&nbsp;<label>'
          . translate('Every') . '</label></td>
            <td class="boxbottom boxright" colspan="2">
              <label><input type="text" size="2" name="rem_rep_days" value="'
          . $rem_rep_days . '" />' . $daysStr . '</label>&nbsp;
              <input type="text" size="2" name="rem_rep_hours" value="'
          . $rem_rep_hours . '" /><label>' . $hoursStr . '</label>&nbsp;
              <input type="text" size="2" name="rem_rep_minutes" value="'
          . $rem_rep_minutes . '" /><label>' . $minutStr . '</label>
            </td>
          </tr>
        </tbody>
      </table>
    </div>';
      }
      echo "</div></div>\n";

      if (
        file_exists('includes/classes/captcha/captcha.php') &&
        $login == '__public__' && !empty($ENABLE_CAPTCHA) && $ENABLE_CAPTCHA == 'Y'
      ) {
        if (function_exists('imagecreatetruecolor')) {
          include_once 'includes/classes/captcha/captcha.php';
          echo captcha::form();
        } else {
          etranslate('CAPTCHA Warning');
        }
      }

      ?>
      <div class="container">
        <div class="col-auto">
          <input type="button" class="form-check btn btn-primary" value="<?php echo $saveStr; ?>" onclick="validate_and_submit()">
          <?php if ($id > 0 && ($login == $create_by || $single_user == 'Y' || $is_admin)) { ?>
            <a class="btn btn-danger" href="del_entry.php?id=<?php echo $id; ?>&csrf_form_key=<?php echo getFormKey();?>" onclick="return confirm('<?php etranslate('Are you sure you want to delete this entry?'); ?>');">
              <?php etranslate('Delete entry'); ?></a><br />
          <?php } ?>
        </div>
        <input type="hidden" name="participant_list" value="" />
      </div>

</form>
<br>


<?php /* Create a hidden div tag for editing categories... */ ?>
<div class="modal" id="catModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php etranslate("Edit Categories"); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form name="editCatForm" id="editCatForm">

          <?php
          if (!empty($categories)) {
            foreach ($categories as $K => $V) {
              // None is index -1 and needs to be ignored
              if ($K > 0 && (($V['cat_owner'] == $login || $V['cat_global'] > 0)
                || $is_admin || substr($form, 0, 4) == 'edit')) {
                $tmpStr = $K . '">' . $V['cat_name'];
                echo '<input type="checkbox" name="cat_' . $K . '" ' .
                  'id="cat_' . $K . '"><label for="cat_' . $K . '">' .
                  htmlentities($V['cat_name']);
                if (empty($V['cat_owner']))
                  echo '<sup>*</sup>';
                echo "</label><br />\n";
              }
            }
          }
          ?>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php etranslate("Cancel"); ?></button>
        <button type="button" class="btn btn-primary" onclick="catOkHandler()"><?php etranslate("Save"); ?></button>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>
<?php

echo print_trailer();

?>
