<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

print_header ( '', '', '', true );

echo $helpListStr . '
    <h2>' . translate( 'WebCal Docs' ) . '</h2>
    <h5>' . translate ( 'Currently in English only.') . '<h5>
    <ul>
      <li><a href="docs/WebCalendar-UserManual.html">' . translate( 'WebCal User Manual' ) . '</a></li>
      <li><a href="docs/WebCalendar-SysAdmin.html">' . translate( 'WebCal SysAdmin Guide' ) . '</a></li>
      <li><a href="docs/WebCalendar-DeveloperGuide.html">' . translate( 'WebCal Developer Guide' ) . '</a></li>
      <li><a href="docs/WebCalendar-Styling.html">' . translate( 'WebCal Styling HOWTO' ) . '</a></li>
      <li><a href="docs/WebCalendar-Database.html">' . translate( 'WebCal DB Docs' ) . '</a></li>
      <li><a href="docs/WebCalendar-Functions.html">' . translate( 'WebCal Function Docs' ) . '</a></li>
      <li><a href="http://www.k5n.us/wiki/">' . translate( 'WebCal Wiki' ) . '</a></li>
    </ul>';

print_trailer ( false, true, true );

?>
