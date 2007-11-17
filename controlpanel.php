<?php
/* $Id$
 *
 * Description:
 * This page generates the JNLP contents
 * that Java Web Start uses to start the application.
 *
 * For more info on Java Web Start:
 *  http://www.java.com/en/download/faq/5000070700.xml
 *
 * This starts up the us.k5n.webcalendar.ui.ControlPanel.Main application.
 * The ControlPanel application may eventually take over as the
 * primary way of administering parts of WebCalendar.
 *
 * Security:
 * This page doesn't really need securing since it just passes info to the
 * web start app. The web start app then does its own authenticating since
 * the web services require authentication to do anything.
 *
 **************************************************************************/

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class.php';
require_once 'includes/classes/Event.class.php';
require_once 'includes/classes/RptEvent.class.php';

$WC =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WC->initializeFirstPhase ();

$WC->initializeSecondPhase ();


$WC->setLanguage ();

// Set content type for java web start
header ( "Content-type: application/x-java-jnlp-file" );

// Make sure app name is set
$appStr = generate_application_name ();
$server_url = getPref ( 'SERVER_URL', 2 );

echo '<?xml version="1.0" encoding="utf-8"?>
<jnlp
  spec="1.0+"
  codebase="' . $server_url . '"
  href="controlpanel.php">
  <information>
    <title>' . translate ( 'Control Panel' ) . ': ' . htmlentities ( $appStr );

?></title>
    <vendor>k5n.us</vendor>
    <homepage href="http://www.k5n.us"/>
    <description>WebCalendar Control Panel</description>
    <!-- <icon href="images/xxx.gif"/> -->
  </information>
  <security>
  </security>
  <resources>
    <j2se version="1.4+"/>
    <jar href="ws/webcalendar.jar"/>
  </resources>
  <application-desc main-class="us.k5n.webcalendar.ui.ControlPanel.Main"
   width="600" height="500">
    <argument>-url=<?php echo $server_url . '</argument>'
 . ( _WC_HTTP_AUTH ? '
    <argument>-httpusername=' . $WC->loginId() . '</argument>'
  : ( $WC->loginId() ? '
    <argument>-user=' . $WC->loginId() . '</argument>' : '' ) )

?>
  </application-desc>
</jnlp>
