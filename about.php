<?php
include_once 'includes/init.php';

$credits = getPostValue( 'Credits' );
static $data;

if ( empty( $data ) ) {
  //  Read in and format AUTHORS file.
  $data = file_get_contents ( 'AUTHORS' );
  $patterns = array ();
  $replacements = array ();
  $patterns[0] = "/\r\n|\n/";
  $replacements[0] = "<br>";
  // Strip email addresses out
  $patterns[1] = "<\S*@\S*>";
  $replacements[1] = "";
  $data = preg_replace ( $patterns, $replacements, $data );
}
print_header ( [], '<link href="includes/css/about.css" rel="stylesheet" />',
  '', true, false, true );
echo '    <div id="creds">' . ( empty( $credits ) ? '
      <a title="' . $PROGRAM_NAME . '" href="' . $PROGRAM_URL
    . '" target="_blank">
      <h2>' . translate( 'Title' ) . '</h2>
      <p>' . str_replace( 'XXX', $PROGRAM_VERSION,
        translate( 'version XXX' ) ) . '<br />' . $PROGRAM_DATE . '</p></a>
      <br />
      <p>' . translate( 'WebCalendar is a PHP application used...' ) . '</p>' : '' ) . '
    </div>
    <form action="about.php" name="aboutform" id="aboutform" method="post">';
print_form_key();
echo '<input type="submit" name="' . ( empty( $credits )
  ? 'Credits" value="' . translate( 'Credits' )
  : 'About" value="' . translate( 'About' ) ) . '" />
      <input type="button" id="ok" name="ok" value="' . translate( 'OK' )
 . '" onclick="window.close()" />
    </form>' . ( empty ( $credits ) ? '' : "
    <script>
      function start() {
        startScroll('creds', '$data');
      }
    </script>
    <script src=\"includes/js/v_h_scrolls.js\"></script>
" ) . print_trailer ( false,true,true );
?>
