<?php
// This handler only gets called if we prompt the user to acknowledge that
// their PHP settings do not match the recommended settings.
$_SESSION["phpSettingsAcked"] = "1";  // User ack'd
redirectToNextAction();
?>
