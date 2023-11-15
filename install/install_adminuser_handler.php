<?php

$error = '';

try {
  db_load_admin();
  $_SESSION['alert'] = translate('Default admin account created with login "admin" and password "admin".');
} catch ( Exception $e ) {
  $error = $e->getMessage();
}

if (empty($error)) {
  redirectToNextAction();
}
