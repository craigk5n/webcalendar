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
 *   Example: background_css ( $GLOBALS['CELLBG'], 50 );
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

 defined ( '_ISVALID' ) or die ( 'You cannot access this file directly!' );
// If called directly from a script,
// this will wrap the CSS with the proper mimetype tags.
if ( ! empty ( $_SERVER['PHP_SELF'] ) && !
    preg_match ( "/css_cacher.php/", $_SERVER['PHP_SELF'] ) ) {
  echo "<style type=\"text/css\">\n<!--\n";
}
?>body {
  margin: 2px;
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
  font-family: <?php echo $GLOBALS['FONTS'];

?>;
<?php if ( ! empty ( $GLOBALS['BGIMAGE'] ) ) { ?>
  background: url('<?php echo $GLOBALS['BGIMAGE'];?>') <?php
    echo $GLOBALS['BGREPEAT'];?>;
<?php } ?>
  background-color: <?php echo $GLOBALS['BGCOLOR'];

?>;
}
a {
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
  text-decoration: none;
}
a:hover {
  color: #0000FF;
}
abbr {
  cursor: help;
}
div {
  border: 0;
}
h2 {
  font-size: 20px;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
}
h3 {
  font-size: 18px;
}
fieldset {
  width: 85%;
}
label {
  font-size: 11px;
  font-weight: bold;
}
p,
input,
select {
  font-size: 12px;
}
textarea {
  font-size: 12px;
  overflow: auto;
}
table {
  border-spacing: 0;
  border: 0;
}
th {
  font-size: 13px;
  color: <?php echo $GLOBALS['THFG'];

?>;
  background-color: <?php echo $GLOBALS['THBG'];

?>;
  padding: 0;
}
td {
  font-size: 11px;
}
ul,
ul a {
  font-size: 12px;
}

.main {
  border-collapse: collapse;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  width: 100%;
  clear: both;
}
.main th {
  <?php echo background_css ( $GLOBALS['THBG'], 15 );

?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  vertical-align: top;
}
.main th.weekend {
  <?php echo background_css ( $GLOBALS['THBG'], 15 );
if ( $DISPLAY_WEEKENDS == 'N' ) {

  ?>
 display: none;
<?php }

?>
}
.main th.today {
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 15 );

?>
}
.main td {
  <?php echo background_css ( $GLOBALS['CELLBG'], 100 );

?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  vertical-align: top;
}
.main td.weekend {
  <?php echo background_css ( $GLOBALS['WEEKENDBG'], 100 );
if ( $DISPLAY_WEEKENDS == 'N' ) {

  ?>
 display: none;
<?php }

?>
}
<?php if ( $GLOBALS['HASEVENTSBG'] != $GLOBALS['CELLBG'] ) {

  ?>
.main td.hasevents {
  <?php echo background_css ( $GLOBALS['HASEVENTSBG'], 100 );

  ?>
}
<?php }

?>
.main td.today {
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 );

?>
}
.main td.othermonth {
  <?php echo background_css ( $GLOBALS['OTHERMONTHBG'], 100 );

?>
}
.underline {
 text-decoration: underline;
}
.cursoradd a {
  cursor: pointer;
}
#tabscontent {
  margin: -1px 2px;
  padding: 0.5em;
  border: 2px groove #C0C0C0;
  width: 98%;
  background-color: #F8F8FF;
  position: relative;
  z-index: 50;
}
.tabfor,
.tabbak  {
  padding: 0.2em 0.2em 0.1em 0.2em;
  margin: 0 0 0 0.1em;
  border-top: 2px ridge #C0C0C0;
  border-left: 2px ridge #C0C0C0;
  border-right: 2px ridge #C0C0C0;
  border-bottom: 2px solid #F8F8FF;
  position: relative;
  -moz-border-radius: .75em .75em 0em 0em;
}
.tabfor a,
.tabbak a {
  font-size: 14px;
  font-weight: bold;
  text-decoration: none;
}
.tabfor a {
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
}
.tabbak a {
  color: #999999;
}
.tabfor {
  background-color: #F8F8FF;
  z-index: 51;
}
.tabbak {
  background-color: #E0E0E0;
  z-index: 49;
}
#editnonusers,
#editremotes,
#editremoteshandler,
#edituser,
#groupedit,
#viewsedit {
  background-color: #F8F8FF;
}
#tabscontent_public,
#tabscontent_uac,
#tabscontent_groups,
#tabscontent_nonuser,
#tabscontent_other,
#tabscontent_email,
#tabscontent_colors,
#tabscontent_participants,
#tabscontent_reminder,
#tabscontent_sched,
#tabscontent_pete,
#tabscontent_nonusers,
#tabscontent_remotes,
#tabscontent_themes,
#tabscontent_boss,
#tabscontent_subscribe,
#tabscontent_header,
#useriframe,
#grpiframe,
#nonusersiframe,
#remotesiframe,
#viewiframe {
  display: none;
}
.sample {
  border-style: groove;
  text-align: left;
  width: 18px;
}
.weeknumber,
.weeknumber a {
  font-size: 10px;
  color: <?php echo $GLOBALS['WEEKNUMBER'];

?>;
  text-decoration: none;
}
img.color {
  border: 0;
  width: 15px;
  height: 15px;
}
#cat {
  display: none;
  font-size: 18px;
}
#dateselector,
#trailer {
  margin: 0;
  padding: 0;
}
#dateselector form {
  float: left;
  width: 33%;
<?php if ( $MENU_ENABLED == 'N' ) {

  ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG'];

  ?>;
<?php }

?>
  padding-top: 5px;
  margin-top: 5px;
  margin-bottom: 25px;
}
#dateselector label,
#trailer label {
  margin: 0;
  padding: 0;
  font-weight: bold;
}
#monthform {
  clear: left;
  margin-bottom: 0;
}
#weekform {
  text-align: center;
  margin-bottom: 0;
}
#weekmenu,
#monthmenu,
#yearmenu {
  font-size: 9px;
  text-align: right;
  margin-bottom: 0;
}
#yearform {
  text-align: right;
  clear: right;
  margin-bottom: 0;
}
#menu {
  font-size: 14px;
  clear: both;
}
#menu a {
  font-size: 14px;
}
.prefix {
  font-weight: bold;
  font-size: 14px;
}
a#programname {
  margin-top: 10px;
  font-size: 10px;
}
.printer {
  font-size: 14px;
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
  text-decoration: none;
  clear: both;
  display: block;
  width: 15ex;
}
.new {
  border: 0;
  float: right;
}
.unapprovedentry {
  font-size: 12px;
/* Remove comments to set unapproved italics
  font-style: italic;
*/
  color: #800000;
  text-decoration: none;
  padding-right: 3px;

}
.nounapproved {
  font-weight: bold;
  font-size: 14px;
}
#listunapproved .odd {
  background-color: <?php echo $GLOBALS['TODAYCELLBG'];

?>;
}
.layerentry {
  font-size: 12px;
  color: #006060;
  text-decoration: none;
  padding-right: 3px;
}
.entry {
  font-size: 12px;
  color: <?php echo $GLOBALS['MYEVENTS'];

?>;
  text-decoration: none;
  padding-right: 3px;
}
.entry img,
.layerentry img,
.unapprovedentry img {
  border: 0;
  margin-left: 2px;
  margin-right: 2px;
}
.dayofmonth {
  color: <?php echo $GLOBALS['TABLEBG'];

?>;
  font-weight: bold;
  text-decoration: none;
  border-top-width: 0;
  border-left-width: 0;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  padding: 0 2px 0 3px;
  vertical-align: top;
}
.dayofmonth:hover {
  color: #0000FF;
  border-right: 1px solid #0000FF;
  border-bottom: 1px solid #0000FF;
}
.prev img {
  border: 0;
  margin-left: 3px;
  margin-top: 7px;
  float: left;
}
.next img {
  border: 0;
  margin-right: 3px;
  margin-top: 7px;
  float: right;
}
#activitylog .prev {
  font-size: 14px;
  font-weight: bold;
  border: 0;
  float: left;
}
#activitylog .next {
  font-size: 14px;
  font-weight: bold;
  border: 0;
  float: right;
}
#day .prev img {
  border: 0;
  margin-top: 37px;
  float: left;
}
#day .next img {
  border: 0;
  margin-top: 37px;
  float: right;
}
#day .monthnav .prev img {
  border: 0;
  margin: 0;
  float: left;
}
#day .monthnav .next img {
  border: 0;
  margin: 0;
  float: right;
}
.dailymatrix {
  cursor: pointer;
  font-size: 12px;
  text-decoration: none;
  text-align: right;
  background-color: <?php echo $GLOBALS['THBG'];

?>;
}
.dailymatrix:hover {
  background-color: #CCFFCC;
}
td.matrixappts {
  cursor: pointer;
  text-align: left;
  background-color: <?php echo $GLOBALS['CELLBG'];

?>;
  vertical-align: middle;
  width: 0%;
}
td.matrixappts:hover {
  background-color: #CCFFCC;
}
td.matrix {
  height: 1px;
  background-color: #000000;
}
.matrix img {
  border: 0;
  width: 100%;
  height: 1px;
}
a.matrix img {
  border: 0;
  width: 100%;
  height: 8px;
}
.matrixd {
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  margin-left: auto;
  margin-right: auto;
}
.matrixledge {
  border-left: 1px solid #000000;
}
.matrixlegend {
  margin-top: 25px;
  padding: 5px;
  text-align: center;
  background: #ffffff;
  margin-left: auto;
  margin-right: auto;
  border: 1px solid #000000;
}
.matrixlegend img {
  border: 0;
  width: 10px;
  height: 10px;
}
.nav {
  font-size: 14px;
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
  text-decoration: none;
}
.popup {
  font-size: 12px;
  color: <?php echo $GLOBALS['POPUP_FG'];

?>;
  <?php echo background_css ( $GLOBALS['POPUP_BG'], 200 );

?>
  text-decoration: none;
  position: absolute;
  z-index: 20;
  visibility: hidden;
  top: 0;
  left: 0;
  border: 1px solid <?php echo $GLOBALS['POPUP_FG'];

?>;
  padding: 3px;
  -moz-border-radius: 6px;
}
.popup dl {
  margin: 0;
  padding: 0;
}
.popup dt {
  font-weight: bold;
  margin: 0;
  padding: 0;
}
.popup dd {
  margin-left: 20px;
}
.tooltip {
  font-size: 11px;
  cursor: help;
  text-decoration: none;
  font-weight: bold;
  width: 120px;
}
.tooltipselect {
  font-size: 11px;
  cursor: help;
  text-decoration: none;
  font-weight: bold;
  vertical-align: top;
}
.user,
.categories {
  font-size: 18px;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
  text-align: center;
}
.asstmode {
  font-weight: bold;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
  text-align: center;
}
.help {
  vertical-align: top;
  font-weight: bold;
}
.helpbody {
  margin-bottom: 1em;
  font-weight: normal;
  vertical-align: top;
}
.helpbody div {
  border: 1px solid #000;
}
.helpbody label {
  font-weight: bold;
  font-size: 1.1em;
  vertical-align: top;
}
.helpbody p {
  margin: 1em;
}
.helplist {
  border: 0;
  font-weight: bold;
  font-size: 1.2em;
  text-align: center;
}
.helplist a {
  font-weight: normal;
  text-decoration: underline;
}
.helplist a.current {
  font-weight: bold;
  text-decoration: none;
}
#helpbug form {
  margin-bottom: 1em;
}
#helpbug label {
  clear: left;
  float: left;
  width: 32%;
}
#helpbug p {
  margin: 0 1em;
}
img.help {
  border: 0;
  cursor: help;
}
.standard {
  border: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  background-color: <?php echo $GLOBALS['CELLBG'];

?>;
  font-size: 12px;
}
.standard th {
  font-size: 18px;
  padding: 0;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
}
.minical {
  border-collapse: collapse;
  font-size: 10px;
  margin: 0 0 5px 0;
}
.minical caption a {
  font-weight: bold;
  color: <?php echo $GLOBALS['CAPTIONS'];

?>;
}
.minical caption a:hover {
  color: #0000FF;
}
.minical th {
 padding: 0px 2px;
}
.minical th,
.minical td.empty {
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
  text-align: center;
  background-color: <?php echo $GLOBALS['BGCOLOR'];

?>;
}
<?php if ( $DISPLAY_WEEKENDS == 'N' ) {

  ?>
.minical th.weekend {
  display: none;
}
<?php }

?>
.minical td {
  padding: 0 2px;
  border: 1px solid <?php echo $GLOBALS['BGCOLOR'];

?>;
}
.minical td a {
  display: block;
  text-align: center;
  margin: 0;
  padding: 3px;
}
.minical td.weekend {
  background-color: <?php echo $GLOBALS['WEEKENDBG'];

?>;
<?php if ( $DISPLAY_WEEKENDS == 'N' ) {

  ?>
  display: none;
<?php }

?>
}
.minical td#today {
  background-color: <?php echo $GLOBALS['TODAYCELLBG'];

?>;
}
.minical td.hasevents {
  font-weight: bold;
}
.minitask {
  width: 98%;
  border: 1px solid #000000;
  margin-left: 1px;
}
.minitask tr.header th {
  text-align: center;
  background-color: <?php echo $GLOBALS['CELLBG'];

?>;
  font-size: 12px;
  padding: 0;
  border-bottom: 2px solid #000000;
}
.minitask  td {
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
  font-size: 12px;
  padding: 0;
  border-bottom: 1px solid #000000;
  text-align: center;
}
.minitask td.filler {
  padding: 0;
  border-bottom: 0;
}
.task {
  color: <?php echo $GLOBALS['TEXTCOLOR'];

?>;
}
#admin table,
#pref table{
  vertical-align: top;
}
#admin .tooltip,
#pref .tooltip{
  cursor: help;
  text-decoration: none;
  font-weight: bold;
  width: 175px;
  vertical-align: top;
}
#minicalendar table {
  width: <?php echo ( ! empty ( $GLOBALS['MINICALWIDTH'] )
  ? $GLOBALS['MINICALWIDTH'] : '160px' );

?>;
}
#minicalendar th{
  font-size: <?php echo ( ! empty ( $GLOBALS['MINICALFONT'] )
  ? $GLOBALS['MINICALFONT'] : '11px' );

?>;
}
#minicalendar td {
  font-size: <?php echo ( ! empty ( $GLOBALS['MINICALFONT'] )
  ? $GLOBALS['MINICALFONT'] : '11px' );

?>;
}
.embactlog {
  width: 100%;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-spacing: 0;
}
.embactlog tr {
  background-color: #FFFFFF;
}
.embactlog .odd {
  background-color: #EEEEEE;
}
.embactlog th {
  width: 14%;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  padding: 1px 3px;
}
.embactlog th.usr,
.embactlog th.cal,
.embactlog th.action {
  width: 7%;
}
.embactlog td {
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  padding: 1px 3px;
}
#day div.minicalcontainer {
  vertical-align: top;
  border: 1px solid #000000;
  padding: 3px;
}
#day table.minical {
  margin-left: auto; margin-right: auto;
}
#day .minical caption {
  margin-left: auto; margin-right: auto;
  font-weight: bold;
  color: <?php echo $GLOBALS['THFG'];

?>;
  background-color: <?php echo $GLOBALS['THBG'];

?>;
  font-size: 47px;
}
#day .minical td.selectedday {
  border: 2px solid <?php echo $GLOBALS['TABLEBG'];

?>;
}
#day .monthnav th {
  text-align: center;
  border: 0;
  padding: 3px;
}
.menuhref {
  font-family:  arial, verdana, sans-serif;
  font-size: 12px;
}
#datesel td.field {
  font-size: 12px;
}
.glance {
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  width: 100%;
}
.glance th.empty {
  border-top: 1px solid <?php echo $GLOBALS['BGCOLOR'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['BGCOLOR'];

?>;
  background-color: <?php echo $GLOBALS['BGCOLOR'];

?>;
}
.glance th.row {
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  height: 40px;
  width: 14%;
  vertical-align: top;
}
.glance td {
  vertical-align: top;
  <?php echo background_css ( $GLOBALS['CELLBG'], 50 );

?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  padding-left: 3px;
}
#day dl.desc {
  display: none;
  margin: 0;
  padding: 0;
}
#day dl.desc dt {
  font-weight: bold;
}
#day dl.desc dd {
  margin: 0;
  padding-left: 20px;
}
#year #monthgrid td {
  vertical-align: top;
  padding: 0px 5px;
}
#year .minical tr {
  vertical-align: top;
}
#admin th td {
  padding: 3px;
}
#admin .main th,
#pref .main th,
#viewv .main th,
#viewl .main th,
#month .main th {
  width: 14%;
}

#vieww .main th,
#week .main th {
  width: 12%;
}
#viewr th.small {
  background: none;
  font-size: 8px;
}
#viewd .main th {
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  padding: 1px;
}
a.weekcell {
  color: <?php echo $GLOBALS['WEEKNUMBER'];

?>;
}
#admin .main th.weekcell,
#pref .main th.weekcell,
#viewl .main th.weekcell,
#month .main th.weekcell{
  background: <?php echo $GLOBALS['BGCOLOR'];

?>;
  background-color: <?php echo $GLOBALS['BGCOLOR'];

?>;
  border-left: 0;
  border-top: 0;
  width: 1%;
}
#admin .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell,
#month .main td.weekcell {
  <?php echo background_css ( $GLOBALS['THBG'], 50 );

?>
  width: 1%;
  margin: 0;
  vertical-align: middle;
  text-align: center;
  font-size: 12px;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
  text-decoration: none;
}
#viewv .main th.empty,
#viewm .main th.empty,
#vieww .main th.empty,
#viewr .main th.empty,
#week .main th.empty {
  width: 5%;
  background: none;
  background-color: <?php echo $GLOBALS['BGCOLOR'];

?>;
  border-top: 1px solid <?php echo $GLOBALS['BGCOLOR'];

?>;
  border-left: 1px solid <?php echo $GLOBALS['BGCOLOR'];

?>;
}
#week .main th.row {
  width: 5%;
  vertical-align: top;
  height: 40px;
}
#viewt.main {
  margin: 0;
  padding: 0;
}
#vieww .main th.row,
#viewv .main th.row,
#viewm .main th.row,
#viewt .main th {
  width: 10%;
  vertical-align: top;
}
<?php if ( $DISPLAY_WEEKENDS == 'N' ) {

  ?>
#viewt .main tr.weekend {
 display: none;
}
<?php }

?>
#viewt .main th.weekend {
  <?php echo background_css ( $GLOBALS['WEEKENDBG'], 15 );

?>
}
#viewv .main th.row {
  text-align: left;
  padding: 0 5px;
}
#viewd .main th.row {
  border-right-width: 0;
  text-align: left;
}
#viewr th.row {
  height: 40px;
  vertical-align: top;
}
#vieww .main th.today,
#viewm .main th.today,
#viewv .main th.today {
  width: 10%;
}
#admin .main th.today,
#pref .main th.today,
#viewr .main th.today,
#week .main th.today {
  width: 12%;
}
#viewr .main th a,
#week .main th a,
#weekdetails .main th a {
  color: <?php echo $GLOBALS['THFG'];

?>;
}
#viewr .main th a:hover,
#week .main th a:hover,
#weekdetails .main th a:hover {
  color: #0000FF;
}
#year .minical td {
  text-align: center;
  vertical-align: top;
}
#admin .main td,
#pref .main td{
  font-size: 12px;
  height: 30px;
}
#viewl .main td,
#month .main td {
  font-size: 12px;
  height: 75px;
  vertical-align: top;
  table-layout: fixed;
  overflow: auto;
}
#vieww .main td,
#week .main td,
#viewr .main td,
#viewm .main td,
#viewv .main td {
  font-size: 12px;
  padding-left: 3px;
}
<?php if ( $DISPLAY_TASKS != 'Y' ) {

  ?>
#month #prevmonth,
<?php }

?>
#viewl #prevmonth {
  float: left;
}
<?php if ( $DISPLAY_TASKS != 'Y' ) {

  ?>
#month #nextmonth,
<?php }

?>
#viewl #nextmonth {
  float: right;
}
#month .minical caption,
#viewl .minical caption {
  margin-left: 4ex;
}
#year .minical {
  display: block;
}
#year .minical caption {
  margin: 0 auto;
}
#viewl .minical,
#month .minical {
  margin: 0px 10px;
  border: 0;
}
.topnav {
  border: 0;
}
.title {
  width: 99%;
  text-align: center;
}
#day .title {
  margin-top: 3px;
  text-align: center;
}
#day .title .date,
.title .date {
  font-size: 24px;
  font-weight: bold;
  text-align: center;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
}
.title .titleweek {
  font-size: 20px;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
}
.title .viewname,
#day .title .user,
.title .user {
  font-size: 18px;
  font-weight: bold;
  color: <?php echo $GLOBALS['H2COLOR'];

?>;
  text-align: center;
}
#weekdetails .main {
  width: 90%;
}
#weekdetails .main th {
  width: 100%;
  padding: 2px;
}
#weekdetails .main td {
  height: 75px;
}
#viewt table.timebar {
  border-collapse: collapse;
  width: 100%;
}
#viewt td.timebar {
  width: 90%;
  background-color: #FFFFFF;
  text-align: center;
  color: #999999;
  font-size: 10px;
}
#viewt .yardstick td {
  padding: 0;
  border: 1px solid #999999;
}
#viewt td.entry {
  padding: 0;
  <?php echo background_css ( $GLOBALS['THBG'], 10 );

?>
}
#viewt table.timebar a {
  text-align: inherit !important;
}
.viewnav {
  border: 0;
  width: 99%;
}
#login {
  margin-top: 30px;
  margin-bottom: 50px;
}
#register table,
#login table {
  border: 1px solid <?php echo $GLOBALS['TABLEBG'];

?>;
  <?php echo background_css ( $GLOBALS['CELLBG'], 200 );

?>
  font-size: 12px;
}
.cookies {
  font-size: 13px;
}
.strikethrough {
  text-decoration : line-through;
}
.pub {
  background-color: #80FF80;
  text-align: center;
}
.conf {
  background-color: #FFFF80;
  text-align: center;
}
.priv {
  background-color: #FF5050;
  text-align: center;
}
.boxtop {
 border-top: 1px solid #888888;
}
.boxleft {
 border-left: 1px solid #888888;
}
.boxright {
 border-right: 1px solid #888888;
}
.boxbottom {
 border-bottom: 1px solid #888888;
}
.boxall {
 padding-left: 3px;
 border: 1px solid #888888;
}
.leftpadded {
 padding-left: 50px;
 text-align: left;
}
.location {
 font-size: 10px;
}
.byxxx th,
.byxxx td {
 text-align: center;
}
.icon_text {
 border: 0;
 width: 10px;
 height: 10px;
}
.minitask td.pct,
.alignright {
 text-align: right;
}
.minitask td.name,
.alignleft {
 text-align: left;
}
.aligncenter {
 text-align: center;
}
.aligntop {
 vertical-align: top;
}
.bold {
 font-weight: bold;
}
#about {
  background-image: url("images/kn5.jpg");
  background-repeat: no-repeat;
}
#about p {
  margin:1px;
  color:#333333;
}
#scroller {
  position:absolute;
  width:100%;
}
.layers {
  float: left;
  margin: 2px 1px;
  padding: 5px;
  background: <?php echo $CELLBG ?>;
}
.layers h4{
  margin: 0 0 5px;
}
.layers p {
  margin: 0;
  padding-left: 5px;
  font-size: 12px;
}
.layers p label {
  font-size: 13px;
}
#colors {
  background: #CCC;
}
#colors td img {
  border: 0;
}
#colorpic {
  width: 192px;
  height: 192px;
}
#thecell,
#theoldcell {
  border: 1px;
  backgcound: #FFF;
}
#thecell td img,
#theoldcell td img {
  width: 55px;
  height: 53px;
}
#cross,
#sliderarrow {
  position: absolute;
  top: 0;
  left: 0;
}
<?php
if ( ! empty ( $_SERVER['PHP_SELF'] ) && !
    preg_match ( "/css_cacher.php/", $_SERVER['PHP_SELF'] ) ) {
  echo "\n-->\n</style>";
}

?>
