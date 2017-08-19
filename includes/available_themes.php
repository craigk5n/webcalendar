<?php

// Add available themes here.  This should correspond to the 
// directories in includes/menu/themes.
$_availableThemes = [
  'autumn_pref',
  'basic_admin',
  'default_admin',
  'default_pref',
  'spring_pref',
  'theme_inc',
  'touch_of_grey_pref'];

function getAvailableThemes () {
  global $_availableThemes;
  return $_availableThemes;
}

function isValidTheme ( $themeName ) {
  global $_availableThemes;
  return in_array ( $themeName, $_availableThemes );
}

?>
