<?php
/*
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */
include_once 'includes/init.php';
print_header();
echo '
    <h2>' . translate ( 'View Another Users Calendar' ) . '</h2>';

if ( ( $ALLOW_VIEW_OTHER != 'Y' && ! $is_admin ) ||
    ( $PUBLIC_ACCESS == 'Y' && $login == '__public__' &&
      $PUBLIC_ACCESS_OTHERS != 'Y' ) ) {
  $error = print_not_auth();
  echo '
    <blockquote>' . $error . '</blockquote>';
} else {
  $userlist = get_my_users ( '', 'view' );
  if ( $NONUSER_ENABLED == 'Y' ) {
    $nonusers = get_my_nonusers ( $login, true );
    $userlist = ( $NONUSER_AT_TOP == 'Y'
      ? array_merge ( $nonusers, $userlist )
      : array_merge ( $userlist, $nonusers ) );
  }

  if ( strpos ( ' ' . $STARTVIEW, 'view' ) )
    $url = 'month.php';
  else {
    $url = $STARTVIEW;
    if ( $url == 'month' || $url == 'day' || $url == 'week' || $url == 'year' )
      $url .= '.php';
  }

  ob_start();

  echo '
    <form action="' . $url . '" method="get" name="SelectUser">
      <select name="user" onchange="document.SelectUser.submit()">';

  foreach ( $userlist as $i ) {
    // Don't list current user
    if ( $login == $i['cal_login'] )
      continue;
    echo $option . $i['cal_login'] . '">' . $i['cal_fullname'] . '</option>';
  }

  echo '
      </select>
      <input type="submit" value="' . translate( 'Go' ) . '">
    </form>';

  ob_end_flush();
}

echo '<br><br>
    ' . print_trailer();

?>
