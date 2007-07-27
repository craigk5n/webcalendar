<?php
// $Id$
// Displays a screenshot if called directly
// and a file exists that matches this script name.
// Include  this file in all themes.
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) && !
    empty ( $_SERVER['PHP_SELF'] ) )
  $PHP_SELF = $_SERVER['PHP_SELF'];

if ( ! empty ( $PHP_SELF ) && preg_match ( '/\/themes\//', $PHP_SELF ) ) {
  $filename = basename ( $PHP_SELF, '.php' ) . '.gif';
  echo <<<EOT
<?xml version="1.0" encoding="iso-8859-1"\?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml xml:lang="en" lang="en">
  <head></head>
  <body>
EOT;
  echo ( file_exists ( $filename )
    ? '<img src="' . $filename . '" />'
    : '<h2>' . ( function_exists ( 'translate' )
      ? translate ( 'NO PREVIEW AVAILABLE' ) : 'NO PREVIEW AVAILABLE' )
    . '</H2>' ) . '
  </body>
</html>';
}

?>
