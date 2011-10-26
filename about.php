<?php
// $Id$
include_once 'includes/init.php';

$credits = getPostValue( 'Credits' );
static $data;

if( empty( $data ) ) {
  //  Read in and format AUTHORS file.
  if( $fd = @fopen( 'AUTHORS', 'r' ) ) {
    while( ! feof( $fd ) && empty( $error ) ) {
      $data .= fgets( $fd );
    }
    fclose( $fd );
  }
  $data = preg_replace( '/<.+>+/', '', $data );
  $data = preg_replace( "/\n\s/", '<br>&nbsp;', $data );
  $data = preg_replace( '/\s\s+/', '&nbsp;&nbsp;', $data );
  $data = preg_replace( "/\n/", '<br>', $data );
}

ob_start();
print_header( '', '<script>
      // $data is too big for a cookie. Let's see if HTML5 works.
      if (Modernizr.localstorage) {
        localStorage[data] = ' . $data . ';
      } else {
        var data = ' . $data . ';          
      }
    </script>', '', true, false, true );
echo '    <div id="creds">' . ( empty( $credits ) ? '
      <a href="' . $PROGRAM_URL . '">
      <h2>' . translate( 'Title' ) . '</h2>
      <p>' . str_replace( 'XXX',
        translate( substr( $PROGRAM_VERSION, 1 ), false, 'N' ),
        translate( 'version XXX' ) ) . '<br>'
    . translate( $PROGRAM_DATE, false, 'D' ) . '</p></a><br>
      <p>' . translate( 'WebCalendar a PHP app' ) . '</p>' : '' ) . '
    </div><br>
    <form action="about.php" method="post" id="aboutform" name="aboutform">
      <input type="submit" name="' . ( empty( $credits )
  ? 'Credits" value="' . translate( 'Credits' )
  : 'About" value="' . translate( 'About' ) ) . '">
      <input type="button" id="ok" name="ok" value="' . $okStr . '">
    </form>' . print_trailer( false, true, true );

ob_end_flush();

?>
