<?php
// $Id: about.php,v 1.20.2.1 2012/02/28 15:43:09 cknudsen Exp $
include_once 'includes/init.php';
require_valid_referring_url ();

$credits = getPostValue( 'Credits' );
static $data;

if ( empty( $data ) ) {
  //  Read in and format AUTHORS file.
  $data = preg_replace (
    ["/\n|\r\n/", '/\sXX*<.+>+/' ],
    ['<br />', ""],
  file_get_contents ( 'AUTHORS' ) );
}
print_header( '', ( empty( $credits ) ? '' : '<script type="text/javascript">
      function start() {
        startScroll( \'creds\', \'' . $data . '\' );
      }
    </script>
    <script type="text/javascript" src="includes/js/v_h_scrolls.js?'
  . filemtime( 'includes/js/v_h_scrolls.js' ) . '"></script>
' ) . '<link type="text/css" href="includes/css/about.css?'
  . filemtime( 'includes/css/about.css' ) . '" rel="stylesheet" />',
  '', true, false, true );
echo '    <div id="creds">' . ( empty( $credits ) ? '
      <a title="' . $PROGRAM_NAME . '" href="' . $PROGRAM_URL
    . '" target="_blank">
      <h2>' . translate( 'Title' ) . '</h2>
      <p>' . str_replace( 'XXX', $PROGRAM_VERSION,
        translate( 'version XXX' ) ) . '<br />' . $PROGRAM_DATE . '</p></a>
      <br />
      <p>' . translate( 'WebCalendar is a PHP application used...' ) . '</p>' : '' ) . '
    </div><br />
    <form action="about.php" name="aboutform" id="aboutform" method="post">
      <input type="submit" name="' . ( empty( $credits )
  ? 'Credits" value="' . translate( 'Credits' )
  : 'About" value="' . translate( 'About' ) ) . '" />
      <input type="button" id="ok" name="ok" value="' . translate( 'OK' )
 . '" onclick="window.close()" />
    </form>
    ' . print_trailer( false, true, true );

?>
