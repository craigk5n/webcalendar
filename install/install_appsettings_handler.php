<?php
$app_settings = ['readonly', 'single_user', 'use_http_auth', 'user_inc', 'mode'];
if ($usingEnv) {
  // This shouldn't happen.
  $error = translate('Unknown error');
} else {
  // Should not be getting here...
  if ($appSettingsCorrect) {
    // Save settings to session
    $_SESSION['use_http_auth'] == 'N'; // default
    $_SESSION['user_inc'] = $_POST['user_inc'];
    if ($_SESSION['user_inc'] == 'http') {
      $_SESSION['user_inc'] == 'user.php';
      $_SESSION['use_http_auth'] == 'Y';
    } else if ($_SESSION['user_inc'] == 'none') {
      $_SESSION['user_inc'] == 'user.php'; // single-user
    }
    $_SESSION['single_user'] = $_POST['user_inc'] == 'none' ? 'Y' : 'N';
    $_SESSION['readonly'] = $_POST['readonly'] == '1' ? 'Y' : 'N';
    $_SESSION['mode'] = $_POST['mode'];
    $_SESSION['single_user_login'] = $_POST['single_user_login'];
    // echo "<pre>"; print_r($_SESSION); echo "</pre>"; exit;
    // Did the user change anything
    $foundChange = false;
    foreach ($app_settings as $setting) {
      if ($_SESSION[$setting] != $settings[$setting] ){
        $foundChange = true;
      }
    }
    if ($foundChange) {
      // Require user to save and overwrite settings.php in a future step.
      $_SESSION['appSettingsModified'] = 1;
    }
    redirectToNextAction();
  } else {
    $error = translate('Invalid Application Settings');
  }
}
