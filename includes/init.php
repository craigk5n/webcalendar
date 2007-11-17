<?php
/* Does various initialization tasks and includes all needed files.
 *
 * This page is included by most WebCalendar pages as the only include file.
 * This greatly simplifies the other PHP pages since they don't need to worry
 * about what files it includes.
 *
 * <b>Comments:</b>
 * The following scripts do not use this file:
 * - login.php
 * - week_ssi.php
 * - upcoming.php
 * - tools/send_reminders.php
 *
 * How to use:
 * 1. call include_once 'includes/init.php'; at the top of your script.
 * 2. call any other functions or includes not in this file that you need
 * 3. call the print_header function with proper arguments
 *
 * What gets called:
 *
 * - include_once 'includes/translate.php';
 * - require_once 'includes/classes/WebCalendar.class.php';
 * - require_once 'includes/classes/Event.class.php';
 * - require_once 'includes/classes/RptEvent.class.php';
 * - include_once 'includes/assert.php';
 * - include_once 'includes/config.php';
 * - include_once 'includes/formvars.php';
 * - include_once 'includes/dbi4php.php';
 * - include_once 'includes/functions.php';
 * - include_once 'includes/site_extras.php';
 * - include_once 'includes/access.php';
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */
if ( empty ( $_SERVER['PHP_SELF'] ) ||
    ( ! empty ( $_SERVER['PHP_SELF'] ) &&
      preg_match ( "/\/includes\//", $_SERVER['PHP_SELF'] ) ) )
  die ( 'You cannot access this file directly!' );

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class.php';
require_once 'includes/classes/Event.class.php';
require_once 'includes/classes/RptEvent.class.php';

$WC =& new WebCalendar ( __FILE__ );

include_once 'includes/assert.php';
include_once 'includes/config.php';
include_once 'includes/dbi4php.php';
include_once 'includes/formvars.php';
include_once 'includes/functions.php';

$WC->initializeFirstPhase();

include_once 'includes/site_extras.php';
include_once 'includes/access.php';
include_once 'includes/header.php';

require_once 'includes/classes/WebCalSmarty.class.php';
$smarty = new WebCalSmarty ( $WC );
$smarty->register_prefilter('template_translate');
//$smarty->load_filter('pre','translate');

$WC->initializeSecondPhase();

//Make sure we can visit this page
if ( ! access_can_view_page () )
 send_to_preferred_view ();
	
?>

