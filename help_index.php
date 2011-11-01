<?php // $Id$
include_once 'includes/init.php';
include_once 'includes/help_list.php';
print_header ( '', '', '', true );
echo '
    <h2>' . translate ( 'Help Index' ) . '</h2>
    <ul>';
$page = 0;
//display About WebCalendar link only on index page
echo '
      <li><a href="" onclick="javascript:openAbout()">'
 . translate( 'About WebCal' ) . '</a></li>';
foreach ( $help_list as $k => $v ) {
  $page++;
  echo '
      <li><a href="' . $v . '?thispage=' . $page . '">' . $k . '</a></li>';
}
echo '
    </ul>' . print_trailer( false, true, true );

?>
