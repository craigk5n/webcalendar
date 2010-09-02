<?php
/**
 * Dynamic CSS styles used in WebCalendar.
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
 * display: none; is unhidden by includes/css/print_styles.css
 * for printer-friendly pages and where else needed.
 *
 *                            PHP FUNCTION CALLS
 * A special function, background_css(), will allow the dynamic creation of
 * gradient images to be used for the background of that selector. The image
 * file will be created and cached (if enabled) for faster processing and the
 * url will be returned for inclusion into the final CSS file.
 *   Example: background_css( $GLOBALS['CELLBG'], 50 );
 *   Yields : background: #FFFFFF url( images/cache/FFFFFF-50.png ) repeat-x;
 *
 *                          CSS CACHING AND VIEWING
 * A caching scheme has been implemented to improve performance and reduce
 * download payloads. This file is now called from a helper file called
 * 'css_cacher.php'. Its function is to control cache expiration and compress
 * the data if possible.
 *
 * To view the current CSS definitions from your browser, simply run
 *   http://yourserver/css_cacher.php
 * The resulting file will contain the color and layout preferences for the
 * logged in user or the default values if not logged in.
 *
 * Each page in WebCalendar is assigned a unique ID. This unique ID is
 * determined by taking the name of the page & removing any underscores (_).
 *   Example: edit_entry.php
 *   Results: <body id="editentry">
 */

defined( '_ISVALID' ) or die( 'You cannot access this file directly!' );

echo ( $DISPLAY_TASKS != 'Y' ? '#month #nextmonth{
  float:right;
}
#month #prevmonth{
  float:left;
}
' : '' ) . '#minicalendar table{
  width:' . ( empty( $GLOBALS['MINICALWIDTH'] )
  ? '10em' : $GLOBALS['MINICALWIDTH'] ) . ';
}
' . ( $MENU_ENABLED == 'N' ? '#dateselector form{
  border-top:0.0625em solid ' . $GLOBALS['TABLEBG'] . ';
}
' : '' ) . '#day .minical td.selectedday,
#eventcomment,
#login table,
#register table,
#securityAuditNotes,
#viewd .main th,
.dayofmonth,
.embactlog,
.embactlog th,
.embactlog td,
.glance,
.glance th.row,
.glance td,
.main,
.main th,
.main td,
.matrixd,
.note,
.rightsidetip,
.standard,
.standard th{
  border-color:' . $GLOBALS['TABLEBG'] . ';
}
.glance th.empty,
.minical th,
.minical td,
#viewv .main th.empty,
#viewm .main th.empty,
#vieww .main th.empty,
#viewr .main th.empty,
#week .main th.empty{
  border-color:' . $GLOBALS['BGCOLOR'] . ';
}
body{
  background:' . $GLOBALS['BGCOLOR'] . ( empty( $GLOBALS['BGIMAGE'] )
  ? '' : ' url( ' . $GLOBALS['BGIMAGE'] . ' ) ' . $GLOBALS['BGREPEAT'] ) . ';
  font-family:' . $GLOBALS['FONTS'] . ';
}
body,
a,
.minical td.empty,
.minitask td,
.nav,
.printer,
.task{
  color:' . $GLOBALS['TEXTCOLOR'] . ';
}
.popup{
  border-color:' . $GLOBALS['POPUP_FG'] . ';
  ' . background_css( $GLOBALS['POPUP_BG'], 200 ) . '
  color:' . $GLOBALS['POPUP_FG'] . ';
}
th,
#day .minical caption,
.dailymatrix,
.layertable th{
  background:' . $GLOBALS['THBG'] . ';
}
.main th,
.main th.weekend{
  ' . background_css( $GLOBALS['THBG'], 15 ) . '
}
.main td{
  ' . background_css( $GLOBALS['CELLBG'], 100 ) . '
}
.main td.weekend{
  ' . background_css( $GLOBALS['WEEKENDBG'], 100 ) . '
}' . ( $GLOBALS['HASEVENTSBG'] != $GLOBALS['CELLBG'] ? '
.main td.hasevents{
  ' . background_css( $GLOBALS['HASEVENTSBG'], 100 ) . '
}' : '' ) . '
.main td.othermonth{
  ' . background_css( $GLOBALS['OTHERMONTHBG'], 100 ) . '
}
.main td.today{
  ' . background_css( $GLOBALS['TODAYCELLBG'], 100 ) . '
}
#admin .main td.weekcell,
#month .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell{
  ' . background_css( $GLOBALS['THBG'], 50 ) . '
}
td.matrixappts,
#adminhome table,
#adminhome td a,
#eventcomment,
.alt,
.layers,
.layertable td,
.minical td,
.minitask tr.header th,
.minitask tr.header td,
.standard{
  background:' . $GLOBALS['CELLBG'] . ';
}
#editentry th.weekend,
.minical td.weekend{
  background:' . $GLOBALS['WEEKENDBG'] . ';
}
#example_month,
.glance th.empty,
.minical th,
.minical td.empty{
  background:' . $GLOBALS['BGCOLOR'] . ';
}
#listunapproved .odd,
.minical td#today{
  background:' . $GLOBALS['TODAYCELLBG'] . ';
}
.glance td,
.note{
  ' . background_css( $GLOBALS['CELLBG'], 50 ) . '
}
#viewt .main th.weekend{
  ' . background_css( $GLOBALS['WEEKENDBG'], 15 ) . '
}
#contentDay .daytimedevent{
  background:' . $GLOBALS['HASEVENTSBG'] . ';
}
#login table,
#register table{
  ' . background_css( $GLOBALS['CELLBG'], 200 ) . '
}
#securityAuditNotes{
  ' . background_css( $GLOBALS['CELLBG'], 150 ) . '
}
#viewt td.entry{
  ' . background_css( $GLOBALS['THBG'], 10 ) . '
}
a.weekcell,
.weeknumber{
  color:' . $GLOBALS['WEEKNUMBER'] . ';
}
h2,
.asstmode,
.categories,
.tabfor a,
.title .date,
.title .titleweek,
.user,
.title .user,
.title .viewname,
#admin .main td.weekcell,
#day .title .date,
#day .title .user,
#example_month p,
#month .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell{
  color:' . $GLOBALS['H2COLOR'] . ';
}
th,
#day .minical caption,
#viewr .main th a,
#week .main th a,
#weekdetails .main th a,
.layertable th{
  color:' . $GLOBALS['THFG'] . ';
}
#contentDay #dayuntimed,
#contentDay .daytimedevent,
.entry{
  color:' . $GLOBALS['MYEVENTS'] . ';
}
.dayofmonth,
.note{
  color:' . $GLOBALS['TABLEBG'] . ';
}
.minical th{
  color:' . $GLOBALS['TEXTCOLOR'] . ';
}
.minical caption a{
  color:' . $GLOBALS['CAPTIONS'] . ';
}
#minicalendar th,
#minicalendar td{
  font-size:' . ( empty( $GLOBALS['MINICALFONT'] )
  ? '0.6875em' : $GLOBALS['MINICALFONT'] ) . ';
}
' . ( $DISPLAY_WEEKENDS == 'N' ? '#viewt .main tr.weekend,
.main th.weekend,
.main td.weekend,
.minical th.weekend,
.minical td.weekend{
  display:none;
}
' : '' );

?>
<?php
if ( $CATEGORIES_ENABLED == 'Y' ) {
  // Need to load user variables so that $is_admin is set before we load
  // categories.
  user_load_variables ( $user, '' );
  load_user_categories ();

  // Default color is $MYEVENTS.  Add a bogus array 'none' element for it.
  $categories['none'] = array ( 'cat_color' => $MYEVENTS );
  foreach ( $categories as $catId => $cat ) {
    if ( $catId == 0 || $catId == -1 ) next;
    //echo "\n cat id = $catId\n";
    $color = $cat['cat_color'];
    $fg = '#000000';
    if ( $catId < 0 )
      $catId = 0 - $catId;
    $rgb = array ( 255, 255, 255 );
    if ( ! empty ( $color ) ) {
      if ( preg_match ( "/#(.)(.)(.)(.)(.)(.)/", $color ) ) {
        $rgb = html2rgb ( $color );
        // If red+green+blue is less than 50%, then we will
        // assume this color is dark enough to use a white text foreground.
        if ( $rgb[0] + $rgb[1] + $rgb[2] < 384 ) {
          $fg = '#ffffff';
        }
        //echo "// " . hextoint ( $matches[1] ) . ',' . hextoint ( $matches[3]).','. hextoint ( $matches[5] )  . " $fg\n";
      }
    // Gradient
    //  echo ".cat_{$catId} { "
    //    . background_css( $color, 15 ) . ' color: ' . $fg . "; }\n";
      echo ".cat_{$catId} { background-color: $color; border: 1px outset $color; color: $fg }\n";
    }
  }
}

?>
