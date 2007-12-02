<?php
/* CSS styles used in WebCalendar
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id$
 * @package WebCalendar
 *
 *                         HOW TO READ THIS DOCUMENT
 *
 * There are two main parts to every CSS style: 'selector' & 'declaration'.
 *   EXAMPLE:
 *     body {
 *       color: red;
 *     }
 * The selector in the example above is 'body',
 * while its declaration is 'color: red;'.
 * Each declaration has two parts: 'property' & 'value'.
 *
 * In the example above, there is only one declaraion ("color: red;").
 * For that declaration, the PROPERTY is "color" and the VALUE is "red".
 *
 * NOTE: Each property must be followed by a colon (:),
 *       and each value must be followed by a semi-colon (;).
 *
 * Each selector can contain multiple declarations.
 *   EXAMPLE:
 *     body {
 *       background: black;
 *       color: red;
 *       font-size: 12px;
 *     }
 * In the example above, there are three declarations:
 *   background: black;
 *   color: red;
 *   font-size: 12px;
 *
 * NOTE: The declarations for a given style must be contained
 *       within curly brackets ({ }).
 *
 *                  PHP VARIABLES USED TO STYLE WEBCALENDAR
 *
 * BGCOLOR      - background-color for the page
 * CELLBG       - background-color for normal cells
 *                (not weekends, today, or any other types of cells)
 * FONTS        - default font-family
 * H2COLOR      - text color for text within h2 tags
 * MYEVENTS     - text color for users' events
 * OTHERMONTHBG - background-color for cells that belong to other month
 * POPUP_BG     - background-color for event popups
 * POPUP_FG     - text color for event popups
 * TABLEBG      - background-color for tables
 *                (typically used when the table also has cellspacing,
 *                thereby creating a border effect)
 * TEXTCOLOR    - default text color
 * THBG         - background-color for table headers
 * THFG         - text color for table headers
 * TODAYCELLBG  - background-color for cells that make up today's date
 * WEEKENDBG    - background-color for cells that make up the weekend
 *
 *           SOME OF THE CSS IDS AND CLASSES USED IN WEBCALENDAR
 *  #programname         - link to webcalendar site
 *                         NOTE: modifying this can make this link disappear
 *  #viewd .main th      - participants cell
 *  #year.minical        - contains ALL months
 *                         NOTE: display: block; here
 *                         keeps the caption vertically close to the day names
 *  .dayofmonth          - numerical date links in main calendars
 *  .dayofmonth:hover    - numerical date links in main calendars on hover
 *  .entry               - links to entries/events
 *  .entry img
 *  .layerentry img
 *  .unapprovedentry img - event (or bullet) icon;
 *                         NOTE: must be defined AFTER the .entry, .layerentry,
 *                         and .unapprovedentry classes.
 *  .layerentry          - links to entries/events on layers
 *  .main                - most display pages use this for calendar content
 *  .main td.hasevents   - only use HASEVENTSBG if it differs from CELLBG
 *  .minical             - styles for minicalendars
 *  .minical caption     - really big number above the minicalendar in day.php
 *  .minical th          - formats the day name (i.e. Sun, Mon, etc) in minicals
 *  .minitask            - table appearing in small task window
 *  .new                 - new event icon (i.e. '+' symbol)
 *  .next img            - right arrow images
 *  .prev img            - left arrow images
 *  .printer             - printer-friendly links
 *  .standard            - standard table mainly in prefs.php & admin.php
 *  .unapprovedentry     - links to unapproved entries/events
 *  .weeknumber          - week number in monthview & such
 *  a                    - links that don't have a specific class must be
 *                         defined BEFORE all other link-related classes for
 *                         those classes to work.
 *  img.color            - transparent images used for visual color-selection
 *  img.help             - question mark img linking to help sections
 *
 * display: none; is unhidden by includes/print_styles.css
 * for printer-friendly pages and where else needed.
 *
 *                            PHP FUNCTION CALLS
 * A special function, background_css(), will allow the dynamic creation of
 * gradient images to be used for the background of that selector. The image
 * file will be created and cached (if enabled) for faster processing and the
 * url will be returned for inclusion into the final CSS file.
 *   Example: background_css ( $CELLBG, 50 );
 *   Yields : background: #FFFFFF url( cache/images/FFFFFF-50.png ) repeat-x;
 *
 *                          CSS CACHING AND VIEWING
 * A caching scheme has been implemented to improve performance and reduce
 * download payloads. The resulting file will be stored in cache/css 
 * with a filename created by md5 ( user id )
 *
 * The resulting file will contain the color and layout preferences for the
 * logged in user or the default values if not logged in.
 *
 * Each page in WebCalendar is assigned a unique ID. This unique ID is
 * determined by taking the name of the page & removing any underscores (_).
 *   Example: edit_entry.php
 *   Results: <body id="editentry">
 */

//include gradient.pho is not already loaded
include_once 'includes/gradient.php';
//do_debug ( "RUNNING STYLES.PHP " . date ( 'His' ) );

$COLORS_FROM_CONFIG = ( _WC_SCRIPT == 'admin.php' ? 2 : 1 );

$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );

$H2COLOR = getPref ( 'H2COLOR', $COLORS_FROM_CONFIG );
$BGCOLOR = getPref ( 'BGCOLOR', $COLORS_FROM_CONFIG );
$THFG = getPref ( 'THFG', $COLORS_FROM_CONFIG );
$WEEKENDBG = getPref ( 'WEEKENDBG', $COLORS_FROM_CONFIG );
$THBG = getPref ( 'THBG', $COLORS_FROM_CONFIG );
$TABLEBG = getPref ( 'TABLEBG', $COLORS_FROM_CONFIG );
$CELLBG = getPref ( 'CELLBG', $COLORS_FROM_CONFIG );
$TODAYCELLBG = getPref ( 'TODAYCELLBG', $COLORS_FROM_CONFIG );
$TEXTCOLOR = getPref ( 'TEXTCOLOR', $COLORS_FROM_CONFIG );
$POPUP_FG = getPref ( 'POPUP_FG', $COLORS_FROM_CONFIG );
$POPUP_BG = getPref ( 'POPUP_BG', $COLORS_FROM_CONFIG );
$HASEVENTBG = getPref ( 'HASEVENTSBG', $COLORS_FROM_CONFIG );
$OTHERMONTHBG = getPref ( 'OTHERMONTHBG', $COLORS_FROM_CONFIG );
$MYEVENTS = getPref ( 'MYEVENTS', $COLORS_FROM_CONFIG );
$WEEKNUMBER = getPref ( 'WEEKNUMBER', $COLORS_FROM_CONFIG );
$CAPTIONS = getPref ( 'CAPTIONS', $COLORS_FROM_CONFIG );

?>
body {
  font-family: <?php echo getPref ( 'FONTS' );?>;
  background: <?php echo $BGCOLOR;?>;
}

h2,
.tabfor a,
.user,
.categories,
.asstmode,
#admin .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell,
#month .main td.weekcell,
#day .title .date,
.title .date,
.title .titleweek,
.title .viewname,
#day .title .user,
.title .user {
  color: <?php echo $H2COLOR;?>;
}
th,
#day .minical caption {
  color: <?php echo $THFG;?>;
  background: <?php echo $THBG;?>;
}
.main th {
  <?php echo background_css ( $THBG, 15 );?>
}
.main th.weekend {
  <?php echo background_css ( $THBG, 15 );
if ( ! $display_weekends ) {?>
 display: none;
<?php } ?>
}
.main th.today {
  <?php echo background_css ( $TODAYCELLBG, 15 );?>
}
.main td {
  <?php echo background_css ( $CELLBG, 100 );?>
}
.main td.weekend {
  <?php echo background_css ( $WEEKENDBG, 100 );
if ( ! $display_weekends ) { ?>
 display: none;
<?php } ?>
}
<?php if ( $HASEVENTBG != $CELLBG ) {?>
.main td.hasevents {
  <?php echo background_css ( $HASEVENTBG, 100 );?>
}
<?php } ?>
.main td.today {
  <?php echo background_css ( $TODAYCELLBG, 100 ); ?>
}
.main td.othermonth {
  <?php echo background_css ( $OTHERMONTHBG, 100 ); ?>
}
.admin,
.admin td a {
	background: <?php echo $CELLBG;?>;
}
.weeknumber,
.weeknumber a,
a.weekcell {
  color: <?php echo $WEEKNUMBER;?>;
}
.odd {
  background: <?php echo $TODAYCELLBG;?>;
}
.alt {
  background: <?php echo $CELLBG;?>;
}
.entry {
  color: <?php echo $MYEVENTS;?>;
}
.dayofmonth {
  color: <?php echo $TABLEBG;?>;
}
.dailymatrix {
  background: <?php echo $THBG;?>;
}
td.matrixappts {
  background: <?php echo $CELLBG;?>;
}
.popup {
  color: <?php echo $POPUP_FG;?>;
  <?php echo background_css ( $POPUP_BG, 200 );?>
  border: 1px solid <?php echo $POPUP_FG;?>;
}
.standard {
  border: 1px solid <?php echo $TABLEBG;?>;
  background: <?php echo $CELLBG;?>;
}
.minical caption a {
  color: <?php echo $CAPTIONS;?>;
}
.minical th {
  border: 0px solid <?php echo $BGCOLOR;?>;
}
.demotable,
.glance th.empty {
  background: <?php echo $BGCOLOR;?>;
}
.alt {
  background: <?php echo $CELLBG;?>;
}
<?php if ( ! $display_weekends ) {?>
.minical th.weekend {
  display: none;
}
<?php }?>
.minical td {
  border: 1px solid <?php echo $BGCOLOR;?>;
  background: <?php echo $CELLBG;?>;
}

.minical td.weekend {
  background: <?php echo $WEEKENDBG;?>;
<?php if ( ! $display_weekends ) {?>
  display: none;
<?php }?>
}
.minical td#today {
  background: <?php echo $TODAYCELLBG;?>;
}
.minitask tr.header th,
.minitask tr.header td {
  background: <?php echo $CELLBG;?>;
}
body,
a,
.printer,
.nav,
.minitask  td,
.task {
  color: <?php echo $TEXTCOLOR;?>;
}
.minical th.empty,
.minical td.empty {
  background: transparent;
}
#minicalendar table {
  width: <?php $minicalwidth = getPref ( 'MINICALWIDTH' );
  echo ( ! empty ( $minicalwidth ) ? $minicalwidth : '160px' );?>;
}
#minicalendar th,
#minicalendar td{
  font-size: <?php $minicalfont = getPref ( 'MINICALFONT' );
  echo ( ! empty ( $minicalfont )?$minicalfont : '11px' );?>;
}
.embactlog td,
.matrixd {
  border-left: 1px solid <?php echo $TABLEBG;?>;
}
#day .minical td.selectedday {
  border: 2px solid <?php echo $TABLEBG;?>;
}
.glance {
  border-bottom: 1px solid <?php echo $TABLEBG;?>;
}
.glance td {
  <?php echo background_css ( $CELLBG, 50 );?>
}
.main th,
.main td,
.embactlog th,
.glance th.row,
.glance td {
  border-top: 1px solid <?php echo $TABLEBG;?>;
  border-left: 1px solid <?php echo $TABLEBG;?>;
}
.main ,
.dayofmonth,
.standard th,
.embactlog,
.embactlog th
.glance,
.matrixd,
#viewd .main th {
  border-right: 1px solid <?php echo $TABLEBG;?>;
}
#admin .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell,
#month .main td.weekcell {
  <?php echo background_css ( $THBG, 50 );?>
}
.glance th.empty,
#viewv .main th.empty,
#viewm .main th.empty,
#vieww .main th.empty,
#viewr .main th.empty,
#week .main th.empty {
  border-top: 1px solid <?php echo $BGCOLOR;?>;
  border-left: 1px solid <?php echo $BGCOLOR;?>;
}
<?php if ( ! $display_weekends ) {?>
#viewt .main tr.weekend {
 display: none;
}
<?php }?>
#viewt .main th.weekend {
  <?php echo background_css ( $WEEKENDBG, 15 );?>
}
#viewr .main th a,
#week .main th a,
#weekdetails .main th a {
  color: <?php echo $THFG;?>;
}
<?php if ( ! getPref ( 'DISPLAY_TASKS' ) ) {?>
#month #prevmonth {
  float: left;
}
#month #nextmonth {
  color: <?php echo $H2COLOR;?>;
}
<?php }?>

#viewt td.entry {
  <?php echo background_css ( $THBG, 10 );?>
}
#register table,
#login table {
  border: 1px solid <?php echo $TABLEBG;?>;
  <?php echo background_css ( $CELLBG, 200 );?>
}
.layers {
  background: <?php echo $CELLBG ?>;
}
td.MainItemHover,td.MainItemActive {
  border:  1px solid <?php echo $CELLBG ?>;
}
.Menubackgr {
  background: <?php echo $CELLBG ?>;
  border-bottom: 1px solid #cccccc;
}
.Menu, .Menubar a, .Menubar td {
  color: <?php echo $THFG ?>;
}
.SubMenu {
  background-color: <?php echo $CELLBG ?>;
  border: 1px solid <?php echo $CELLBG ?>;
}
.MenuItem,
#DomContextMenu_div,
#DomContextMenuContent_div ul li {
  background-color: <?php echo $CELLBG ?>;
}
.MenuItemHover,.MenuItemActive,
#DomContextMenuContent_div ul li:hover {
  background-color: <?php echo $WEEKENDBG ?>;
}
td.MainFolderLeft,td.MainItemLeft,
.MenuFolderLeft,.MenuItemLeft {
  border-top:  1px solid <?php echo $OTHERMONTHBG ?>;
  border-bottom:  1px solid <?php echo $OTHERMONTHBG ?>;
  border-left:  1px solid <?php echo $OTHERMONTHBG ?>;
}
td.MainFolderText,td.MainItemText,
.MenuFolderText,.MenuItemText {
  border-top:  1px solid <?php echo $OTHERMONTHBG ?>;
  border-bottom:  1px solid <?php echo $OTHERMONTHBG ?>;
}
td.MainFolderRight,td.MainItemRight,
.MenuFolderRight,.MenuItemRight {
  border-top:  1px solid <?php echo $OTHERMONTHBG ?>;
  border-bottom:  1px solid <?php echo $OTHERMONTHBG ?>;
  border-right:  1px solid <?php echo $OTHERMONTHBG ?>;
}
.MenuItem .MenuFolderLeft,
.MenuItem .MenuItemLeft {
  background-color:  #DDE1E6;
}
.MenuSplit {
  border-top:  1px solid #C6C3BD;
}
.tabfor {
  background:<?php echo $CELLBG;?>;
}
.tabbak {
  background:#D0D0D0;
}
#fc{
  background: #FFFFFF;
}
#fc td{
 text-align:center;
}
#fc #mns,
#fc #dn {
  text-align:center;
  font:bold 13px Arial;
}
#fc .current {
  background: <?php echo $TODAYCELLBG;?>;
  color: <?php echo $TEXTCOLOR;?>;
}
#fc .day,
#fc .other {
  background: <?php echo $CELLBG;?>;
  color: <?php echo $TEXTCOLOR;?>;
}
#fc .current:hover,
#fc .day:hover {
   background: <?php echo $TODAYCELLBG;?>;
}