<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

  build_header ( '', '', '', 29 );

  ob_start ();

  echo $helpListStr . '
    <h2>' . translate ( 'WebCalendar Documentation' ) . '</h2>
	  <ul>
        <li><a href="docs/WebCalendar-UserManual.html">WebCalendar User Manual</a></li>
        <li><a href="docs/WebCalendar-SysAdmin.html">WebCalendar System Administrator\'s Guide</a></li>
		<li><a href="docs/WebCalendar-DeveloperGuide.html">WebCalendar Developer Guide</a></li>
        <li><a href="docs/WebCalendar-Styling.html">WebCalendar Styling HOWTO</a></li>
		<li><a href="docs/WebCalendar-Database.html">WebCalendar Database Documentation</a></li>
        <li><a href="docs/WebCalendar-Functions.html">WebCalendar Function Documentation</a></li>
    </ul>';
	
	
	
print_trailer ( false, true, true );

  ?>
