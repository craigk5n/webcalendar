<?php
include_once 'includes/init.php';

// If not yet logged in, you will be redirected to login.php before
// we get to this point (by connect.php included above)

do_redirect ( empty ( $STARTVIEW ) ? "month.php" : "$STARTVIEW.php" );
?>
