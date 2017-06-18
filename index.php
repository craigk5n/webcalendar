<?php
/* $Id: index.php,v 1.16.2.2 2007/08/06 02:28:30 cknudsen Exp $ */
include_once 'includes/init.php';

// If not yet logged in, you will be redirected to login.php before
// we get to this point (by init.php included above).
if ( ! empty ( $STARTVIEW ) ) {
  $page = get_preferred_view ();
  if ( access_can_view_page ( $page ) )
    send_to_preferred_view ();
  else {
    // User's preferences need to be updated to their preferred view.
    if ( access_can_access_function ( ACCESS_PREFERENCES ) )
      do_redirect ( 'pref.php' );
    // User does not have access to preferences...
    // So, we need to pick another page.
    if ( access_can_access_function ( ACCESS_WEEK ) )
      do_redirect ( 'week.php' );
    elseif ( access_can_access_function ( ACCESS_MONTH ) )
      do_redirect ( 'month.php' );
    elseif ( access_can_access_function ( ACCESS_DAY ) )
      do_redirect ( 'day.php' );
    elseif ( access_can_access_function ( ACCESS_YEAR ) )
      do_redirect ( 'year.php' );
    // At this point, this user cannot view the preferred view in their
    // preferences (and they cannot update their preferences), and they cannot
    // view any of the standard day/week/month/year pages. All that's left is a
    // custom view that is either created by them or a global view.
    if ( count ( $views ) > 0 )
      do_redirect ( $views[0]['url'] );

    // No views either?  You gotta be kidding me! ;-)
  }
} else
  do_redirect ( 'month.php' );

?>
