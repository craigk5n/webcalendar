<?php
require_once 'includes/init.php';
require_once 'includes/help_list.php';

if ( empty ( $SERVER_SOFTWARE ) )
  $SERVER_SOFTWARE = $_SERVER['SERVER_SOFTWARE'];

if ( empty ( $HTTP_USER_AGENT ) )
  $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

print_header ( [], '', '', true );

echo $helpListStr . '
    <h2>' . translate ( 'WebCalendar Documentation' ) . '</h2>
    <h5>' . translate ( 'Currently in English only.') . '<h5>
    <ul>
      <li><a href="docs/user-guide.md">WebCalendar User Guide</a></li>
      <li><a href="docs/admin-guide.md">WebCalendar Administrator Guide</a></li>
      <li><a href="docs/developer-guide.md">WebCalendar Developer Guide</a></li>
      <li><a href="docs/configuration.md">WebCalendar Configuration Reference</a></li>
      <li><a href="docs/WebCalendar-Database.md">WebCalendar Database Documentation</a></li>
      <li><a href="docs/faq.md">FAQ</a></li>
    </ul>';

echo print_trailer ( false, true, true );

?>
