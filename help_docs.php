<?php
/* $Id: help_docs.php,v 1.3.2.2 2007/08/06 02:28:30 cknudsen Exp $ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

print_header ( '', '', '', true );

echo $helpListStr . '
    <h2>' . translate ( 'WebCalendar Documentation' ) . '</h2>
    <h5>' . translate ( 'Currently in English only.') . '<h5>
    <ul>
      <li><a href="docs/WebCalendar-UserManual.html">WebCalendar User Manual</a></li>
      <li><a href="docs/WebCalendar-SysAdmin.html">WebCalendar System Administrator\'s Guide</a></li>
      <li><a href="docs/WebCalendar-DeveloperGuide.html">WebCalendar Developer Guide</a></li>
      <li><a href="docs/WebCalendar-Styling.html">WebCalendar Styling HOWTO</a></li>
      <li><a href="docs/WebCalendar-Database.html">WebCalendar Database Documentation</a></li>
      <li><a href="docs/WebCalendar-Functions.html">WebCalendar Function Documentation</a></li>
      <li><a href="http://www.k5n.us/dokuwiki/doku.php">WebCalendar Wiki</a></li>
    </ul>';

print_trailer ( false, true, true );

?>
