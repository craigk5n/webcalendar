<?php
/* $Id$ */
include_once 'includes/init.php';
include_once 'includes/help_list.php';
build_header ( '', '', '', 29 );
echo '
    <h2>' . translate ( 'Help Index' ) . '</h2>
    <ul>';
$page = 0;
//display About WebCalendar link only on index page
$aboutStr = translate ( 'About WebCalendar' );
echo '
      <li><a title="' . $aboutStr . '" href="" onclick="openAbout()">'
      . $aboutStr . '</a></li>'; 
foreach ( $help_list as $key => $val ) {
  $page++;
  $transStr = translate ( $key );
  echo '
      <li><a title="' . $transStr . '" href="' . $val . '?thispage=' . $page
   . '">' . $transStr . '</a></li>';
}
echo '
    </ul>
    ' . print_trailer ( false, true, true );

?>
