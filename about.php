<?php
// $Id$
include_once 'includes/init.php';

$credits = getPostValue( 'Credits' );
static $data;

if( empty( $data ) ) {
  //  Read in and format AUTHORS file.
  if( $fd = @fopen( 'AUTHORS', 'r' ) ) {
    while( ! feof( $fd ) && empty( $error ) ) {
      $data .= fgets( $fd, 4096 );
    }
    fclose( $fd );
  }
  $data = preg_replace( '/<.+>+/', '', $data );
  $data = preg_replace( "/\n\s/", '<br>&nbsp;', $data );
  $data = preg_replace( '/\s\s+/', '&nbsp;&nbsp;', $data );
  $data = preg_replace( "/\n/", '<br>', $data );
}

ob_start();
print_header( '', ( empty( $credits ) ? '' : '<script>
      function start() {
        startScroll( \'creds\', \'' . $data . '\' );
      }
    </script>
    <script src="includes/js/v_h_scrolls.js"></script>
' ) . '<link href="includes/css/about.css" rel="stylesheet">',
  '', true, false, true );
echo '    <div id="creds">' . ( empty( $credits ) ? '
      <a title="' . $PROGRAM_NAME . '" href="' . $PROGRAM_URL
    . '" target="_blank">
      <h2>' . translate( 'Title' ) . '</h2>
      <p>' . str_replace( 'XXX', translate( $PROGRAM_VERSION , false, 'N' ),
        translate( 'version XXX' ) ) . '<br>'
    . translate( $PROGRAM_DATE, false, 'D' ) . '</p></a>
      <br>
      <p>' . translate( 'WebCalendar is a PHP application used...' ) . '</p>' : '' ) . '
    </div><br>
    <form action="about.php" name="aboutform" id="aboutform" method="post">
      <input type="submit" name="' . ( empty( $credits )
  ? 'Credits" value="' . translate( 'Credits' )
  : 'About" value="' . translate( 'About' ) ) . '">
      <input type="button" id="ok" name="ok" value="' . translate( 'OK' )
 . '" onclick="window.close()">
    </form>
    ' . print_trailer( false, true, true );

ob_end_flush();

?>
