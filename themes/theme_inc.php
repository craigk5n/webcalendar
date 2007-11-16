<?php
// $Id$
// Displays a screenshot if called directly
// and a file exists that matches this script name.
// Include  this file in all themes.
if ( preg_match ( '/\/themes\//', $_SERVER['PHP_SELF'] ) ) {
  $filename = basename ( $_SERVER['PHP_SELF'], '.php' ) . '.gif';
  echo <<<EOT
<?xml version="1.0" encoding="iso-8859-1"\?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml xml:lang="en" lang="en">
  <head></head>
  <body>
EOT;
  echo ( file_exists ( $filename )
    ? '<img src="' . $filename . '" />' : '<h2>NO PREVIEW AVAILABLE</H2>' ) . '
  </body>
</html>';
}

?>
