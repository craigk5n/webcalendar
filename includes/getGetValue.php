/**
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://k5n.us/webcalendar
 * @license https://gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL
 * @package WebCalendar
 */

/**
 * Gets the value resulting from an HTTP GET method.
*
* If you need to enforce a specific input format (such as numeric input),
* then use the {@link getValue()} function.
*
 * @param string $name used in the HTML form or found in the URL
 *
 * @return string The value used in the HTML form (or URL)
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
