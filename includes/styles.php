<?php
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}
if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/includes\//", $PHP_SELF ) ) {
    die ( "You can't access this file directly!" );
}

/* 

                   HOW TO READ THIS DOCUMENT

  Below are CSS styles used in WebCalendar.
  There are two main parts to every CSS style: 'selector' & 'declaration'
    EXAMPLE:
      body {
        color: red;
      }
  The selector in the example above is 'body', while its
  declaration is 'color: red;'
  Each declaration has two parts: 'property' & 'value'

  In the example above, there is only one declaraion ("color: red;")
  For that declaration, the PROPERTY is "color" and the VALUE is "red"

  NOTE: Each property must be followed by a colon (:), 
    and each value must be followed by a semi-colon (;)

  Each selector can contain multiple declarations
    EXAMPLE:
      body {
        color: red;
        font-size: 12px;
        background-color: black;
      }
  In the example above, there are three declarations:
      color: red;
      font-size: 12px;
      background-color: black;

  NOTE: The declarations for a given style must be contained within
    curly brackets ({ })

                  VARIABLES USED TO STYLE WEBCALENDAR

  TEXTCOLOR - default text color
  FONTS - default font-family
  BGCOLOR - background-color for the page
  TABLEBG - background-color for tables
    (typically used when the table also has cellspacing, thereby
    creating a border effect)
  CELLBG - background-color for normal cells
    (not weekends, today, or any other types of cells)
  TODAYCELLBG - background-color for cells that make up today's date
  WEEKENDBG - background-color for cells that make up the weekend
  OTHERMONTHBG - background-color for cells that belong to other month  
  THFG - text color for table headers
  THBG - background-color for table headers
  POPUP_FG - text color for event popups
  POPUP_BG - background-color for event popups
  H2COLOR - text color for text within h2 tags
*/
 /*==================== SECTION A ===============================

  The CSS for WebCalendar is broken down into several sections.
  This should make it easier to understand, debug & understand the
  logical sequence of how the style system is built.
  Each page in WebCalendar is assigned a unique ID. This unique ID is
  determined by taking the name of the page & removing any underscores (_).
  For a complete list of and their IDs, see includes/init.php or
  docs/WebCalendar-StyleSystem.html.

  The following sections appear below:
    Section A - basic, required elements that affect WebCalendar as a whole
    Section B - more specific to select areas of WebCalendar, yet still 
      affects many areas of WebCalendar
    Section C - classes specific to certain pages, but that affect either 
      the page as a whole, or large areas within that page
    Section D - the "nitty gritty" of classes. Used specifically for 
      fine-tuning elements within a specific page
*/
if ( ! empty ( $PHP_SELF ) && ! preg_match ( "/css_cacher.php/", $PHP_SELF ) ) {
  echo "<style type=\"text/css\">\n<!--\n";
}
/* SECTION A */
?>body {
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
<?php //links that don't have a specific class
//NOTE: these must appear ABOVE the 'printer' & all other 
//link-related classes for those classes to work 
?>a {
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  text-decoration: none;
}
a:hover {
  color: #0000FF;
}
#viewsedit,
#edituser,
#edituserhandler,
#groupedit,
#editnonusers,
#editremotes,
#groupedithandler,
#editnonusershandler,
#editremoteshandler {
  background-color: #F8F8FF;
}
#tabscontent {
  margin: 0px;
  padding: 0.5em;
  border: 2px groove #C0C0C0;
  width: 80%;
  background-color: #F8F8FF;
}
.tabfor {
  padding: 0.2em 0.2em 0.07em 0.2em;
  margin: 0px 0.2em 0px 0.8em;
  border-top: 2px ridge #C0C0C0;
  border-left: 2px ridge #C0C0C0;
  border-right: 2px ridge #C0C0C0;
  border-bottom: 2px solid #F8F8FF;
  background-color: #F8F8FF;
  font-size: 14px;
  text-decoration: none;
  color: #000000;
}
.tabbak {
  padding: 0.2em 0.2em 0px 0.2em;
  margin: 0 0.2em 0 0.8em;
  border-top: 2px ridge #C0C0C0;
  border-left: 2px ridge #C0C0C0;
  border-right: 2px ridge #C0C0C0;
  background-color: #E0E0E0;
  font-size: 14px;
  text-decoration: none;
  color: #000000;
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
label {
  font-weight: bold;
}
.sample {
  border-style: groove;
}
<?php //week number in monthview & such 
?>.weeknumber,
.weeknumber a {
  font-size: 10px;
  color: <?php echo $GLOBALS['WEEKNUMBER']; ?>;
  text-decoration: none;
}
<?php //transparent images used for visual color-selection
?>img.color {
  border-width: 0px;
  width: 15px;
  height: 15px;
}
<?php //display:none; is unhidden by includes/print_styles.css for printer-friendly pages 
?>#cat {
  display: none;
  font-size: 18px;
}
#trailer {
  margin: 0px;
  padding: 0px;
}
#trailer form {
  float: left;
  width: 33%;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding-top: 5px;
  margin-top: 5px;
  margin-bottom: 25px;
}
#trailer label {
  margin: 0px;
  padding: 0px;
  font-weight: bold;
}
#monthform {
  clear: left;
  margin-bottom:0px;
}
#weekform {
  text-align: center;
  margin-bottom:0px;
}
#yearform {
  text-align: right;
  clear: right;
  margin-bottom:0px;
}
#menu {
  clear: both;
}
#menu a {
  font-size: 14px;
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  text-decoration: none;
}
.prefix {
  font-weight: bold;
  font-size: 14px;
}
<?php //link to webcalendar site -- NOTE: by modifying this style, you can make this link disappear
?>a#programname {
  margin-top: 10px;
  font-size: 10px;
}
<?php //printer-friendly links 
?>.printer {
  font-size: 14px;
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  text-decoration: none;
  clear: both;
  display: block;
  width: 15ex;
}
<?php //new event icon (i.e. '+' symbol)
?>.new {
  border-width: 0px;
  float: right;
}
<?php //links to unapproved entries/events
?>.unapprovedentry {
  font-size: 13px;
  color: #800000;
  text-decoration: none;
  padding-right: 3px;
}
.nounapproved {
  margin-left: 20px;
}
<?php //links to entries/events on layers
?>.layerentry {
  font-size: 13px;
  color: #006060;
  text-decoration: none;
  padding-right: 3px;
}
<?php //links to entries/events
?>.entry {
  font-size: 13px;
  color: <?php echo $GLOBALS['MYEVENTS']; ?>;
  text-decoration: none;
  padding-right: 3px;
}
<?php //event (or bullet) icon; NOTE: must appear AFTER the .entry, .layerentry, and .unapprovedentry classes
?>.entry img,
.layerentry img,
.unapprovedentry img {
  border-width: 0px;
  margin-left: 2px;
  margin-right: 2px;
}
<?php //numerical date links in main calendars
?>.dayofmonth {
  font-size: 13px;
  color: <?php echo $GLOBALS['TABLEBG']; ?>;
  font-weight: bold;
  text-decoration: none;
  border-top-width: 0px;
  border-left-width: 0px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding: 0px 2px 0px 3px;
  vertical-align:top;
}
<?php //numerical date links in main calendars on hover
?>.dayofmonth:hover {
  color: #0000FF;
  border-right: 1px solid #0000FF;
  border-bottom: 1px solid #0000FF;
}
<?php //left arrow images
?>.prev img {
  border-width: 0px;
  margin-left: 3px;
  margin-top: 7px;
  float: left;
}
<?php //right arrow images
?>.next img {
  border-width: 0px;
  margin-right: 3px;
  margin-top: 7px;
  float: right;
}
#activitylog .prev {
  border-width: 0px;
  float: left;
}
#activitylog .next {
  border-width: 0px;
  float: right;
}
<?php //left arrow image in day.php
?>#day .prev img {
  border-width: 0px;
  margin-top: 37px;
  float: left;
}
<?php //right arrow image in day.php
?>#day .next img {
  border-width: 0px;
  margin-top: 37px;
  float: right;
}
<?php //left arrow image in day.php
?>#day .monthnav .prev img {
  border-width: 0px;
  margin: 0px;
  float: left;
}
<?php //right arrow image in day.php
?>#day .monthnav .next img {
  border-width: 0px;
  margin: 0px;
  float: right;
}
.dailymatrix {
  cursor: pointer;
  font-size: 12px;
  text-decoration: none;
  text-align: right;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
}
td.matrixappts {
  cursor: pointer;
  text-align: left;
  background-color: <?php echo $GLOBALS['CELLBG']; ?>;
  vertical-align: middle;
}
td.matrix {
  height: 1px;
  background-color: #000000;
}
.matrix img {
  border-width: 0px;
  width: 100%;
  height: 1px;
}
a.matrix img {
  border-width: 0px;
  width: 100%;
  height: 8px;
}
.matrixd {
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  margin-left: auto; 
  margin-right: auto;
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
  border-width: 0px;
  width: 10px;
  height: 10px;
}
.nav {
  font-size: 14px;
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  text-decoration: none;
}
.popup {
  font-size: 12px;
  color: <?php echo $GLOBALS['POPUP_FG']; ?>;
  <?php echo background_css ( $GLOBALS['POPUP_BG'], 200 ); ?>
  text-decoration: none;
  position: absolute;
  z-index: 20;
  visibility: hidden;
  top: 0px;
  left: 0px;
  border: 1px solid <?php echo $GLOBALS['POPUP_FG']; ?>;
  padding: 3px;
  -moz-border-radius: 6px;
}
.popup dl {
  margin: 0px;
  padding: 0px;
}
.popup dt {
  font-weight: bold;
  margin: 0px;
  padding: 0px;
}
.popup dd {
  margin-left: 20px;
}
.tooltip {
  cursor: help;
  text-decoration: none;
  font-weight: bold;
 width:120px;
}
.tooltipselect {
  cursor: help;
  text-decoration: none;
  font-weight: bold;
  vertical-align: top;
}
h2 {
  font-size: 20px;
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
h3 {
  font-size: 18px;
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
.user {
  font-size: 18px;
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
  text-align: center;
}
.categories {
  font-size: 18px;
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
  text-align: center;
}
<?php //left column in help sections 
?>.help {
  vertical-align: top;
  font-weight: bold;
}
<?php //question mark img linking to help sections
?>img.help {
  border-width: 0px;
  cursor: help;
}
<?php //standard table appearing mainly in prefs.php & admin.php 
?>.standard {
  border: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG']; ?>;
  font-size: 12px;
}
.standard th {
  color: <?php echo $GLOBALS['THFG']; ?>;
  <?php echo background_css ( $GLOBALS['THBG'], 100 ); ?>
  font-size: 18px;
  padding: 0px;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
<?php //Styles for minicalendars
      //keep font-size:12px for IE6
?>.minical {
  font-size: 12px;
  border-collapse: collapse;
  margin: 0px 0px 5px 0px;
}
.minical caption a {
  font-weight: bold;
  color: <?php echo $GLOBALS['CAPTIONS']; ?>;
}
.minical caption a:hover {
  color: #0000FF;
}
<?php //formats the day name (i.e. Sun, Mon, etc) in minicals
?>.minical th, 
.minical td.empty {
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  text-align: center;
  background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
<?php if ( $DISPLAY_WEEKENDS == 'N' ) { ?>
.minical th.weekend {
  display: none;
}
<?php } ?>
.minical td {
  padding: 0px 2px;
  border: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
}
.minical td a {
  display: block;
  text-align: center;
  margin: 0px;
  padding: 3px;
}
.minical td.weekend {
  background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
<?php if ( $DISPLAY_WEEKENDS == 'N' ) { ?>
  display: none;
<?php } ?>
}
.minical td#today {
  background-color: <?php echo $GLOBALS['TODAYCELLBG']; ?>;
}
.minical td.hasevents {
  font-weight: bold;
}
<?php // table appearing in small task window 
?>.minitask {
 width:100%;
  border: 1px solid #000000;
}
.minitask tr.header th {
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG']; ?>;
  font-size: 12px;
  padding: 0px;
  border-bottom: 2px solid #000000;
}
.minitask  td {
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  font-size: 12px;
  padding: 0px;
  border-bottom: 1px solid #000000;
  text-align: center;
}
.minitask td.filler {
  padding: 0px;
  border-bottom: 0px;
}
#admin .tooltip,
#pref .tooltip{
  cursor: help;
  text-decoration: none;
  font-weight: bold;
 width:175px;
}
#minicalendar table {
  width: <?php echo ( ! empty ( $GLOBALS['MINICALWIDTH'])?$GLOBALS['MINICALWIDTH']: '160px'); ?>;
}
#minicalendar th{
  font-size: <?php echo ( ! empty ( $GLOBALS['MINICALFONT'])?$GLOBALS['MINICALFONT']: '11px'); ?>;
}
#minicalendar td {
  font-size: <?php echo ( ! empty ( $GLOBALS['MINICALFONT'])?$GLOBALS['MINICALFONT']: '11px'); ?>;
}
.embactlog {
  width: 100%;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-spacing: 0px;
}
.embactlog tr {
  background-color: #FFFFFF;
}
.embactlog .odd {
  background-color: #EEEEEE;
}
.embactlog th {
  width: 14%;
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding: 1px 3px;
}
.embactlog th.usr,
.embactlog th.cal,
.embactlog th.action {
  width: 7%;
}
.embactlog td {
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding: 1px 3px;
}
#day div.minicalcontainer {
  text-align:center;
  border: 1px solid #000000;
  padding: 3px;
}
#day table.minical {
  margin-left:auto; margin-right:auto;
}
<?php //the really big number above the minicalendar in day.php
?>#day .minical caption {
  margin-left:auto; margin-right:auto;
  font-weight: bold;
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  font-size: 47px;
}
#day .minical td.selectedday {
  border: 2px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#day .monthnav th {
  text-align: center;
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  border-width: 0px;
  padding: 3px;
}
.glance {
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 100%;
}
.glance th.empty {
  border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
  background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
.glance th.row {
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  height: 40px;
  width: 14%;
  color: <?php echo $GLOBALS['THFG']; ?>;
  font-size: 13px;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  vertical-align: top;
}
.glance td {
  vertical-align: top;
  <?php echo background_css ( $GLOBALS['CELLBG'], 50 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding-left: 3px;
}
<?php //display: none; is unhidden by includes/print_styles.css for printer-friendly pages
?>#day dl.desc {
  display: none;
  margin: 0px;
  padding: 0px;
}
#day dl.desc dt {
  font-weight: bold;
}
#day dl.desc dd {
  margin: 0px;
  padding-left: 20px;
}
.viewt,
#viewv .main,
#viewm .main,
#vieww .main,
#week .main,
#viewl .main,
#viewr .main,
#month .main {
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 100%;
  clear: both;
}
<?php //contains ALL months
?>#year .main tr {
  vertical-align: top;
}
th {
  font-size: 13px;
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
}
#admin .main th, 
#pref .main th, 
#viewv .main th,
#viewl .main th,
#month .main th {
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  <?php echo background_css ( $GLOBALS['THBG'], 15 ); ?>
  width: 14%;
}
<?php if ( $DISPLAY_WEEKENDS == 'N' ) { ?>
#admin .main th.weekend,
#pref .main th.weekend,
#viewl .main th.weekend,
#viewr .main th.weekend,
#month .main th.weekend {
  display: none;
}
<?php } ?>
#vieww .main th,
#week .main th {
  <?php echo background_css ( $GLOBALS['THBG'], 15 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 12%;
}
#viewr .main th,
#viewm .main th {
  <?php  echo background_css ( $GLOBALS['THBG'], 15 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewr th.small {
  background: none;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  font-size: 8px;
}
<?php //participants cell
?>#viewd .main th {
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding: 1px;
}
a.weekcell {
  color: <?php echo $GLOBALS['WEEKNUMBER']; ?>;
}
#admin .main th.weekcell,
#pref .main th.weekcell,
#viewl .main th.weekcell,
#month .main th.weekcell{
  background: <?php echo $GLOBALS['BGCOLOR']; ?>;
  background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
  border-left: 0px;
  border-top: 0px;
  width: 1%;
}
#admin .main td.weekcell,
#pref .main td.weekcell,
#viewl .main td.weekcell,
#month .main td.weekcell {
  <?php echo background_css ( $GLOBALS['THBG'], 50 ); ?>
  width: 1%;
  margin: 0px 0px 0px 0px;
  vertical-align:middle;
  text-align:center;
  font-size: 12px;
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
  text-decoration: none;
}
#viewv .main th.empty,
#viewm .main th.empty,
#vieww .main th.empty,
#viewr .main th.empty,
#week .main th.empty {
  width: 5%;
  background: none;
  background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
  border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#week .main th.row {
  width: 5%;
  vertical-align: top;
  height: 40px;
}
#vieww .main th.row,
#viewv .main th.row,
#viewm .main th.row,
#viewt th.row {
  width: 10%;
  vertical-align: top;
  <?php echo background_css ( $GLOBALS['THBG'], 15 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewd .main th.row {
  border-right-width: 0px;
  text-align: left;
}
#viewr th.row {
  height: 40px;
  vertical-align: top;
}
#vieww .main th.today,
#viewm .main th.today,
#viewv .main th.today,
#viewt .main th.today {
  width: 10%;
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  vertical-align: top;
}
#admin .main th.today,
#pref .main th.today,
#viewr .main th.today,
#week .main th.today {
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 12%;
}
#viewr .main td.hasevents {
  font-size: 8px;
  <?php echo background_css ( $GLOBALS['HASEVENTSBG'], 100 ); ?>
}
#week .main td.hasevents,
#day .glance td.hasevents {
  <?php echo background_css ( $GLOBALS['HASEVENTSBG'], 100 ); ?>
}
#viewr .main th a,
#week .main th a,
#weekdetails .main th a {
  color: <?php echo $GLOBALS['THFG']; ?>;
}
#viewr .main th a:hover,
#week .main th a:hover,
#weekdetails .main th a:hover {
  color: #0000FF;
}
#year .main td {
  text-align: center;
  padding: 0px 3px;
}
#admin .main td,
#pref .main td{
  font-size: 12px;
  height: 30px;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  <?php echo background_css ( $GLOBALS['CELLBG'], 100 ); ?>
  vertical-align: top;
}
#viewl .main td,
#month .main td {
  font-size: 12px;
  height: 75px;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  <?php echo background_css ( $GLOBALS['CELLBG'], 100 ); ?>
  vertical-align: top;
  table-layout:fixed;
  overflow:auto;
  <?php if ( ! empty ( $browser ) && $browser == 'MSIE' ) { ?>
  word-break: break-all;
  <?php } ?>
}
#vieww .main td,
#week .main td,
#viewr .main td,
#viewm .main td,
#viewv .main td {
  font-size: 12px;
  <?php echo background_css ( $GLOBALS['CELLBG'], 100 ); ?>
  vertical-align: top;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  padding-left: 3px;
}
#admin .main td.weekend,
#pref .main td.weekend,
#viewl .main td.weekend,
#month .main td.weekend,
#viewm .main td.weekend,
#viewv .main td.weekend,
#vieww .main td.weekend,
#viewr .main td.weekend,
#week .main td.weekend {
  <?php echo background_css ( $GLOBALS['WEEKENDBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
<?php if ( $DISPLAY_WEEKENDS == 'N' ) { ?>
 display: none; 
<?php } ?>  
}
#admin .main td.othermonth,
#pref .main td.othermonth,
#viewl .main td.othermonth,
#month .main td.othermonth {
  <?php echo background_css ( $GLOBALS['OTHERMONTHBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#admin .main td.today,
#pref .main td.today,
#viewl .main td.today,
#month .main td.today,
#viewm .main td.today,
#vieww .main td.today,
#viewv .main td.today {
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  vertical-align: top;
}
<?php if ( $DISPLAY_TASKS != 'Y' ) { ?>
#month #prevmonth,
<?php } ?>
#viewl #prevmonth {
  float: left;
}
<?php if ( $DISPLAY_TASKS != 'Y' ) { ?>
#month #nextmonth,
<?php } ?>
#viewl #nextmonth {
  float: right;
}
#month .minical caption,
#viewl .minical caption {
  margin-left: 4ex;
}
<?php //keep font-size:12px; for IE6 rendering
      //display: block; keeps the caption vertically close to the day names
?>#year .minical {
  margin: 5px auto;
  display: block;
}
#year .minical caption {
  margin: 0px auto;
}
#viewl .minical,
#month .minical {
  border-width: 0px;
}
#viewt td.reg {
  <?php echo background_css ( $GLOBALS['CELLBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 90%;
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
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.title .weeknumber {
  font-size: 20px;
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.title .viewname,
#day .title .user,
.title .user {
  font-size: 18px;
  font-weight: bold;
  color: <?php echo $GLOBALS['H2COLOR']; ?>;
  text-align: center;
}
#weekdetails .main {
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 90%;
}
#weekdetails .main th {
  font-size: 13px;
  color: <?php echo $GLOBALS['THFG']; ?>;
  <?php echo background_css ( $GLOBALS['THBG'], 100 ); ?>
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  width: 100%;
  padding: 2px;
}
#weekdetails .main td {
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  <?php echo background_css ( $GLOBALS['CELLBG'], 100 ); ?>
  vertical-align: top;
  height: 75px;
}
<?php /* must appear after th & td declarations
  optionally, you can change this to read "#weekdetails td.today" to
  color today's cell instead of the header
  to color both the cell & the header with this style, simply remove
  the "th" below
*/?>#weekdetails .main th.today {
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 ); ?>
}
<?php /* must appear after th & td declarations
  optionally, you can change this to read "#weekdetails th.weekend" to
  color the weekend headers instead of the cells
  to color both the cell & the header with this style, simply remove
  the "td" below
*/?>#weekdetails .main td.weekend {
  <?php echo background_css ( $GLOBALS['WEEKENDBG'], 100 ); ?>
}
#viewt table {
  border-collapse: collapse;
}
#viewt .timebar {
  padding: 0px;
  width: 100%;
  border-width: 0px;
}
#viewt .timebar td {
  padding: 0px;
  background-color: #FFFFFF;
  text-align: center;
  color: #CCCCCC;
  font-size: 10px;
}
#viewt .yardstick {
  width: 100%;
  padding: 0px;
  border-width: 0px;
}
#viewt .yardstick td {
  background-color: #FFFFFF;
  border: 1px solid #CCCCCC;
}
#viewt .entrycont {
  width: 100%;
  padding: 0px;
  border-width: 0px;
}
#viewt .entrycont td {
  text-align: right;
}
#viewt .entrybar {
  width: 100%;
  border-width: 0px;
}
#viewt .entrybar td.entry {
  text-align: center;
  background-color: #F5DEB3;
  border: 1px solid #000000;
}
#viewt .weekend {
  width: 90%;
  <?php echo background_css ( $GLOBALS['WEEKENDBG'], 100 ); ?>
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  vertical-align: top;
}
#viewt td.today {
  width: 90%;
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 ); ?>
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  vertical-align: top;
}
#viewt th.today {
  color: <?php echo $GLOBALS['THFG']; ?>;
  <?php echo background_css ( $GLOBALS['TODAYCELLBG'], 100 ); ?>
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  vertical-align: top;
}
#login {
  margin-top: 70px;
  margin-bottom: 50px;
  text-align: center;
}
#register table,
#login table {
  border: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  <?php echo background_css ( $GLOBALS['CELLBG'], 200 ); ?>
  font-size: 12px;
}
.cookies {
  font-size: 13px;
}
abbr {
  cursor: help;
}
.strikethrough {
  text-decoration : line-through;
}
.pub {
  background-color:#80FF80;
  text-align:center;
}
.conf {
  background-color:#FFFF80;
  text-align:center;
}
.priv {
  background-color:#FF5050;
  text-align:center;
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
.leftpadded {
 padding-left:50px;
 text-align:left;
}
<?php
if ( ! empty ( $PHP_SELF ) && ! preg_match ( "/css_cacher/", $PHP_SELF ) ) {
  echo "\n-->\n</style>";
}
?>