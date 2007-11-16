<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

build_header ( '', '', '', 29 );

ob_start ();

echo $helpListStr . '
    <h2>' . translate ( 'Help' ) . ': ' . translate ( 'Import' ) . '</h2>
    <h3>' . translate ( 'Palm Desktop' ) . '</h3>
    <p>' .
translate ( 'This form will allow you to import entries from the Palm Desktop Datebook.' )
 . '<br />' .
translate ( 'It should be located in your Palm directory' )
 . '</p>
    <p>' . translate ( 'The following entries will not be imported' ) . ':</p>
    <ul>
      <li>' . translate ( 'Entries older than the current date' ) . '</li>
      <li>' .
translate ( 'Entries created in the Palm Desktop that have not been HotSync&#39;d' )
 . '</li>
    </ul>
    <p>' .
translate ( 'Anything imported from Palm' )
 . translate ( 'Therefore, updates should be made in the Palm Desktop.' )
 . '</p>
    <h3>' . translate ( 'vCal' ) . '</h3>
    <p>' . translate ( 'This form will import vCalendar (.vcs) 1.0 events' )
 . '.</p>
    <p>' . translate ( 'The following formats have been tested' ) . ':</p>
    <ul>
      <li>Palm Desktop 4</li>
      <li>Lotus Organizer 6</li>
      <li>Microsoft Outlook 2002</li>
    </ul>
    <h3>iCalendar</h3>
    <p>' . translate ( 'This form will import iCalendar (.ics) events' ) . '. '
 . translate ( 'Enabling' ) . ' <b>' . translate ( 'Overwrite Prior Import' )
 . '</b>, ' .
translate ( 'same UID marked as deleted' )
 . '</p>';

ob_end_flush ();

echo print_trailer ( false, true, true );

?>
