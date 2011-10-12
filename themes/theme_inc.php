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
  echo '<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
  </head>
  <body>
    <' . ( file_exists( $filename )
    ? 'img src="' . $filename . '"'
    : 'h2>' . ( function_exists( 'translate' )
      ? translate( 'NO PREVIEW AVAILABLE' )
      : 'NO PREVIEW AVAILABLE' ) . '</h2' ) . '>
  </body>
</html>';
}

?>
