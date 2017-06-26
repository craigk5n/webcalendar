<?php // $Id: translate.js.php,v 1.8 2010/09/16 01:20:22 cknudsen Exp $
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

global $GROUPS_ENABLED, $WORK_DAY_END_HOUR, $WORK_DAY_START_HOUR;

$tmp1 =
$tmp2 = '';

foreach( array( 'SU','MO','TU','WE','TH','FR','SA' ) as $b ) {
  $tmp1 .= '\'' . $b . '\',';
  $tmp2 .= '\'' . translate( $b ) . '\',';
}
echo 'var
  allowCustomColors= "' . $ALLOW_COLOR_CUSTOMIZATION . '",
  bydayLabels      = [' . $tmp1 . '],
  bydayTrans       = [' . $tmp2 . '],
  evtEditTabs      = "' .
    ( empty ( $GLOBALS['EVENT_EDIT_TABS'] ) ? 'Y' : $GLOBALS['EVENT_EDIT_TABS'] ) . '",
  groupsEnabled    = "' . $GROUPS_ENABLED . '",
  timeFormat       = "' . $GLOBALS['TIME_FORMAT'] . '",
  workEndHour      = "' . $WORK_DAY_END_HOUR . '",
  workStartHour    = "' . $WORK_DAY_START_HOUR . '",
  xlate            = [];

// Page: includes/js/admin.js
xlate[\'endServerURL\']   = \'' . translate( 'Server URL must end with /.', true ) . "'\n;" . '
xlate[\'formatColorRGB\'] = \'' . translate( 'Color format should be RRGGBB.', true ) . "'\n;" . '
xlate[\'invalidCellBG\']  = \'' . translate( 'Invalid color for table cell background.', true ) . "'\n;" . '
xlate[\'invalidDocuBG\']  = \'' . translate( 'Invalid color for document background.', true ) . "'\n;" . '
xlate[\'invalidGridFG\']  = \'' . translate( 'Invalid color for table grid.', true ) . "'\n;" . '
xlate[\'invalidHours\']   = \'' . translate( 'Invalid work hours.', true ) . "'\n;" . '
xlate[\'invalidPopupBG\'] = \'' . translate( 'Invalid color for event popup background.', true ) . "'\n;" . '
xlate[\'invalidPopupFG\'] = \'' . translate( 'Invalid color for event popup text.', true ) . "'\n;" . '
xlate[\'invalidTextFG\']  = \'' . translate( 'Invalid color for table header text.', true ) . "'\n;" . '
xlate[\'invalidTHBG\']    = \'' . translate( 'Invalid color for table header background.', true ) . "'\n;" . '
xlate[\'invalidTitleFG\'] = \'' . translate( 'Invalid color for document title.', true ) . "'\n;" . '
xlate[\'invalidTodayBG\'] = \'' . translate( 'Invalid color for table cell background for today.', true ) . "'\n;" . '
xlate[\'reqServerURL\']   = \'' . translate( 'Server URL is required.', true ) . "'\n;" . '

// Page: includes/js/availability.js
xlate[\'changeEntryDatetime\'] = \'' . translate( 'Change the date and time of this entry?', true ) . '\';

// Page: includes/js/edit_entry.js
xlate[\'addParticipant\']    = \'' . translate( 'Please add a participant', true ) . '\';
xlate[\'inputBriefDescipt\'] = \'' . translate( 'You have not entered a Brief Description', true ) . '\';
xlate[\'inputTimeOfDay\']    = \'' . translate( 'You have not entered a valid time of day', true ) . '\';
xlate[\'invalidDate\']       = \'' . translate( 'Invalid Date',true ) . '\';
xlate[\'invalidEvtDate\']    = \'' . translate( 'Invalid Event Date', true ) . '\';
xlate[\'timeB4WorkHours\']   = \'' . translate( 'time prior to work hours...', true ) . '\';

// Page: includes/js/edit_nonuser.php
xlate[\'noBlankCalId\'] = \'' . translate( 'Calendar ID cannot be blank.', true ) . '\';
xlate[\'noBlankNames\'] = \'' . translate( 'First and last names cannot both be blank.', true) . '\';

// Page: includes/js/edit_remotes.php
xlate[\'colorInvalid\'] = \'' . translate( 'Invalid color', true ) . '\';
xlate[\'noBlankURL\']  = \'' . translate( 'URL cannot be blank.', true ) . '\';

// Page: includes/js/export_import.php
xlate[\'noMatchImport\'] = \'' . translate( 'File type does not match Import Format', true ) . '\';

// Page: includes/js/visible.js
xlate[\'invalidColor\'] = \'' . translate( 'Invalid Color', true ) . '\';

// So far, the rest already get listed by page in the translations/.txt files.
xlate[\'Categories\']       = \'' . translate( 'Categories', true ) . '\';
xlate[\'dbNameStr\']        = \'' . translate( 'Database Name', true ) . ':\';
xlate[\'error\']            = \'' . translate( 'Error', true ) . "'\n;" . '
xlate[\'fullPath\']         = \'' . translate( 'Full Path (no backslashes)', true ) . '\';
xlate[\'illegalPwdChr\']    = \'' . translate( 'The password contains illegal characters.', true ) . '\';
xlate[\'input1UserLogin\']  = \'' . translate( 'Error you must specify a Single-User Login', true ) . '\';
xlate[\'inputPassword\']    = \'' . translate( 'You have not entered a password.', true ) . '\';
xlate[\'noBlankUsername\']  = \'' . translate( 'Username cannot be blank.', true ) . '\';
xlate[\'notFind\']          = \'' . translate( 'Could not find XXX.', true ) . '\';
xlate[\'notInDom\']         = \'' . translate( 'Could not find XXX in DOM.', true ) . '\';
xlate[\'passwordsNoMatch\'] = \'' . translate( 'The passwords were not identical.', true ) . '\';';

?>
