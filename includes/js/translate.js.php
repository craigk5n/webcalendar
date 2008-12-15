<?php /* $Id$ */
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

global $byday_names, $GROUPS_ENABLED, $WORK_DAY_END_HOUR, $WORK_DAY_START_HOUR;

echo 'var
  allowCustomColors= ' . $ALLOW_COLOR_CUSTOMIZATION . ',
  bydayLabels      = ' . $byday_names . ',
  evtEditTabs      = ' . $GLOBALS['EVENT_EDIT_TABS'] . ',
  groupsEnabled    = ' . $GROUPS_ENABLED . ',
  timeFormat       = ' . $GLOBALS['TIME_FORMAT'] . ',
  workEndHour      = ' . $WORK_DAY_END_HOUR . ',
  workStartHour    = ' . $WORK_DAY_START_HOUR . ',
  xlate            = [],
  bydayTrans       = [
    \'' . translate( 'SU', true ) . '\',
    \'' . translate( 'MO', true ) . '\',
    \'' . translate( 'TU', true ) . '\',
    \'' . translate( 'WE', true ) . '\',
    \'' . translate( 'TH', true ) . '\',
    \'' . translate( 'FR', true ) . '\',
    \'' . translate( 'SA', true ) . '\'
  ];

xlate[\'addParticipant\']      = \'' . translate( 'Please add a participant', true ) . '\';
xlate[\'Categories\']          = \'' . translate( 'Categories', true ) . '\';
xlate[\'changeEntryDatetime\'] = \'' . translate( 'Change the date and time of this entry?', true ) . '\';
xlate[\'colorFormatRGB\']      = \'' . translate( 'Color format should be RRGGBB.', true ) . '\';
xlate[\'dbNameStr\']           = \'' . translate( 'Database Name', true ). ':\';
xlate[\'endServerURL\']        = \'' . translate( 'Server URL must end with /.', true ) . "\n';" . '
xlate[\'error\']               = \'' . translate( 'Error', true ) . ":\n\n';" . '
xlate[\'formatColorRGB\']      = \'' . translate( 'Color format should be RRGGBB.', true ) . "\n';" . '
xlate[\'fullPath\']            = \'' . translate( 'Full Path (no backslashes)', true ) . '\';
xlate[\'illegalPwdChr\']       = \'' . translate( 'The password contains illegal characters.', true ) . '\';
xlate[\'input1UserLogin\']     = \'' . translate( 'Error you must specify a Single-User Login', true ) . '\';
xlate[\'inputBriefDescipt\']   = \'' . translate( 'You have not entered a Brief Description.', true ) . '\';
xlate[\'inputPassword\']       = \'' . translate( 'You have not entered a password.', true ) . '\';
xlate[\'inputTimeOfDay\']      = \'' . translate( 'You have not entered a valid time of day.', true ) . '\';
xlate[\'invalidCellBG\']       = \'' . translate( 'Invalid color for table cell background.', true ) . "\n';" . '
xlate[\'invalidColor\']        = \'' . translate( 'Invalid Color', true ) . '\';
xlate[\'invalidDate\']         = \'' . translate( 'Invalid Date',true ) . '\';
xlate[\'invalidDocuBG\']       = \'' . translate( 'Invalid color for document background.', true ) . "\n';" . '
xlate[\'invalidEvtDate\']      = \'' . translate( 'Invalid Event Date.', true ) . '\';
xlate[\'invalidGridFG\']       = \'' . translate( 'Invalid color for table grid.', true ) . "\n';" . '
xlate[\'invalidHours\']        = \'' . translate( 'Invalid work hours.', true ) . "\n';" . '
xlate[\'invalidPopupBG\']      = \'' . translate( 'Invalid color for event popup background.', true ) . "\n';" . '
xlate[\'invalidPopupFG\']      = \'' . translate( 'Invalid color for event popup text.', true ) . "\n';" . '
xlate[\'invalidTextFG\']       = \'' . translate( 'Invalid color for table header text.', true ) . "\n';" . '
xlate[\'invalidTHBG\']         = \'' . translate( 'Invalid color for table header background.', true ) . "\n';" . '
xlate[\'invalidTitleFG\']      = \'' . translate( 'Invalid color for document title.', true ) . "\n';" . '
xlate[\'invalidTodayBG\']      = \'' . translate( 'Invalid color for table cell background for today.', true ) . "\n';" . '
xlate[\'noBlankCalId\']        = \'' . translate( 'Calendar ID cannot be blank.', true) . '\';
xlate[\'noBlankNames\']        = \'' . translate( 'First and last names cannot both be blank.', true) . '\';
xlate[\'noBlankURL\']          = \'' . translate( 'URL cannot be blank.', true ) . '\';
xlate[\'noBlankUsername\']     = \'' . translate( 'Username cannot be blank.', true ) . '\';
xlate[\'noMatchImport\']       = \'' . translate( 'File type does not match Import Format', true ) . '\';
xlate[\'notFind\']             = \'' . translate( 'Could not find XXX.', true ) . '\';
xlate[\'notInDom\']            = \'' . translate( 'Could not find XXX in DOM.', true ) . '\';
xlate[\'passwordsNoMatch\']    = \'' . translate( 'The passwords were not identical.', true ) . '\';
xlate[\'reqServerURL\']        = \'' . translate( 'Server URL is required.', true ) . "\n';" . '
xlate[\'timeB4WorkHours\']     = \'' . translate( 'The time you have entered begins before your preferred work hours. Is this correct?', true ) . '\';';

?>
