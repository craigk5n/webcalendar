<?php

function update_password_in_settings($file, $password, $hint)
{
  // Check if file exists
  if (!file_exists($file)) {
    return false; // File doesn't exist
  }

  // Read the file content
  $content = file_get_contents($file);

  // Update or set the install_password and hint lines
  if (strpos($content, 'install_password:') !== false) {
    // Replace existing install_password and hint
    $content = preg_replace('/install_password:.*\n/', 'install_password: ' . $password . "\n", $content);
  } else {
    // Add the install_password
    $content = str_replace("# end settings.php", "install_password: " . $password . "\n# end settings.php", $content);
  }
  if (strpos($content, 'install_password_hint:') !== false) {
    $content = preg_replace('/install_password_hint:.*\n/', 'install_password_hint: ' . $hint . "\n", $content);
  } else {
    $content = str_replace("# end settings.php", "install_password_hint: " . $hint . "\n# end settings.php", $content);
  }

  // Update the date
  $date = new DateTime();
  $formattedDate = $date->format('D, d M Y H:i:s O');
  $content = preg_replace('/updated via install\/index.php on .*/', 'updated via install/index.php on ' . $formattedDate, $content);

  // Write the updated content back to the file
  return file_put_contents($file, $content);
}

function write_password_in_new_settings($file, $password, $hint)
{
  $date = new DateTime();
  $formattedDate = $date->format('D, d M Y H:i:s O');
  $content = "<?php\n/* updated via install/index.php on " . $formattedDate . "\n" .
    "install_password: $password\ninstall_password_hint: $hint\n# end settings.php */\n?>\n";
  return file_put_contents($file, $content);
}

// Handle form submission on Auth page (both setting and checking password)
$passwordSet = !empty($settings['install_password']);

if (!$passwordSet) {
  // No password set.  New instsall. Set password now.
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';
  if ($password != $password2) {
    $error = translate('Your passwords must match.');
  }
  $hint = $_POST['hint'] ?? '';
  $settingsFile = __DIR__ . '/../includes/settings.php';
  if (file_exists($settingsFile) && strlen(file_get_contents($settingsFile) > 10)) {
    $ret = update_password_in_settings($settingsFile, md5($password), $hint);
  } else {
    $ret = write_password_in_new_settings($settingsFile, md5($password), $hint);
  }
  if (!$ret) {
    $error = 'Error writing ' . $settingsFile . ' file.';
  } else {
    $_SESSION['alert'] = translate('Install password saved.  Login with password to continue.');
    redirectToNextAction();
  }
} else {
  // Upgrade: check the password
  $password = $_POST['password'];
  $hash = md5($password);
  if ($hash == $settings['install_password']) {
    // Success
    $_SESSION["validUser"] = "1";  // Successful login session var
    $_SESSION['alert'] = translate('Successful login');
    redirectToFurthestAvailableAction();
  } else {
    $error = translate("Invalid passphrase.");
  }
}
