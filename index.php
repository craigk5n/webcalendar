<?php
include_once 'includes/init.php';

// If not yet logged in, you will be redirected to login.php before
// we get to this point (by connect.php included above)

if ( ! empty ( $STARTVIEW ) )
  send_to_preferred_view ();
else
  do_redirect ( "month.php" );
?>
