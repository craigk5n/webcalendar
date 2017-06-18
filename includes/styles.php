<?php
/* CSS styles used in WebCalendar
 *
 * @author Craig Knudsen <cknudsen@cknudsen.com>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: styles.php,v 1.225.2.6 2010/04/06 16:43:45 cknudsen Exp $
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
 * A special function, background_css (), will allow the dynamic creation of
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
$end_style = '';
if ( ! empty ( $_SERVER['PHP_SELF'] ) && !
    preg_match ( '/css_cacher.php/', $_SERVER['PHP_SELF'] ) ) {
  echo '<style type="text/css">
';
  $end_style = '
</style>
';
}

echo '  body {
    margin:2px;
    background:' . $GLOBALS['BGCOLOR'] . ( empty ( $GLOBALS['BGIMAGE'] )
  ? '' : ' url( ' . $GLOBALS['BGIMAGE'] . ' ) ' . $GLOBALS['BGREPEAT'] ) . ';
    color:' . $GLOBALS['TEXTCOLOR'] . ';
    font-family:' . $GLOBALS['FONTS'] . ';
  }
  a {
    color:' . $GLOBALS['TEXTCOLOR'] . ';
    text-decoration:none;
  }
  a:hover {
    color:#0000ff;
  }
  abbr {
    cursor:help;
  }
  div {
    border:0;
  }
  h2 {
    color:' . $GLOBALS['H2COLOR'] . ';
    font-size:20px;
  }
  h3 {
    font-size:18px;
  }
  fieldset {
    width:96%;
  }
  label {
    font-weight:bold;
    font-size:11px;
  }
  p,
  input,
  select {
    font-size:12px;
  }
  textarea {
    font-size:12px;
    overflow:auto;
  }
  table {
    border:0;
    border-spacing:0;
  }
  th {
    font-size:13px;
    padding:0;
    background:' . $GLOBALS['THBG'] . ';
    color:' . $GLOBALS['THFG'] . ';
  }
  td {
    font-size:11px;
  }
  ul,
  ul a {
    font-size:12px;
  }

  .main {
    clear:both;
    width:100%;
    border-right:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-bottom:1px solid ' . $GLOBALS['TABLEBG'] . ';
  }
  .main th {
    width:14%;
    border-top:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
    ' . background_css ( $GLOBALS['THBG'], 15 ) . '
    vertical-align:top;
  }
  .main th.weekend {
    ' . background_css ( $GLOBALS['THBG'], 15 ) . '
  }
  .main td {
    width:14%;
    border-top:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
    ' . background_css ( $GLOBALS['CELLBG'], 100 ) . '
    vertical-align:top;
  }
  .main td.weekend {
    ' . background_css ( $GLOBALS['WEEKENDBG'], 100 ) . '
  }' . ( $GLOBALS['HASEVENTSBG'] != $GLOBALS['CELLBG'] ? '
  .main td.hasevents {
    ' . background_css ( $GLOBALS['HASEVENTSBG'], 100 ) . '
  }' : '' ) . '
  .main td.othermonth {
    ' . background_css ( $GLOBALS['OTHERMONTHBG'], 100 ) . '
  }
  .main td.today {
    ' . background_css ( $GLOBALS['TODAYCELLBG'], 100 ) . '
  }
  .underline {
   text-decoration:underline;
  }
  .cursoradd a {
    cursor:pointer;
  }
  #tabs,
  #tabscontent {
    position:relative;
    width:96%;
  }
  #tabscontent {
    margin:-1px 2px;
    border:2px groove #C0C0C0;
    padding:.5em;
    background:#F8F8FF;
    z-index:50;
  }
  .tabfor,
  .tabbak  {
    margin:0 0 0 .1em;
    border:2px ridge #C0C0C0;
    border-bottom:2px solid #F8F8FF;
    padding:.2em .2em .1em;
    position:relative;
    -moz-border-radius:.75em .75em 0 0;
  }
  .tabfor a,
  .tabbak a {
    font-weight:bold;
    font-size:14px;
  }
  .tabfor a {
    color:' . $GLOBALS['H2COLOR'] . ';
  }
  .tabbak a {
    color:#909090;
  }
  .tabfor {
    background:#F8F8FF;
    z-index:51;
  }
  .tabbak {
    background:#E0E0E0;
    z-index:49;
  }
  #editnonusers,
  #editremotes,
  #editremoteshandler,
  #edituser,
  #groupedit,
  #viewsedit {
    background:#F8F8FF;
  }
  #cat,
  #day dl.desc,
  #grpiframe,
  #nonusersiframe,
  #remotesiframe,
  #tabscontent_boss,
  #tabscontent_colors,
  #tabscontent_email,
  #tabscontent_groups,
  #tabscontent_header,
  #tabscontent_nonuser,
  #tabscontent_nonusers,
  #tabscontent_other,
  #tabscontent_participants,
  #tabscontent_pete,
  #tabscontent_public,
  #tabscontent_reminder,
  #tabscontent_remotes,
  #tabscontent_sched,
  #tabscontent_subscribe,
  #tabscontent_themes,
  #tabscontent_uac,
  #useriframe,
  #viewiframe' . ( $DISPLAY_WEEKENDS == 'N' ? ',
  #viewt .main tr.weekend,
  .main td.weekend,
  .main th.weekend,
  .minical td.weekend,
  .minical th.weekend' : '' ) . ' {
    display:none;
  }
  #tabscontent_colors p span,
  .sample {
    margin:0 1px;
    border-style:groove;
    padding:0 8px;
  }
  .weeknumber {
    color:' . $GLOBALS['WEEKNUMBER'] . ';
    font-size:10px;
    text-decoration:none;
  }
  img {
    border:0;
  }
  img.color {
    width:15px;
    height:15px;
  }
  #cat {
    font-size:18px;
  }
  #dateselector,
  #trailer {
    margin:0;
    padding:0;
  }
  #dateselector form {
    float:left;
    width:33%;
    margin-top:5px;
    margin-bottom:25px;' . ( $MENU_ENABLED == 'N' ? '
    border-top:1px solid ' . $GLOBALS['TABLEBG'] . ';' : '' ) . '
    padding-top:5px;
  }
  #dateselector label,
  #trailer label {
    margin:0;
    padding:0;
  }
  #monthform {
    clear:left;
    margin-bottom:0;
  }
  #weekform {
    margin-bottom:0;
    text-align:center;
  }
  #weekmenu,
  #monthmenu,
  #yearmenu {
    margin-bottom:0;
    font-size:9px;
    text-align:right;
  }
  #yearform {
    clear:right;
    margin-bottom:0;
    text-align:right;
  }
  #menu {
    clear:both;
  }
  #menu,
  #menu a,
  .prefix,
  .printer {
    font-size:14px;
  }
  .prefix {
    font-weight:bold;
  }
  a#programname {
    margin-top:10px;
    font-size:10px;
  }
  .printer {
    clear:both;
    width:15ex;
    color:' . $GLOBALS['TEXTCOLOR'] . ';
    text-decoration:none;
    display:block;
  }
  .new {
    float:right;
  }
  .unapprovedentry {
    padding-right:3px;
    color:#800000;
  /* Remove comments to set unapproved in italics.
    font-style:italic;
  */
    font-size:12px;
    text-decoration:none;
  }
  .nounapproved {
    font-weight:bold;
    font-size:14px;
  }
  #listunapproved .odd {
    background:' . $GLOBALS['TODAYCELLBG'] . ';
  }
  .entry,
  .layerentry {
    padding-right:3px;
    font-size:12px;
    text-decoration:none;
  }
  .layerentry {
    color:#006060;
  }
  .entry {
    color:' . $GLOBALS['MYEVENTS'] . ';
  }
  .entry img,
  .layerentry img,
  .unapprovedentry img {
    margin-right:2px;
    margin-left:2px;
  }
  .dayofmonth {
    border:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-width:0 1px 1px 0;
    padding:0 2px 0 3px;
    color:' . $GLOBALS['TABLEBG'] . ';
    font-weight:bold;
    text-decoration:none;
    vertical-align:top;
  }
  .dayofmonth:hover {
    border-right:1px solid #0000ff;
    border-bottom:1px solid #0000ff;
    color:#0000ff;
  }
  .next img,
  #activitylog .next {
    float:right;
  }
  .prev img,
  #activitylog .prev {
    float:left;
  }
  .next img,
  .prev img {
    margin-top:7px;
    margin-right:3px;
  }
  #activitylog .next,
  #activitylog .prev {
    border:0;
    font-weight:bold;
    font-size:14px;
  }
  #day .next img,
  #day .prev img {
    margin-top:37px;
  }
  #day .monthnav .next img,
  #day .monthnav .prev img {
    margin:0;
  }
  .dailymatrix {
    background:' . $GLOBALS['THBG'] . ';
    cursor:pointer;
    font-size:12px;
    text-align:right;
    text-decoration:none;
  }
  .dailymatrix:hover {
    background:#cfc;
  }
  td.matrixappts {
    width:0%;
    background:' . $GLOBALS['CELLBG'] . ';
    cursor:pointer;
    text-align:left;
    vertical-align:middle;
  }
  td.matrixappts:hover {
    background:#cfc;
  }
  td.matrix {
    height:1px;
    background:#000;
  }
  .matrix img {
    width:100%;
    height:1px;
  }
  a.matrix img {
    width:100%;
    height:8px;
  }
  .matrixd {
    margin-right:auto;
    margin-left:auto;
    border-right:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
  }
  .matrixledge {
    border-left:1px solid #000;
  }
  .matrixlegend {
    margin-top:25px;
    margin-right:auto;
    margin-left:auto;
    border:1px solid #000;
    padding:5px;
    background:#ffffff;
    text-align:center;
  }
  .matrixlegend img {
    width:10px;
    height:10px;
  }
  .nav {
    color:' . $GLOBALS['TEXTCOLOR'] . ';
    font-size:14px;
    text-decoration:none;
  }
  .popup {
    position:absolute;
    top:0;
    left:0;
    border:1px solid ' . $GLOBALS['POPUP_FG'] . ';
    padding:3px;
    ' . background_css ( $GLOBALS['POPUP_BG'], 200 ) . '
    color:' . $GLOBALS['POPUP_FG'] . ';
    font-size:12px;
    text-decoration:none;
    visibility:hidden;
    z-index:20;
    -moz-border-radius:6px;
  }
  .popup dl,
  .popup dt {
    margin:0;
    padding:0;
  }
  .popup dt {
    font-weight:bold;
  }
  .popup dd {
    margin-left:20px;
  }
  .tooltip,
  .tooltipselect {
    cursor:help;
    font-weight:bold;
    font-size:11px;
    text-decoration:none;
  }
  .tooltip {
    width:120px;
  }
  .tooltipselect {
    vertical-align:top;
  }
  .user,
  .categories {
    color:' . $GLOBALS['H2COLOR'] . ';
    font-size:18px;
    text-align:center;
  }
  .asstmode {
    color:' . $GLOBALS['H2COLOR'] . ';
    font-weight:bold;
    text-align:center;
  }
  .help {
    font-weight:bold;
    vertical-align:top;
  }
  .helpbody {
    margin-bottom:1em;
    font-weight:normal;
    vertical-align:top;
  }
  .helpbody div {
    border:1px solid #000;
  }
  .helpbody label {
    font-size:1.1em;
    vertical-align:top;
  }
  .helpbody p {
    margin:1em;
  }
  .helplist {
    border:0;
    font-weight:bold;
    font-size:1.2em;
    text-align:center;
  }
  .helplist a {
    font-weight:normal;
    text-decoration:underline;
  }
  .helplist a.current {
    font-weight:bold;
    text-decoration:none;
  }
  #helpbug form {
    margin-bottom:1em;
  }
  #helpbug label {
    clear:left;
    float:left;
    width:32%;
  }
  #helpbug p {
    margin:0 1em;
  }
  img.help {
    cursor:help;
  }
  .sample {
  text-align: left;
  width: 16px;
}
  .standard {
    border:1px solid ' . $GLOBALS['TABLEBG'] . ';
    background:' . $GLOBALS['CELLBG'] . ';
    font-size:12px;
  }
  .standard th {
    border-bottom:1px solid ' . $GLOBALS['TABLEBG'] . ';
    padding:0;
    font-size:18px;
  }
   /* style for week hover highlight */
  tr.highlight td {
    background-color:#ffffb0 !important;
  }
  .minical {
    margin:0 0 5px 0;
    border-collapse:collapse;
    font-size:10px;
  }
  .minical caption a {
    color:' . $GLOBALS['CAPTIONS'] . ';
    font-weight:bold;
  }
  .minical caption a:hover {
    color:#0000ff;
  }
  .minical th {
    border:0 solid ' . $GLOBALS['BGCOLOR'] . ';
    padding:0 2px;
  }
  .minical th,
  .minical td.empty {
    background:' . $GLOBALS['BGCOLOR'] . ';
    color:' . $GLOBALS['TEXTCOLOR'] . ';
    text-align:center;
  }
  .minical th.empty {
    background:transparent;
  }
  .minical td {
    background:' . $GLOBALS['CELLBG'] . ';
    border:1px solid ' . $GLOBALS['BGCOLOR'] . ';
    padding:0 2px;
  }
  .minical td a {
    margin:0;
    padding:3px;
    text-align:center;
    display:block;
  }
  .minical td.weekend,
  #editentry th.weekend {
    background:' . $GLOBALS['WEEKENDBG'] . ';
  }
  .minical td#today {
    background:' . $GLOBALS['TODAYCELLBG'] . ';
  }
  .minical td.hasevents {
    font-weight:bold;
  }
  .minitask {
    width:98%;
    margin-left:1px;
    border:1px solid #000;
  }
  .minitask tr.header th,
  .minitask tr.header td {
    border-bottom:2px solid #000;
    padding:0;
    background:' . $GLOBALS['CELLBG'] . ';
    font-size:12px;
    text-align:center;
  }
  .minitask tr.header td {
    margin:0;
    border-bottom:0;
    text-align:right;
  }
  .sorter {
    margin:0;
    border-bottom:0px;
    cursor:pointer;
    text-align:left !important;
  }
  .sorterbottom {
   border-bottom:1px solid #000 !important;
  }
  .minitask  td {
    border-bottom:1px solid #000;
    padding:0;
    color:' . $GLOBALS['TEXTCOLOR'] . ';
    font-size:12px;
    text-align:center;
  }
  .minitask td.filler {
    border-bottom:0;
    padding:0;
  }
  .task {
    color:' . $GLOBALS['TEXTCOLOR'] . ';
  }
  #admin table,
  #pref table {
    vertical-align:top;
  }
  #admin input,
  #admin select,
  #pref input,
  #pref select {
    margin:0 3px;
  }
  #admin .main td,
  #pref .main td {
    height:30px;
  }
  #admin .main td,
  #admin .main th,
  #pref .main td,
  #pref .main th {
    font-size:12px;
  }
  #admin .empty,
  #pref .empty {
    border-top:transparent;
    border-left:transparent;
  }
  #admin .main td.weekcell,
  #pref .main td.weekcell {
    margin:0;
    ' . background_css ( $GLOBALS['THBG'], 50 ) . '
    color:' . $GLOBALS['H2COLOR'] . ';
    text-align:center;
    text-decoration:none;
    vertical-align:middle;
  }
  #admin .main th.weekcell,
  #pref .main th.weekcell {
    background:transparent;
  }
  #admin .main td.empty,
  #admin .main td.weekcell,
  #admin .main th.empty,
  #admin .main th.weekcell,
  #pref .main td.empty,
  #pref .main td.weekcell,
  #pref .main th.empty,
  #pref .main th.weekcell {
    width:1%;
  }
  #admin #tabscontent p,
  #pref #tabscontent p {
    padding:0 .25em;
    clear:both;
  }
  #admin #tabscontent p label,
  #pref #tabscontent p label {
    clear:both;
    float:left;
    width:25%;
    margin:0;
  }
  #admin #tabscontent_colors p,
  #pref #tabscontent_colors p,
  #admin #tabscontent_colors p label,
  #pref #tabscontent_colors p label {
    clear:none;
  }
  #admin #saver {
    clear:both;
    margin-top:1em;
  }
  #example_month {
    float:right;
    width:45%;
    margin:3em 1em 0;
    background:' . $BGCOLOR . ';
  }
  #example_month p {
    color:' . $H2COLOR . ';
    font-weight:bold;
    text-align:center;
  }
  #pref .tooltip{
    width:175px;
    vertical-align:top;
  }
  #minicalendar table {
    width:'
 . ( empty ( $GLOBALS['MINICALWIDTH'] ) ? '160px' : $GLOBALS['MINICALWIDTH'] ) . ';
  }
  #minicalendar td,
  #minicalendar th {
    font-size:'
 . ( empty ( $GLOBALS['MINICALFONT'] ) ? '11px' : $GLOBALS['MINICALFONT'] ) . ';
  }
  .embactlog {
    width:100%;
    border-right:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-bottom:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-spacing:0;
  }
  .embactlog tr {
    background:#FFF;
  }
  .embactlog .odd {
    background:#EEE;
  }
  .embactlog th {
    width:14%;
    border-top:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-bottom:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
    padding:1px 3px;
  }
  .embactlog th.action,
  .embactlog th.cal,
  .embactlog th.usr {
    width:7%;
  }
  .embactlog td {
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
    padding:1px 3px;
  }
  #day div.minicalcontainer {
    border:1px solid #000;
    padding:3px;
    vertical-align:top;
  }
  #day table.minical {
    margin-right:auto;
    margin-left:auto;
  }
  #day .minical caption {
    margin-right:auto;
    margin-left:auto;
    background:' . $GLOBALS['THBG'] . ';
    color:' . $GLOBALS['THFG'] . ';
    font-weight:bold;
    font-size:47px;
  }
  #day .minical td.selectedday {
    border:2px solid ' . $GLOBALS['TABLEBG'] . ';
  }
  #day .monthnav th {
    border:0;
    padding:3px;
    text-align:center;
  }
  .menuhref {
    font-size:12px;
    font-family: arial, verdana, sans-serif;
  }
  #datesel td.field {
    font-size:12px;
  }
  .glance {
    width:100%;
    border-right:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-bottom:1px solid ' . $GLOBALS['TABLEBG'] . ';
  }
  .glance th.empty {
    border-top:1px solid ' . $GLOBALS['BGCOLOR'] . ';
    border-left:1px solid ' . $GLOBALS['BGCOLOR'] . ';
    background:' . $GLOBALS['BGCOLOR'] . ';
  }
  .glance th.row {
    width:14%;
    height:40px;
    border-top:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
    vertical-align:middle;
  }
  .glance td {
    border-top:1px solid ' . $GLOBALS['TABLEBG'] . ';
    border-left:1px solid ' . $GLOBALS['TABLEBG'] . ';
    padding-left:3px;
    ' . background_css ( $GLOBALS['CELLBG'], 50 ) . '
    vertical-align:top;
  }
  #day .glance td {
    width:86%;
    height:40px;
  }
  #day dl.desc {
    margin:0;
    padding:0;
  }
  #day dl.desc dt {
    font-weight:bold;
  }
  #day dl.desc dd {
    margin:0;
    padding-left:20px;
  }
  #year #monthgrid td {
    padding:0 5px;
    vertical-align:top;
  }
  #year .minical tr {
    vertical-align:top;
  }
  #viewm .main,
  #viewr .main,
  #viewt .main,
  #viewv .main,
  #week .main {
    border-collapse:collapse;
  }
  #pref .main th,
  #viewv .main th,
  #viewl .main th,
  #month .main th {
    width:14%;
  }
  #vieww .main th,
  #week .main th {
    width:12%;
  }
  #viewr th.small {
    background:none;
    font-size:8px;
  }
  #viewd .main th {
    border-right:1px solid ' . $GLOBALS['TABLEBG'] . ';
    padding:1px;
  }
  a.weekcell {
    color:' . $GLOBALS['WEEKNUMBER'] . ';
  }
  #pref .main th.weekcell,
  #viewl .main th.empty,
  #day .main th.empty,
  #month .main th.empty{
    width:1%;
    border-top:0;
    border-left:0;
    background:transparent;
  }
  #pref .main td.weekcell,
  #viewl .main td.weekcell,
  #month .main td.weekcell {
    width:1%;
    margin:0;
    ' . background_css ( $GLOBALS['THBG'], 50 ) . '
    color:' . $GLOBALS['H2COLOR'] . ';
    font-size:12px;
    text-align:center;
    text-decoration:none;
    vertical-align:middle;
  }
  #pref .main td.empty,
  #pref .main th.empty {
    width:1%;
    border-top-color:transparent;
    border-left-color:transparent;
  }
  #pref .main td.weekcell,
  #pref .main th.weekcell {
    width:1%;
  }
  #viewv .main th.empty,
  #viewm .main th.empty,
  #vieww .main th.empty,
  #viewr .main th.empty,
  #week .main th.empty {
    width:5%;
    border-top:1px solid ' . $GLOBALS['BGCOLOR'] . ';
    border-left:1px solid ' . $GLOBALS['BGCOLOR'] . ';
    background:none;
    background:transparent;
  }
  #week .main th.row {
    width:5%;
    height:40px;
    vertical-align:top;
  }
  #viewt.main {
    margin:0;
    padding:0;
  }
  #vieww .main th.row,
  #viewv .main th.row,
  #viewm .main th.row,
  #viewt .main th {
    width:10%;
    vertical-align:top;
  }
  #viewt .main th.weekend {
    ' . background_css ( $GLOBALS['WEEKENDBG'], 15 ) . '
  }
  #viewv .main th.row {
    padding:0 5px;
    text-align:left;
  }
  #viewd .main th.row {
    border-right-width:0;
    text-align:left;
  }
  #viewr th.row {
    height:40px;
    vertical-align:top;
  }
  #vieww .main th.today,
  #viewm .main th.today,
  #viewv .main th.today {
    width:10%;
  }
  #pref .main th.today,
  #viewr .main th.today,
  #week .main th.today {
    width:14%;
  }
  #viewr .main th a,
  #week .main th a,
  #weekdetails .main th a {
    color:' . $GLOBALS['THFG'] . ';
  }
  #viewr .main th a:hover,
  #week .main th a:hover,
  #weekdetails .main th a:hover {
    color:#0000ff;
  }
  #year .minical td {
    text-align:center;
    vertical-align:top;
  }
  #pref .main td {
    height:30px;
    font-size:12px;
  }
  #viewl .main td,
  #month .main td {
    height:75px;
    font-size:12px;
    /*overflow:auto;*/
    table-layout:fixed;
    vertical-align:top;
  }
  #vieww .main td,
  #week .main td,
  #viewr .main td,
  #viewm .main td,
  #viewv .main td {
    font-size:12px;
    padding-left:3px;
  }' . ( $DISPLAY_TASKS != 'Y' ? '
  #month #prevmonth,' : '' ) . '
  #viewl #prevmonth {
    float:left;
  }' . ( $DISPLAY_TASKS != 'Y' ? '
  #month #nextmonth,' : '' ) . '
  #viewl #nextmonth {
    float:right;
  }
  #month .minical caption,
  #viewl .minical caption {
    margin-left:4ex;
  }
  #year .minical {
    display:block;
  }
  #year .minical caption {
    margin:0 auto;
  }
  #viewl .minical,
  #month .minical {
    margin:0 4px;
    border:0;
  }
  .topnav {
    border:0;
  }
  .title {
    width:99%;
    text-align:center;
  }
  #day .title {
    margin-top:3px;
    text-align:center;
  }
  #day .title .date,
  .title .date {
    color:' . $GLOBALS['H2COLOR'] . ';
    font-weight:bold;
    font-size:24px;
    text-align:center;
  }
  .title .titleweek {
    color:' . $GLOBALS['H2COLOR'] . ';
    font-size:20px;
  }
  .title .viewname,
  #day .title .user,
  .title .user {
    color:' . $GLOBALS['H2COLOR'] . ';
    font-weight:bold;
    font-size:18px;
    text-align:center;
  }
  #weekdetails .main {
    width:90%;
  }
  #weekdetails .main th {
    width:100%;
    padding:2px;
  }
  #weekdetails .main td {
    height:75px;
  }
  #viewt table.timebar {
    width:100%;
    border-collapse:collapse;
  }
  #viewt td.timebar {
    width:90%;
    background:#ffffff;
    color:#909090;
    font-size:10px;
    text-align:center;
  }
  #viewt .yardstick td {
    padding:0;
    border:1px solid #909090;
  }
  #viewt td.entry {
    padding:0;
    ' . background_css ( $GLOBALS['THBG'], 10 ) . '
  }
  #viewt table.timebar a {
    text-align:inherit !important;
  }
	#viewt table.timebar td {
    width: 1%;
  }
  .viewnav {
    width:99%;
    border:0;
  }
  #login {
    margin-top:30px;
    margin-bottom:50px;
  }
  #register table,
  #login table {
    border:1px solid ' . $GLOBALS['TABLEBG'] . ';
    ' . background_css ( $GLOBALS['CELLBG'], 200 ) . '
    font-size:12px;
  }
  .cookies {
    font-size:13px;
  }
  .strikethrough {
    text-decoration:line-through;
  }
  .pub {
    background:#80FF80;
    text-align:center;
  }
  .conf {
    background:#FFFF80;
    text-align:center;
  }
  .priv {
    background:#FF5050;
    text-align:center;
  }
  .boxtop {
   border-top:1px solid #808080;
  }
  .boxright {
   border-right:1px solid #808080;
  }
  .boxbottom {
   border-bottom:1px solid #808080;
  }
  .boxleft {
   border-left:1px solid #808080;
  }
  .boxall {
   border:1px solid #808080;
   padding-left:3px;
  }
  .leftpadded {
   padding-left:50px;
   text-align:left;
  }
  .location {
   font-size:10px;
  }
  .byxxx th,
  .byxxx td {
   text-align:center;
  }
  .icon_text {
   width:10px;
   height:10px;
   border:0;
  }
  .minitask td.pct,
  .alignright {
   text-align:right;
  }
  .minitask td.name,
  .alignleft {
   text-align:left;
  }
  .aligncenter {
   text-align:center;
  }
  .aligntop {
   vertical-align:top;
  }
  .bold {
   font-weight:bold;
  }
  #about {
    background-image:url( images/kn5.jpg );
    background-repeat:no-repeat;
  }
  #about p {
    margin:1px;
    color:#303030;
  }
  #scroller {
    position:absolute;
    width:100%;
  }
  .alt {
  background:' . $CELLBG . ';
  }  
  .layers {
    float:left;
    margin:2px 1px;
    padding:5px;
    background:' . $CELLBG . ';
  }
  .layers h4{
    margin:0 0 5px;
  }
  .layers p {
    margin:0;
    padding-left:5px;
    font-size:12px;
  }
  .layers p label {
    font-size:13px;
  }
  #securityAudit {
    border: 1px solid #c0c0c0;
  }
  #securityAudit th {
    background-color: #d0d0d0;
  }
  #securityAudit .odd {
    background-color: #E0E0E0;
  }
  #securityAudit .even {
    background-color: #ffffff;
  }
  #securityAuditNotes {
    margin: 20px;
    border:1px solid ' . $GLOBALS['TABLEBG'] . ';
    ' . background_css ( $GLOBALS['CELLBG'], 150 ) . '
  }
  #securityAuditNotes li {
    margin-top: 4px;
    margin-bottom: 4px;
  }
  #accountiframe,
  #useriframe {
    width:90%;
    border:0;
  }
  #accountiframe {
    height:210px;
  }
  #useriframe {
    height:280px;
  }
  #eventcomment {
   padding:.25em;
   border:1px solid ' . $GLOBALS['TABLEBG'] . ';
   background:' . $CELLBG . ';
  }' . $end_style;

?>
