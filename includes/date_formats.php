<?php
/**
 * This file contains the date formats that are used within 
 * admin.php and pref.php to populate the 'Date forma' selects
 * 
 *
 * <b>Note:</b>
 * PLEASE EDIT THIS FILE TO ADD ANY ADDITIONAL FORMATS REQUIRED
 *  alid  codes   example
 *    __month__ = December
 *    __mon__   = Dec
 *    __dd__    = 31 (leading zero)
 *    __j__     = 31 ( noleading zero)
 *    __yyyy__  = 2005
 *    __yy__    = 05
 * 
 *
 * @author Ray Jones < rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: 
 * @package WebCalendar
 */
//Day Month Year format
$datestyles = array (
 "LANGUAGE_DEFINED", translate("LANGUAGE DEFINED"),
 "__month__ __dd__, __yyyy__", translate("December") . " 31, 2000",
 "__mon__ __j__, __yyyy__", translate("Dec") . " 5, 2000",
 "__dd__ __month__, __yyyy__", "31 " . translate("December") . ", 2000",
 "__dd__ __month__ __yyyy__", "31 " . translate("December") . " 2000",
 "__dd__-__month__-__yyyy__", "31-" . translate("December") . "-2000",
 "__dd__ __month__ __yy__", "31 " . translate("December") . " 00",
 "__dd__-__month__-__yy__", "31-" . translate("December") . "-00",
 "__dd__. __month__ __yyyy__", "31. " . translate("December") . " 2000",
 "__dd__. __month__ __yy__", "31." . translate("December") . " 00",
 "__mm__/__dd__/__yyyy__", "12/31/2000",
 "__mm__/__dd__/__yy__", "12/31/00",
 "__mm__-__dd__-__yyyy__", "12-31-2000",
 "__mm__-__dd__-__yy__", "12-31-00",
 "__yyyy__-__mm__-__dd__", "2000-12-31",
 "__yy__-__mm__-__dd__", "00-12-31",
 "__yyyy__/__mm__/__dd__", "2000/12/31",
 "__yy__/__mm__/__dd__", "00/12/31",
 "__dd__/__mm__/__yyyy__", "31/12/2000",
 "__dd__/__mm__/__yy__", "31/12/00",
 "__dd__-__mm__-__yyyy__", "31-12-2000",
 "__dd__-__mm__-__yy__", "31-12-00",
 "__dd__.__mm__.__yyyy__", "31.12.2000",
 "__dd__.__mm__.__yy__", "31.12.00"
);
//Month Year format
$datestyles_my = array (
 "LANGUAGE_DEFINED", translate("LANGUAGE DEFINED"),    
 "__month__ __yyyy__", translate("December") . " 2000",
 "__mon__ __yyyy__", translate("Dec") . " 2000",
 "__month__ __yy__", translate("December") . " 00",
 "__month__-__yyyy__", translate("December") . "-2000",
 "__month__-__yy__", translate("December") . "-00",
 "__mm__/__yyyy__", "12/2000",
 "__mm__/__yy__", "12/00",
 "__mm__-__yyyy__", "12-2000",
 "__mm__-__yy__", "12-00",
 "__mm__.__yyyy__", "12.2000",
 "__mm__.__yy__", "12.00",
 "__yyyy__-__mm__", "2000-12",
 "__yy__-__mm__", "00-12",
 "__yyyy__/__mm__", "2000/12",
 "__yy__/__mm__", "00/12"
);
//Month Day format
$datestyles_md = array (
 "LANGUAGE_DEFINED", translate("LANGUAGE DEFINED"),    
 "__month__ __dd__", translate("December") . " 31",
 "__mon__ __dd__", translate("Dec") . " 31",
 "__month__-__dd__", translate("December") . "-31",
 "__dd__ __month__", "31 " . translate("December"),
 "__dd__. __month__", "31. " . translate("December"),
 "__mm__/__dd__", "12/31",
 "__mm__-__dd__", "12-31",
 "__dd__/__mm__", "31/12",
 "__dd__-__mm__", "31-12",
 "__dd__.__mm__", "31.12"
);   
?>