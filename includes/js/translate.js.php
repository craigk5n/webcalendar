<?php /* $Id$ */
defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );

// Leave the "// translate ( phrase )" in the *.js files
// to give a better indication of where they actually get used.

$tmp1 = $tmp2 = $tmpm = $tmpsm = $tmpw = $tmpsw = '';

foreach ( array ( 'SU','MO','TU','WE','TH','FR','SA' ) as $b ) {
  $tmp1 .= '\'' . $b . '\',';
  $tmp2 .= '\'' . translate ( $b ) . '\',';
}
for ( $i = 0; $i < 12; $i++ ) {
  $tmpm  .= "'" . month_name ( $i ) . "',";
  $tmpsm .= "'" . month_name ( $i, 'M' ) . "',";

  if ( $i < 7 ) {
    $tmpw  .= "'" . weekday_name ( $i ) . "',";
    $tmpsw .= "'" . weekday_name ( $i, 'D' ) . "',";
  }
}
echo 'var today= new Date();    // Examples using "js/dateformat.js".
isDST    = today.format(\'I\'); // Is user in daylight saving time?
tzOffSet = today.format(\'O\'); // User offset from UTC.

months       = [' . $tmpm . '],
shortMonths  = [' . $tmpsm . '],
weekdays     = [' . $tmpw . '],
shortWeekdays= [' . $tmpsw . '],

bydayLabels= [' . $tmp1 . '],
bydayTrans = [' . $tmp2 . '],
xlate      = [];

xlate[\'Categories\']         = \'' . translate( 'Categories', true ) . '\';
xlate[\'Hide\']               = \'' . translate( 'Hide', true ) . '\';
xlate[\'JSONerrXXX\']         = \'' . translate( 'JSON error XXX', true ) . '\';
xlate[\'Show\']               = \'' . translate( 'Show', true ) . '\';
xlate[\'addCalEntry\']        = \'' . translate( 'will add entry to your cal', true ). '\';
xlate[\'addParticipant\']     = \'' . translate( 'Please add a participant', true ) . '\';
xlate[\'approveEntry\']       = \'' . translate( 'Approve this entry?', true ) . '\';
xlate[\'cancel\']             = \'' . translate( 'Cancel', true ) . '\';
xlate[\'changeEntryDatetime\']= \'' . translate( 'Change entry date/time', true ) . '\';
xlate[\'colorInvalid\']       = \'' . translate( 'Invalid color', true ) . '\';
xlate[\'dbNameStr\']          = \'' . translate( 'Database Name', true ) . '\';
xlate[\'endServerURL\']       = \'' . translate( 'Server URL must end with /.', true ) . '\';
xlate[\'enterLoginPwd\']      = \'' . translate( 'must enter login/password', true ) . '\';
xlate[\'errorXXX\']           = \'' . translate( 'Error XXX', true ) . ' \';
xlate[\'error\']              = \'' . translate( 'Error', true ) . ' \';
xlate[\'formatColorRGB\']     = \'' . translate( 'Color format should be RGB', true ) . '\';
xlate[\'fullPath\']           = \'' . translate( 'Full Path (no backslashes)', true ) . '\';
xlate[\'illegalPwdChr\']      = \'' . translate( 'illegal chars in password', true ) . '\';
xlate[\'input1UserLogin\']    = \'' . translate( 'specify Single-User login', true ) . '\';
xlate[\'inputBriefDescipt\']  = \'' . translate( 'must enter Brief Description', true ) . '\';
xlate[\'inputPassword\']      = \'' . translate( 'must enter a password', true ) . '\';
xlate[\'inputTimeOfDay\']     = \'' . translate( 'must enter valid time', true ) . '\';
xlate[\'invalidCellBG\']      = \'' . translate( 'Invalid table cell BG color', true ) . '\';
xlate[\'invalidColor\']       = \'' . translate( 'Invalid Color', true ) . '\';
xlate[\'invalidDate\']        = \'' . translate( 'Invalid Date',true ) . '\';
xlate[\'invalidDocuBG\']      = \'' . translate( 'Invalid doc BG color', true ) . '\';
xlate[\'invalidEvtDate\']     = \'' . translate( 'Invalid Event Date', true ) . '\';
xlate[\'invalidGridFG\']      = \'' . translate( 'Invalid table grid color', true ) . '\';
xlate[\'invalidHours\']       = \'' . translate( 'Invalid work hours.', true ) . '\';
xlate[\'invalidPopupBG\']     = \'' . translate( 'Invalid popup BG color', true ) . '\';
xlate[\'invalidPopupFG\']     = \'' . translate( 'Invalid popup text color', true ) . '\';
xlate[\'invalidTHBG\']        = \'' . translate( 'Invalid table header BG color', true ) . '\';
xlate[\'invalidTextFG\']      = \'' . translate( 'Invalid table head text color', true ) . '\';
xlate[\'invalidTitleFG\']     = \'' . translate( 'Invalid doc title color', true ) . '\';
xlate[\'invalidTodayBG\']     = \'' . translate( 'Invalid table cell today BG', true ) . '\';
xlate[\'no\']                 = \'' . translate( 'no', true ) . '\';
xlate[\'noBlankCalId\']       = \'' . translate( 'no blank cal ID', true ) . '\';
xlate[\'noBlankNames\']       = \'' . translate( 'both names cannot be blank', true) . '\';
xlate[\'noBlankURL\']         = \'' . translate( 'no blank URLs', true ) . '\';
xlate[\'noBlankUsername\']    = \'' . translate( 'no blank username', true ) . '\';
xlate[\'noMatchImport\']      = \'' . translate( 'Import Format type mismatch', true ) . '\';
xlate[\'noXXXInDom\']         = \'' . translate( 'Could not find XXX in DOM.', true ) . '\';
xlate[\'noYMDXXX\']           = \'' . translate( 'No such object for YMD XXX', true ) . '\';
xlate[\'notFind\']            = \'' . translate( 'Could not find XXX.', true ) . '\';
xlate[\'passwordsNoMatch\']   = \'' . translate( 'passwords not identical', true ) . '\';
xlate[\'reallyDeleteEntry\']  = \'' . translate( 'really delete entry', true ) . '\';
xlate[\'reallyDeleteReport\'] = \'' . translate( 'really delete report', true ) . '\';
xlate[\'rejThisEntry\']       = \'' . translate( 'Reject this entry?', true ) . '\';
xlate[\'reqServerURL\']       = \'' . translate( 'Server URL is required.', true ) . '\';
xlate[\'timeB4WorkHours\']    = \'' . translate( 'time before work hours', true ) . '\';
xlate[\'yes\']                = \'' . translate( 'yes', true ) . '\';

';

?>
