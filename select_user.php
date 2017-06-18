<?php
/* $Id: select_user.php,v 1.35.2.2 2008/02/12 01:47:52 cknudsen Exp $ */
include_once 'includes/init.php';
print_header ();
echo '
    <h2>' . translate ( 'View Another Users Calendar' ) . '</h2>';

if ( $ALLOW_VIEW_OTHER != 'Y' && ! $is_admin ) {
  $error = print_not_auth (7);
  echo '
    <blockquote>' . $error . '</blockquote>';
} else if (  $PUBLIC_ACCESS == 'Y' && $login == '__public__' &&
  $PUBLIC_ACCESS_OTHERS != 'Y' ) {
  $error = print_not_auth (35);
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

  if ( strstr ( $STARTVIEW, 'view' ) )
    $url = 'month.php';
  else {
    $url = $STARTVIEW;
    if ( $url == 'month' || $url == 'day' || $url == 'week' || $url == 'year' )
      $url .= '.php';
  }

  ob_start ();

  echo '
    <form action="' . $url . '" method="get" name="SelectUser">
      <select name="user" onchange="document.SelectUser.submit()">';

  for ( $i = 0, $cnt = count ( $userlist ); $i < $cnt; $i++ ) {
    // Don't list current user
    if ( $login == $userlist[$i]['cal_login'] )
      continue;
    echo '
        <option value="' . $userlist[$i]['cal_login'] . '">'
     . $userlist[$i]['cal_fullname'] . '</option>';
  }

  echo '
      </select>
      <input type="submit" value="' . translate ( 'Go' ) . '" />
    </form>';

  ob_end_flush ();
}

echo '<br /><br />
    ' . print_trailer ();

?>
