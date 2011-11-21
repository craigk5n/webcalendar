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
 *    __yy__    = 11   (2 digit year.)
 *    __yyyy__  = 2011 (4 digit year.)
 *
 *
 * @author Ray Jones < rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 */

// I would like to eliminate this whole file.
// Just have input text boxes for users to type in their desired formats.

// These translates will be moved when I find a better place for them. bb
// translate( 'DATE_FORMAT' ) translate( 'DATE_FORMAT_MD' )
// translate( 'DATE_FORMAT_MY' ) translate( 'DATE_FORMAT_TASK' )
 
// This will force $LANGUAGE to the current value
// and eliminate having to double click the 'SAVE' button.
reset_language ( get_pref_setting ( $login, 'LANGUAGE' ) );
define_languages(); // Load the language list.
reset ( $languages );

$datestyles = $datestyles_md = $datestyles_my = $datestyles_task =
array( 'LANGUAGE_DEFINED' => translate( 'LANGUAGE DEFINED' ) );

// Day Month Year format
$datestyles += array (
  '__mon__ __j__, __yyyy__'   => translate( date( 'M j, Y' ), false, 'D' ),
  '__month__ __dd__, __yyyy__'=> translate( date( 'F d, Y' ), false, 'D' ),
  '__dd__ __month__ __yy__'   => translate( date( 'd F y' ), false, 'D' ),
  '__dd__ __month__ __yyyy__' => translate( date( 'd F Y' ), false, 'D' ),
  '__dd__ __month__, __yyyy__'=> translate( date( 'd F, Y' ), false, 'D' ),
  '__dd__. __month__ __yy__'  => translate( date( 'd, F y' ), false, 'D' ),
  '__dd__. __month__ __yyyy__'=> translate( date( 'd. F Y' ), false, 'D' ),
  '__dd__-__month__-__yy__'   => translate( date( 'd-F-y' ), false, 'D' ),
  '__dd__-__month__-__yyyy__' => translate( date( 'd-F-Y' ), false, 'D' ),
  '__dd__.__mm__.__yy__'      => translate( date( 'd.m.y' ), false, 'N' ),
  '__dd__.__mm__.__yyyy__'    => translate( date( 'd.m.Y' ), false, 'N' ),
  '__dd__/__mm__/__yy__'      => translate( date( 'd/m/y' ), false, 'N' ),
  '__dd__/__mm__/__yyyy__'    => translate( date( 'd/m/Y' ), false, 'N' ),
  '__dd__-__mm__-__yy__'      => translate( date( 'd-m-y' ), false, 'N' ),
  '__dd__-__mm__-__yyyy__'    => translate( date( 'd-m-Y' ), false, 'N' ),
  '__mm__/__dd__/__yy__'      => translate( date( 'm/d/y' ), false, 'N' ),
  '__mm__/__dd__/__yyyy__'    => translate( date( 'm/d/Y' ), false, 'N' ),
  '__mm__-__dd__-__yy__'      => translate( date( 'm-d-y' ), false, 'N' ),
  '__mm__-__dd__-__yyyy__'    => translate( date( 'm-d-Y' ), false, 'N' ),
  '__yy__/__mm__/__dd__'      => translate( date( 'y/m/d' ), false, 'N' ),
  '__yy__-__mm__-__dd__'      => translate( date( 'y-m-d' ), false, 'N' ),
  '__yyyy__/__mm__/__dd__'    => translate( date( 'Y/m/d' ), false, 'N' ),
  '__yyyy__-__mm__-__dd__'    => translate( date( 'Y-m-d' ), false, 'N' ),
  );

// Month Year format
$datestyles_my += array (
  '__mon__ __yyyy__'  => translate( date( 'M Y' ), false, 'D' ),
  '__month__ __yy__'  => translate( date( 'F y' ), false, 'D' ),
  '__month__ __yyyy__'=> translate( date( 'F Y' ), false, 'D' ),
  '__month__-__yy__'  => translate( date( 'F-y' ), false, 'D' ),
  '__month__-__yyyy__'=> translate( date( 'F-Y' ), false, 'D' ),
  '__mm__.__yy__'     => translate( date( 'm.y' ), false, 'N' ),
  '__mm__.__yyyy__'   => translate( date( 'm.Y' ), false, 'N' ),
  '__mm__/__yy__'     => translate( date( 'm/y' ), false, 'N' ),
  '__mm__/__yyyy__'   => translate( date( 'm/Y' ), false, 'N' ),
  '__mm__-__yy__'     => translate( date( 'm-y' ), false, 'N' ),
  '__mm__-__yyyy__'   => translate( date( 'm-Y' ), false, 'N' ),
  '__yy__/__mm__'     => translate( date( 'y/m' ), false, 'N' ),
  '__yy__-__mm__'     => translate( date( 'y-m' ), false, 'N' ),
  '__yyyy__/__mm__'   => translate( date( 'Y/m' ), false, 'N' ),
  '__yyyy__-__mm__'   => translate( date( 'Y-m' ), false, 'N' ),
  );

// Month Day format
$datestyles_md += array (
  '__mon__ __dd__'   => translate( date( 'M d' ), false, 'D' ),
  '__month__ __dd__' => translate( date( 'F d' ), false, 'D' ),
  '__month__-__dd__' => translate( date( 'F-d' ), false, 'D' ),
  '__dd__ __mon__'   => translate( date( 'd M' ), false, 'D' ),
  '__dd__ __month__' => translate( date( 'd F' ), false, 'D' ),
  '__dd__. __month__'=> translate( date( 'd. M' ), false, 'D' ),
  '__dd__.__mm__'    => translate( date( 'd.m' ), false, 'N' ),
  '__dd__/__mm__'    => translate( date( 'd/m' ), false, 'N' ),
  '__dd__-__mm__'    => translate( date( 'd-m' ), false, 'N' ),
  '__mm__/__dd__'    => translate( date( 'm/d' ), false, 'N' ),
  '__mm__-__dd__'    => translate( date( 'm-d' ), false, 'N' ),
  );

// Task Date format
$datestyles_task += array (
  '__mon__ __dd__'        => translate( date( 'M d' ), false, 'D' ),
  '__dd__ __mon__'        => translate( date( 'd M' ), false, 'D' ),
  '__dd__.__mm__'         => translate( date( 'd.m' ), false, 'N' ),
  '__dd__/__mm__'         => translate( date( 'd/m' ), false, 'N' ),
  '__dd__-__mm__'         => translate( date( 'd-m' ), false, 'N' ),
  '__mm__/__dd__'         => translate( date( 'm/d' ), false, 'N' ),
  '__mm__-__dd__'         => translate( date( 'm-d' ), false, 'N' ),
  '__dd__/__mm__/__yy__'  => translate( date( 'd/m/y' ), false, 'N' ),
  '__dd__-__mm__-__yy__'  => translate( date( 'd-m-y' ), false, 'N' ),
  '__mm__/__dd__/__yy__'  => translate( date( 'm/d/y' ), false, 'N' ),
  '__mm__/__dd__/__yyyy__'=> translate( date( 'm/d/Y' ), false, 'N' ),
  );

// These loops were combined and moved from "admin.php" and "pref.php".
$datestyle_md = $datestyle_my = $datestyle_tk = $datestyle_ymd = '';

foreach ( $datestyles as $k => $v ) {
  $datestyle_ymd .= $option . $k
   . ( $s['DATE_FORMAT'] == $k || $prefarray['DATE_FORMAT'] == $k
     ? '" selected>' : '">' ) . $v . '</option>';
}
foreach ( $datestyles_my as $k => $v ) {
  $datestyle_my .= $option . $k
   . ( $s['DATE_FORMAT_MY'] == $k ||  $prefarray['DATE_FORMAT_MY'] == $k
     ? '" selected>' : '">' ) . $v . '</option>';
}
foreach ( $datestyles_md as $k => $v ) {
  $datestyle_md .= $option . $k
   . ( $s['DATE_FORMAT_MD'] == $k ||  $prefarray['DATE_FORMAT_MD'] == $k
     ? '" selected>' : '">' ) . $v . '</option>';
}
foreach ( $datestyles_task as $k => $v ) {
  $datestyle_tk .= $option . $k
   . ( $s['DATE_FORMAT_TASK'] == $k || $prefarray['DATE_FORMAT_TASK'] == $k
     ? '" selected>' : '">' ) . $v . '</option>';
}

?>
