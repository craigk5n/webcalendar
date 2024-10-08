<?php
/**
 * Implements the assertion handler.
 *
 * This is called anytime a WebCalendar call to assert() fails.
 *
 * @todo Create a link that will pass all the bug details to a form
 *       hosted on k5n.us so that it can be easily submitted.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://k5n.us/webcalendar
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 *
 * @package WebCalendar
 */

/**
 * Setup callback function only if $settings.php mode == dev
 */
if ( ! empty ( $run_mode ) && $run_mode == 'dev' )
  assert_options( ASSERT_CALLBACK, 'assert_handler');

/**
 * Return a backtrace.
 *
 * Each entry is separated by a newline. This function requires PHP 4.3/5.0.
 *
 * @return string Backtrace
 */
function assert_backtrace() {
  global $settings;

  if ( empty ( $settings ) ||
      empty ( $settings['mode'] ) || $settings['mode'] == 'prod' )
    return 'No stack trace [production mode]';

  if ( ! function_exists ( 'debug_backtrace' ) )
    return '[stacktrack requires PHP 4.3/5.0. Not available in PHP '
     . phpversion() . ']';
  $bt = debug_backtrace();

  $file = [];
  for ( $i = 2, $cnt = count ( $bt ); $i < $cnt; $i++ ) {
    // skip the first two, since it's always this func and assert_handler
    $afile = $bt[$i];

    $line = basename ( $afile['file'] ) . ':' . $afile['line'];

    if ( ! empty ( $afile['function'] ) ) {
      $line .= ' ' . $afile['function'] . ' ( ';
      for ( $j = 0, $cnt_args = count ( $afile['args'] ); $j < $cnt_args; $j++ ) {
        if ( $j )
          $line .= ', ';
        $v = $afile['args'][$j];
        if ( is_null ( $v ) )
          $line .= 'null';
        else
        if ( is_array ( $v ) )
          $line .= 'Array[' . sizeof ( $v ) . ']';
        else
        if ( is_object ( $v ) )
          $line .= 'Object:' . get_class ( $v );
        else
        if ( is_bool ( $v ) )
          $line .= $v ? 'true' : 'false';
        else {
          $v = ( string ) @$v;
          $line .= '"' . htmlspecialchars ( substr ( $v, 0, 40 ) )
           . ( strlen ( $v ) > 40 ? '...' : '' ) . '"';
        }
      }
      $line .= ' )';
    }
    $out[] = $line;
  }
  return implode ( "\n", $out );
}

/**
 * Report an assertion failure.
 *
 * Abort execution, print the specified error message along with a stack trace.
 *
 * @param  string  $script  Pathname where assertion failed
 * @param  int     $line    number where assertion failed
 * @param  string  $msg     Failed assertion expression
 */
function assert_handler ( $script, $line, $msg='' ) {
  if ( empty ( $msg ) )
    $msg = "Assertion failed<br>\n";
  $trace = ( function_exists ( 'debug_backtrace' )
    ? assert_backtrace() : basename( $script ) . ': ' . $line . ' ' . $msg );
  $msg .= ( function_exists ( 'debug_backtrace' ) ? '<b>Stack Trace:</b><br><br>' : '' )
    . '<blockquote class="tt">' . nl2br ( $trace ) . '</blockquote>';
  if ( function_exists ( 'die_miserable_death' ) )
    die_miserable_death ( $msg );
  else {
    echo '<html><head><title>WebCalendar Error</title></head>
  <body><h2>WebCalendar Error</h2><p>' . $msg . '</p></body></html>
';
    exit;
  }
}

?>
