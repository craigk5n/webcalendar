<?php
/**
 * About WebCalendar
 *
 * Acknowledge contributors.
 *
 * @package WebCalendar
 */
/**
 * Include the basics.
 *
 * // Required to end the page docBlock without getting errors..
 */
require_once 'includes/init.php';

$credits = getPostValue ( 'Credits' );
static $data;

if ( empty ( $data ) ) {
  // Read in and format AUTHORS file.
  $data = file_get_contents ( 'AUTHORS' );
  $patterns = $replacements = [];
  $patterns[] = "/\r\n|\n/";
  $replacements[] = '<br>';
  // Strip email addresses out
  $patterns[] = "<\S*@\S*>";
  $replacements[] = '';
  $data = preg_replace ( $patterns, $replacements, $data );
}
print_header ( ['css/about.css'], '', '', true, false, true );
echo '
    <div id="creds">'
  . ( empty ( $credits ) ? '
      <a href="' . $PROGRAM_URL
    . '" target="_blank" title="' . $PROGRAM_NAME . '">
      <h2>' . translate ( 'Title' ) . '</h2>
      <p>' . str_replace ( 'XXX', $PROGRAM_VERSION,
        translate ( 'version XXX' ) ) . '<br>' . $PROGRAM_DATE . '</p></a>
      <br>
      <p>' . translate ( 'WebCalendar is a PHP application used...' ) . '</p>' : '' ) . '
    </div>
    <form id="aboutform" name="aboutform" action="about.php" method="post">';
print_form_key();
echo '
      <button type="submit" name="'
  . ( empty ( $credits )
    ? 'Credits">' . translate ( 'Credits' )
    : 'About">' . translate ( 'About' ) )
  . '</button>
      <button id="ok" name="ok" type="button" onclick="window.close()">'
  . translate ( 'OK' ) . '</button>
    </form>'
  . ( empty ( $credits ) ? '' : "
    <script src=\"includes/js/v_h_scrolls.js\"></script>
    <script>
      function start() {
        startScroll('creds', '$data');
      }
    </script>
" ) . print_trailer ( false,true,true );
?>
