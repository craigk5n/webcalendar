<?php // $Id$
defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

// Leave the "// translate(phrase)" in the *.js files
// to give a better indication of where they actually get used.

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

xlate[\'addParticipant\']     = \'' . translate( 'Please add a participant', true ) . '\';
xlate[\'Categories\']         = \'' . translate( 'Categories', true ) . '\';
xlate[\'changeEntryDatetime\']= \'' . translate( 'Change the date and time of this entry?', true ) . '\';
xlate[\'colorInvalid\']       = \'' . translate( 'Invalid color', true ) . '\';
xlate[\'dbNameStr\']          = \'' . translate( 'Database Name', true ) . '\';
xlate[\'endServerURL\']       = \'' . translate( 'Server URL must end with /.', true ) . '\';
xlate[\'error\']              = \'' . translate( 'Error', true ) . '\';
xlate[\'formatColorRGB\']     = \'' . translate( 'Color format should be RRGGBB.', true ) . '\';
xlate[\'fullPath\']           = \'' . translate( 'Full Path (no backslashes)', true ) . '\';
xlate[\'illegalPwdChr\']      = \'' . translate( 'The password contains illegal characters.', true ) . '\';
xlate[\'input1UserLogin\']    = \'' . translate( 'Error you must specify a Single-User Login', true ) . '\';
xlate[\'inputBriefDescipt\']  = \'' . translate( 'You have not entered a Brief Description', true ) . '\';
xlate[\'inputPassword\']      = \'' . translate( 'You have not entered a password.', true ) . '\';
xlate[\'inputTimeOfDay\']     = \'' . translate( 'You have not entered a valid time of day', true ) . '\';
xlate[\'invalidCellBG\']      = \'' . translate( 'Invalid color for table cell background.', true ) . '\';
xlate[\'invalidColor\']       = \'' . translate( 'Invalid Color', true ) . '\';
xlate[\'invalidDate\']        = \'' . translate( 'Invalid Date',true ) . '\';
xlate[\'invalidDocuBG\']      = \'' . translate( 'Invalid color for document background.', true ) . '\';
xlate[\'invalidEvtDate\']     = \'' . translate( 'Invalid Event Date', true ) . '\';
xlate[\'invalidGridFG\']      = \'' . translate( 'Invalid color for table grid.', true ) . '\';
xlate[\'invalidHours\']       = \'' . translate( 'Invalid work hours.', true ) . '\';
xlate[\'invalidPopupBG\']     = \'' . translate( 'Invalid color for event popup background.', true ) . '\';
xlate[\'invalidPopupFG\']     = \'' . translate( 'Invalid color for event popup text.', true ) . '\';
xlate[\'invalidTextFG\']      = \'' . translate( 'Invalid color for table header text.', true ) . '\';
xlate[\'invalidTHBG\']        = \'' . translate( 'Invalid color for table header background.', true ) . '\';
xlate[\'invalidTitleFG\']     = \'' . translate( 'Invalid color for document title.', true ) . '\';
xlate[\'invalidTodayBG\']     = \'' . translate( 'Invalid color for table cell background for today.', true ) . '\';
xlate[\'noBlankCalId\']       = \'' . translate( 'Calendar ID cannot be blank.', true ) . '\';
xlate[\'noBlankNames\']       = \'' . translate( 'First and last names cannot both be blank.', true) . '\';
xlate[\'noBlankURL\']         = \'' . translate( 'URL cannot be blank.', true ) . '\';
xlate[\'noBlankUsername\']    = \'' . translate( 'Username cannot be blank.', true ) . '\';
xlate[\'noMatchImport\']      = \'' . translate( 'File type does not match Import Format', true ) . '\';
xlate[\'notFind\']            = \'' . translate( 'Could not find XXX.', true ) . '\';
xlate[\'notInDom\']           = \'' . translate( 'Could not find XXX in DOM.', true ) . '\';
xlate[\'passwordsNoMatch\']   = \'' . translate( 'The passwords were not identical.', true ) . '\';
xlate[\'reallyDeleteEntry\']  = \'' . translate( 'reallyDeleteEntry', true ) . '\';
xlate[\'reqServerURL\']       = \'' . translate( 'Server URL is required.', true ) . '\';
xlate[\'timeB4WorkHours\']    = \'' . translate( 'time prior to work hours...', true ) . '\';
';

?>
