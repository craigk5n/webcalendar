<?php
/* This file contains the date formats that are used within
 * admin.php and pref.php to populate the 'Date format' selects.
 *
 * <b>Note:</b>
 * PLEASE EDIT THIS FILE TO ADD ANY ADDITIONAL FORMATS REQUIRED.
 *  valid codes example
 *    __month__ = December
 *    __mon__ = Dec
 *    __dd__ = 09 (date with leading zero)
 *    __j__ = 9 ( date without leading zero)
 *    __yyyy__ = 2005
 *    __yy__ = 05
 *
 *
 * @author Ray Jones < rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: date_formats.php,v 1.10.2.3 2012/02/20 01:29:20 cknudsen Exp $
 * @package WebCalendar
 */
// .
// This will force $LANGUAGE to the current value
// and eliminate having to double click the 'SAVE' button.
function_exists('reset_language') or die('You cannot access this file directly!');
reset_language ( get_pref_setting ( $login, 'LANGUAGE' ) );
define_languages (); // Load the language list.
reset ( $languages );

$DecemberStr = translate ( 'December' );
$DecStr = translate ( 'Dec' );
$langDefStr = translate ( 'LANGUAGE DEFINED' );
// .
// Day Month Year format
$datestyles = array ( 'LANGUAGE_DEFINED', $langDefStr,
  '__mon__ __j__, __yyyy__', $DecStr . ' 5, 2000',
  '__month__ __dd__, __yyyy__', $DecemberStr . ' 31, 2000',
  '__dd__ __month__ __yy__', '31 ' . $DecemberStr . ' 00',
  '__dd__ __month__ __yyyy__', '31 ' . $DecemberStr . ' 2000',
  '__dd__ __month__, __yyyy__', '31 ' . $DecemberStr . ', 2000',
  '__dd__. __month__ __yy__', '31.' . $DecemberStr . ' 00',
  '__dd__. __month__ __yyyy__', '31. ' . $DecemberStr . ' 2000',
  '__dd__-__month__-__yy__', '31-' . $DecemberStr . '-00',
  '__dd__-__month__-__yyyy__', '31-' . $DecemberStr . '-2000',
  '__dd__.__mm__.__yy__', '31.12.00',
  '__dd__.__mm__.__yyyy__', '31.12.2000',
  '__dd__/__mm__/__yy__', '31/12/00',
  '__dd__/__mm__/__yyyy__', '31/12/2000',
  '__dd__-__mm__-__yy__', '31-12-00',
  '__dd__-__mm__-__yyyy__', '31-12-2000',
  '__mm__/__dd__/__yy__', '12/31/00',
  '__mm__/__dd__/__yyyy__', '12/31/2000',
  '__mm__-__dd__-__yy__', '12-31-00',
  '__mm__-__dd__-__yyyy__', '12-31-2000',
  '__yy__/__mm__/__dd__', '00/12/31',
  '__yy__-__mm__-__dd__', '00-12-31',
  '__yyyy__/__mm__/__dd__', '2000/12/31',
  '__yyyy__-__mm__-__dd__', '2000-12-31',
  );
// .
// Month Year format
$datestyles_my = array ( 'LANGUAGE_DEFINED', $langDefStr,
  '__mon__ __yyyy__', $DecStr . ' 2000',
  '__month__ __yy__', $DecemberStr . ' 00',
  '__month__ __yyyy__', $DecemberStr . ' 2000',
  '__month__-__yy__', $DecemberStr . '-00',
  '__month__-__yyyy__', $DecemberStr . '-2000',
  '__mm__.__yy__', '12.00',
  '__mm__.__yyyy__', '12.2000',
  '__mm__/__yy__', '12/00',
  '__mm__/__yyyy__', '12/2000',
  '__mm__-__yy__', '12-00',
  '__mm__-__yyyy__', '12-2000',
  '__yy__/__mm__', '00/12',
  '__yy__-__mm__', '00-12',
  '__yyyy__/__mm__', '2000/12',
  '__yyyy__-__mm__', '2000-12',
  );
// .
// Month Day format
$datestyles_md = array ( 'LANGUAGE_DEFINED', $langDefStr,
  '__mon__ __dd__', $DecStr . ' 31',
  '__month__ __dd__', $DecemberStr . ' 31',
  '__month__-__dd__', $DecemberStr . '-31',
  '__dd__ __mon__', ' 31' . $DecStr,
  '__dd__ __month__', '31 ' . $DecemberStr,
  '__dd__. __month__', '31. ' . $DecemberStr,
  '__dd__.__mm__', '31.12',
  '__dd__/__mm__', '31/12',
  '__dd__-__mm__', '31-12',
  '__mm__/__dd__', '12/31',
  '__mm__-__dd__', '12-31',
  );
// .
// Task Date format
$datestyles_task = array ( 'LANGUAGE_DEFINED', $langDefStr,
  '__mon__ __dd__', $DecStr . ' 31',
  '__dd__ __mon__', ' 31' . $DecStr,
  '__dd__.__mm__', '31.12',
  '__dd__/__mm__', '31/12',
  '__dd__-__mm__', '31-12',
  '__mm__/__dd__', '12/31',
  '__mm__-__dd__', '12-31',
  '__dd__/__mm__/__yy__', '31/12/00',
  '__dd__-__mm__-__yy__', '31-12-00',
  '__mm__/__dd__/__yy__', '12/31/00',
  '__mm__/__dd__/__yyyy__', '12/31/2000',
  );

?>
