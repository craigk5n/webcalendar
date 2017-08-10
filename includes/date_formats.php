<?php
/**
 * This file contains the date formats that are used within
 * admin.php and pref.php to populate the 'Date format' selects.
 *
 * <b>Note:</b>
 * PLEASE EDIT THIS FILE TO ADD ANY ADDITIONAL FORMATS REQUIRED.
 *  valid codes example
 *    __month__ = September
 *    __mon__   = Sep
 *    __mm__    = 09 (Number of month with leading zero.)
 *
 *    __dd__    = 04 (Date with leading zero.)
 *    __j__     = 4  (Date without leading zero.)
 *
 *    __yy__    = 07   (2 digit year.)
 *    __yyyy__  = 2007 (4 digit year.)
 *
 *
 * @author Ray Jones < rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @package WebCalendar
 */

// This will force $LANGUAGE to the current value
// and eliminate having to double click the 'SAVE' button.
reset_language ( get_pref_setting ( $login, 'LANGUAGE' ) );
define_languages(); // Load the language list.
reset ( $languages );

$DecemberStr = translate ( 'December' );
$DecStr = translate ( 'Dec' );

$datestyles = $datestyles_md = $datestyles_my = $datestyles_task =
array ( 'LANGUAGE_DEFINED', translate ( 'LANGUAGE DEFINED' ) );

// Day Month Year format
$datestyles += [
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
  '__yyyy__-__mm__-__dd__', '2000-12-31'];

// Month Year format
$datestyles_my += [
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
  '__yyyy__-__mm__', '2000-12'];

// Month Day format
$datestyles_md += [
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
  '__mm__-__dd__', '12-31'];

// Task Date format
$datestyles_task += [
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
  '__mm__/__dd__/__yyyy__', '12/31/2000'];

?>
