<?php
include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/validate.php";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();

// If not yet logged in, you will be redirected to login.php before
// we get to this point (by connect.php included above)

do_redirect ( empty ( $STARTVIEW ) ? "month.php" : "$STARTVIEW.php" );
?>
