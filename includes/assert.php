<?php
/**
 * This file implements the assertion handler this is called anytime
 * a WebCalendar call to assert() fails.
 *
 * @todo Create a link that will pass all the bug details to a form hosted on
 *       k5n.us so that it can be easily submitted.
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */


/**
 * Gets the CVS file version for a specific file.
 *
 * Searches through the file and looks for the CVS Id tag.
 *
 * @param string $file Filename
 *
 * @return string File's CVS version string
 */
function assert_get_cvs_file_version ( $file )
{
  $version = "v?.?";
  $path = array ( "", "includes/", "../" );

  for ( $i = 0; $i < count ( $path ); $i++ ) {
    $newfile = $path[$i] . $file;
    if ( file_exists ( $newfile ) ) {
      $fd = @fopen ( $newfile, "rb", false );
      if ( $fd ) {
        while ( ! feof ( $fd ) ) {
          $data = fgets ( $fd, 1024 );
          if ( preg_match ( "/Id: (\S+),v (\d\S+)/", $data, $match ) ) {
            $version = "v" . $match[2];
            break;
          }
        }
        fclose ( $fd );
        break;
      }
    }
  }
  return $version;
}

/**
 * Return a backtrace.
 *
 * Each entry is separated by a newline. This function requires PHP 4.3/5.0.
 *
 * @return string Backtrace
 */
function assert_backtrace ()
{
  global $settings;

  if ( empty ( $settings ) || empty ( $settings['mode'] ) ||
    $settings['mode'] == 'prod' ) {
    return "No stack trace [production mode]";
  }

  if ( ! function_exists ( "debug_backtrace" ) )
    return "[stacktrack requires PHP 4.3/5.0.  " .
      "Not available in PHP " . phpversion() . "]";
  $bt = debug_backtrace ();
  //print_r ( $bt );
  $file = array ();
  for ( $i = 0; $i < count ( $bt ); $i++ ) {
    // skip the first two, since it's always this func and assert_handler
    if ( $i < 2 )
      continue;
    $afile = $bt[$i];
    
    $line = basename ( $afile['file'] ) . ':' . $afile['line'];
    $line .= ' [' . assert_get_cvs_file_version ( $afile['file'] ) . ']';
    if ( ! empty ( $afile['function'] ) ) {
      $line .= ' ' . $afile['function'] . ' ( ';
      for ( $j = 0; $j < count ( $afile['args'] ); $j++) {
        if ( $j ) $line .= ', ';
        $v = $afile['args'][$j];
        if ( is_null ( $v ) )
          $line .= 'null';
        else if ( is_array ( $v ) )
          $line .= 'Array[' . sizeof ( $v ) . ']';
        else if ( is_object ( $v ) )
          $line .= 'Object:' . get_class ( $v );
        else if ( is_bool ( $v ) )
          $line .= $v ? 'true' : 'false';
        else {
          $line .= '"';
          $v = (string) @$v;
          $line .= htmlspecialchars ( substr ( $v, 0, 40 ) );
          if ( strlen ( $v ) > 40 )
            $line .= '...';
          $line .= '"';
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
 * @param string $script Pathname where assertion failed
 * @param int    $line   Line number where assertion failed
 * @param string $msg    Failed assertion expression
 */
function assert_handler ( $script, $line, $msg )
{
  if ( empty ( $msg ) )
    $msg = "Assertion failed<br />\n";
  if ( function_exists ( "debug_backtrace" ) )
    $trace = assert_backtrace ();
  else
    $trace = basename ( $script ) . ":" . $line . " " . $msg;
  $msg .= "<b>Stack Trace:</b><br /><br /><blockquote><tt>\n" .
    nl2br ( $trace ) .
    "\n</tt></blockquote>\n";
  if ( function_exists ( "die_miserable_death" ) ) {
    die_miserable_death ( $msg );
  } else {
    print "<html><head><title>WebCalendar Error</title></head>\n" .
      "<body><h2>WebCalendar Error</h2><p>" . $Msg . "</p></body></html>\n";
    exit;
  }
}


?>
