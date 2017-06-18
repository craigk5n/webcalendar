<?php
/* WebCalendar's functions to retrieve Predefined Variables
 *
 * See http://www.php.net/manual/en/reserved.variables.php
 * for a complete description and examples
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: formvars.php,v 1.1.2.5 2013/01/24 21:10:21 cknudsen Exp $
 * @package WebCalendar
 */



/**
  * This function examines the data for a form POST or GET to check
  * for malicious hacks.  If one is found, we just exit since this
  * should not happen with normal use.
  */
function preventHacking ( $name, $instr ) {
  $bannedTags = array (
    'APPLET', 'BODY', 'EMBED', 'FORM', 'HEAD',
    'HTML', 'IFRAME', 'LINK', 'META', 'NOEMBED',
    'NOFRAMES', 'NOSCRIPT', 'OBJECT', 'SCRIPT',
    );
  $failed = false;

  if ( is_array ( $instr ) ) {
    for ( $j = 0; $j < count ( $instr ); $j++ ) {
      for ( $i = 0; $i < count ( $bannedTags ) && ! $failed; $i++ ) {
        // First, replace any escape characters like '\x3c'
        $teststr = preg_replace ( "#(\\\x[0-9A-F]{2})#e",
          "chr(hexdec('\\1'))", $instr[$j] );
        if ( preg_match ( "/<\s*$bannedTags[$i]/i", $teststr ) ) {
          $failed = true;
        }
      }
    }
    if ( $failed ) {
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid data format for' ) . ' ' . $name );
    }
  } else {
    // Not an array
    // First, replace any escape characters like '\x3c'
    $teststr = preg_replace ( "#(\\\x[0-9A-F]{2})#e",
      "chr(hexdec('\\1'))", $instr );
    for ( $i = 0; $i < count ( $bannedTags ) && ! $failed; $i++ ) {
      if ( preg_match ( "/<\s*$bannedTags[$i]/i", $teststr ) ) {
        $failed = true;
      }
    }
    if ( $failed ) {
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid data format for' ) . ' ' . $name );
    }
  }
}

/* Gets the value resulting from an HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * @param string $name Name used in the HTML form
 *
 * @return string The value used in the HTML form
 *
 * @see getGetValue
 */
function getPostValue ( $name ) {
  $postName = null;
  if ( isset ( $_POST ) && is_array ( $_POST ) && isset ( $_POST[$name] ) )
    $postName = ( get_magic_quotes_gpc () != 0
      ? $_POST[$name] : (is_array ( $_POST[$name] ) 
			? array_map ( 'addslashes',  
			$_POST[$name] ): addslashes ( $_POST[$name] ) ) );

  preventHacking ( $name, $postName );
  return $postName;
}

/* Gets the value resulting from an HTTP GET method.
 *
 * Since this function is used in more than one place, with different names,
 * let's make it a separate 'include' file on it's own.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * If you need to enforce a specific input format (such as numeric input), then
 * use the {@link getValue ()} function.
 *
 * @param string  $name  Name used in the HTML form or found in the URL
 *
 * @return string        The value used in the HTML form (or URL)
 *
 * @see getPostValue
 */
function getGetValue ( $name ) {
  $getName = null;
  if ( isset ( $_GET ) && is_array ( $_GET ) && isset ( $_GET[$name] ) )
    $getName = ( get_magic_quotes_gpc () != 0
      ? $_GET[$name] : addslashes ( $_GET[$name] ) );
  preventHacking ( $name, $getName );
  return $getName;
}

/* Gets the value resulting from either HTTP GET method or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * <b>Note:</b> If you need to get an integer value, you can use the
 * getIntValue function.
 *
 * @param string $name   Name used in the HTML form or found in the URL
 * @param string $format A regular expression format that the input must match.
 *                       If the input does not match, an empty string is
 *                       returned and a warning is sent to the browser. If The
 *                       <var>$fatal</var> parameter is true, then execution
 *                       will also stop when the input does not match the
 *                       format.
 * @param bool   $fatal  Is it considered a fatal error requiring execution to
 *                       stop if the value retrieved does not match the format
 *                       regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getGetValue
 * @uses getPostValue
 */
function getValue ( $name, $format = '', $fatal = false ) {
  global $settings;

  $val = getPostValue ( $name );
  if ( ! isset ( $val ) )
    $val = getGetValue ( $name );
  // for older PHP versions...
  if ( ! isset ( $val ) && get_magic_quotes_gpc () == 1 && !
      empty ( $GLOBALS[$name] ) )
    $val = $GLOBALS[$name];
  if ( ! isset ( $val ) )
    return '';
  if ( ! empty ( $format ) && ! preg_match ( '/^' . $format . '$/', $val ) ) {
    // does not match
    if ( $fatal ) {
      if ( $settings['mode'] == 'dev' )
        $error_str = ' "' . $val . '"';
      else
        $error_str = '';
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid data format for' ) . ' ' . $name . $error_str );
    }
    // ignore value
    return '';
  }
  preventHacking ( $name, $val );
  return $val;
}

/* Gets an integer value resulting from an HTTP GET or HTTP POST method.
 *
 * <b>Note:</b> The return value will be affected by the value of
 * <var>magic_quotes_gpc</var> in the php.ini file.
 *
 * @param string $name  Name used in the HTML form or found in the URL
 * @param bool   $fatal Is it considered a fatal error requiring execution to
 *                      stop if the value retrieved does not match the format
 *                      regular expression?
 *
 * @return string The value used in the HTML form (or URL)
 *
 * @uses getValue
 */
function getIntValue ( $name, $fatal = false ) {
  return getValue ( $name, '-?[0-9]+', $fatal );
}

?>
