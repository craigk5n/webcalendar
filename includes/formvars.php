<?php

/**
 * WebCalendar's functions to retrieve Predefined Variables
 *
 * See http://www.php.net/manual/en/reserved.variables.php
 * for a complete description and examples
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */


/**
  * This function examines the data for a form POST or GET to check
  * for malicious hacks.  If one is found, we just exit since this
  * should not happen with normal use.
  */
function preventHacking_helper($matches) {
  return chr(hexdec($matches[1]));
}
function preventHacking ( $name, $instr ) {
  global $PHP_SELF;
  $script = basename($PHP_SELF);

  $bannedTags = [
    'APPLET', 'BODY', 'EMBED', 'FORM', 'HEAD',
    'HTML', 'IFRAME', 'LINK', 'META', 'NOEMBED',
    'NOFRAMES', 'NOSCRIPT', 'OBJECT', 'SCRIPT'];
  $failed = false;

  // If this is a POST, require a form key to prevent CSRF
  // Assume all database db changes make use of POST or else
  // they end in "_handler.php" or are one of a handful of known URLs.
  if ($script == "login.php" || $script=="register.php" ||
    $script == "search_handler.php") {
    // No form token needed
  } else if ($_SERVER['REQUEST_METHOD'] === 'POST' ||
    ($_SERVER['REQUEST_METHOD'] === 'GET' &&
    ($script == 'del_entry.php' ||
    $script == 'add_entry.php' || $script == 'docdel.php' ||
    endsWith($script, "_handler.php")))) {
//echo "KEY CHECK <br>\n";
    $formKey = $_REQUEST['csrf_form_key'];
    if ($formKey == $_SESSION['csrf_form_key'] && !empty($_SESSION['csrf_form_key'])) {
      // Okay to proceed
//echo "FORM KEY: $formKey \n"; exit;
    } else {
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid form request' ) );
    }
  }
  //echo "METHOD " . $_SERVER['REQUEST_METHOD'] . "<br>";
  //echo "PHP_SELF " . $script . "<br>";
  //print_r ( $_SERVER );
  //echo "NO ERROR <br>\n"; exit;

  if ( is_array ( $instr ) ) {
    for ( $j = 0; $j < count ( $instr ); $j++ ) {
      // First, replace any escape characters like '\x3c'
      $teststr = preg_replace_callback("#(\\\x[0-9A-F]{2})#i",
                                       'preventHacking_helper', $instr[$j]);
      for ( $i = 0; $i < count ( $bannedTags ) && ! $failed; $i++ ) {
        if ( preg_match ( "/<\s*$bannedTags[$i]/i", $teststr ) ) {
          $failed = true;
        }
      }
    }
    if ( $failed ) {
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid data format for' ) . '&nbsp;' . $name .
         '<br>Value: ' . htmlspecialchars($instr));
    }
  } else {
    // Not an array
    // First, replace any escape characters like '\x3c'
    $teststr = preg_replace_callback("#(\\\x[0-9A-F]{2})#i",
                                     'preventHacking_helper', $instr);
    for ( $i = 0; $i < count ( $bannedTags ) && ! $failed; $i++ ) {
      if ( preg_match ( "/<\s*$bannedTags[$i]/i", $teststr ) ) {
        $failed = true;
      }
    }
    if ( $failed ) {
      die_miserable_death ( translate ( 'Fatal Error' ) . ': '
         . translate ( 'Invalid data format for' ) . ' ' . $name .
         '<br>Value: ' . htmlspecialchars($instr));
    }
  }
}

// Function to check the string is ends 
// with given substring or not
function endsWith($string, $endString)
{
  $len = strlen($endString);
  if ($len == 0) {
    return true;
  }
  return (substr($string, -$len) === $endString);
}

/**
  * Generate a key to include in HTML form to prevent CSRF.
  */
function getFormKey() {
  if (!isset($_SESSION['csrf_form_key'])) {
    $formKey = bin2hex(openssl_random_pseudo_bytes(32));
    $_SESSION['csrf_form_key'] = $formKey;
  } else {
    $formKey = $_SESSION['csrf_form_key'];
  }
  return $formKey;
}

function csrf_form_key() {
  return '<input type="hidden" name="csrf_form_key" value="' .
    getFormKey() . '" />' .  "\n";
}
function print_form_key() {
  echo csrf_form_key ();
}


/**
 * Gets the value resulting from an HTTP POST method.
 *
 * @param string $name Name used in the HTML form
 * @param string $defVal Value to return if form field is empty
 * @param string $chkXSS Switch to control XSS checking
 *
 * @return string The value used in the HTML form
 *
 * @see getGetValue
 */
function getPostValue($name, $defVal = NULL, $chkXSS = false)
{
  $postName = $defVal;
  if (isset($_POST) && is_array($_POST) && isset($_POST[$name])) {
    $postName =
      (is_array($_POST[$name]) ? array_map('addslashes', $_POST[$name]) :
        addslashes($_POST[$name]));
  }

  $cleanXSS = $chkXSS ? chkXSS($postName) : true;
  preventHacking($name, $postName);
  return $cleanXSS ? $postName : NULL;
}

/**
 * Gets the value resulting from an HTTP GET method.
 *
 * Since this function is used in more than one place, with different names,
 * let's make it a separate 'include' file on it's own.
 *
 * If you need to enforce a specific input format (such as numeric input), then
 * use the {@link getValue()} function.
 *
 * @param string  $name  Name used in the HTML form or found in the URL
 *
 * @return string        The value used in the HTML form (or URL)
 *
 * @see getPostValue
 */
function getGetValue($name, $devVal=NULL, $chkCSS=false)
{
  $getName = null;
  if (isset($_GET) && is_array($_GET) && isset($_GET[$name])) {
    $getName = is_array($_GET[$name]) ? array_map('addslashes', $_GET[$name]) :
      addslashes($_GET[$name]);
  }
  $cleanXSS = $chkCSS ? chkXSS($getName) : true;
  preventHacking($name, $getName);
  return $cleanXSS ? $getName : NULL;
}

/**
 * Gets the value resulting from either HTTP GET method or HTTP POST method.
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
function getValue($name, $format = '', $fatal = false)
{
  $val = getPostValue($name);
  if (!isset($val))
    $val = getGetValue($name);
  if (!isset($val))
    return '';
  if (!empty($format) && !preg_match('/^' . $format . '$/', $val)) {
    // does not match
    if ($fatal) {
      die_miserable_death(translate('Fatal Error') . ': '
      . translate('Invalid data format for') . $name .
        '<br>Value: ' . htmlspecialchars($val));
    }
    // ignore value
    return '';
  }
  preventHacking($name, $val);
  return $val;
}

/**
 * Gets an integer value resulting from an HTTP GET or HTTP POST method.
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
function getIntValue($name, $fatal = false) {
  return getValue($name, '-?[0-9]+', $fatal);
}

/**
 * Checks string for certain XSS attack strings.
 *
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
function chkXSS($name) {
  global $login;
  $cleanXSS = true;
    //add more array elements as needed
    foreach (array( 'Ajax.Request', 'onerror') as $i) {
      if (preg_match("/$i/i", $name)) {
        activity_log(0, $login, $login, SECURITY_VIOLATION,
                'Hijack attempt:' . $i);
        $cleanXSS = false;
      }
    }

  return $cleanXSS;
}

?>
