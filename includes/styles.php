<?
if (preg_match("/\/includes\//", $PHP_SELF)) {
    die ("You can't access this file directly!");
}
?>
<?php /* 
   HOW TO READ THIS DOCUMENT
	Below are CSS styles used in WebCalendar. There are two main parts to every CSS style: 'selector' & 'declaration'
		EXAMPLE:
			body {
				color: red;
			}
		The selector in the example above is 'body', while its declaration is 'color: red;'
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
		NOTE: The declarations for a given style must be contained within curly brackets ({ })

   VARIABLES USED TO STYLE WEBCALENDAR
	TEXTCOLOR - default text color
	FONTS - default font-family
	BGCOLOR - background-color for the page
	TABLEBG - background-color for tables (typically used when the table also has cellspacing, thereby
		creating a border effect)
	CELLBG - background-color for normal cells (not weekends, today, or any other types of cells)
	TODAYCELLBG - background-color for cells that make up today's date
	WEEKENDBG - background-color for cells that make up the weekend
	THFG - text color for table headers
	THBG - background-color for table headers
	POPUP_FG - text color for event popups
	POPUP_BG - background-color for event popups
	H2COLOR - text color for text within h2 tags
*/
?>
<style type="text/css">
<!--
<?php //=============================== SECTION A ==========================================
	/* The CSS for WebCalendar is broken down into several sections.
	This should make it easier to understand, debug & understand the logical sequence
	of how the new style system is built.
	Each page in WebCalendar is assigned a unique ID. This unique ID is determined by 
	taking the name of the page & removing any underscores (_). For a complete list of
	and their IDs, look in includes/init.php.

	The following sections appear below:
		Section A - basic, required elements that affect WebCalendar as a whole
		Section R - more specific to select areas of WebCalendar, yet still 
			affects many areas of WebCalendar
		Section B - classes specific to certain pages, but that affect either 
			the page as a whole, or large areas within that page
		Section S - the "nitty gritty" of classes. Used specifically for 
			fine-tuning elements within a specific page
*/
?>body {
	color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#edituser,
#groupedit,
#groupedithandler,
#edituserhandler {
	background-color: #F8F8FF;
	color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
}
#tabs {
	font-size: 1.2em;
}
#tabscontent {
	margin: 0px;
	padding: 0.5em;
	border: 2px groove #C0C0C0;
	width: 70%;
	background-color: #F8F8FF;
}
span.tabfor {
	padding: 0.2em 0.2em 0.07em 0.2em;
	margin: 0px 0.2em 0px 0.5em;
	border-top: 2px ridge #C0C0C0;
	border-left: 2px ridge #C0C0C0;
	border-right: 2px ridge #C0C0C0;
	border-bottom: 2px solid #F8F8FF;
	background-color: #F8F8FF;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
}
span.tabbak {
	padding: 0.2em 0.2em 0px 0.2em;
	margin: 0 0.2em 0 0.8em;
	border-top: 2px ridge #C0C0C0;
	border-left: 2px ridge #C0C0C0;
	border-right: 2px ridge #C0C0C0;
	background-color: #E0E0E0;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
}
span.tabbak a {
	text-decoration: none;
	color: #000000;
} 
span.tabfor a {
	text-decoration: none;
	color: #000000;
}
#tabscontent_public,
#tabscontent_groups,
#tabscontent_nonuser,
#tabscontent_other,
#tabscontent_email,
#tabscontent_colors,
#tabscontent_participants,
#tabscontent_sched,
#tabscontent_pete,
#tabscontent_export,
#useriframe,
#grpiframe {
	display: none;
}
label {
	font-weight: bold;
	font-size: 12px;
}
.sample {
	border-style: groove;
}
<?php //week number in monthview & such 
?>.weeknumber,
.weeknumber a {
	font-size: 10px;
	color: #B04040;
	text-decoration: none;
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
	border-top: 1px solid #000000;
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
}
#weekform {
	text-align: center;
}
#yearform {
	text-align: right;
	clear: right;
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
	color: #006000;
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
	color: #000000;
	font-weight: bold;
	text-decoration: none;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid #888888;
	border-bottom: 1px solid #888888;
	padding: 0px 2px 0px 3px;
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
<?php //====================================== /SECTION A ================================

      //====================================== SECTION R =================================
?>#activitylog .prev {
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
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
}
td.matrixappts {
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
}
.nav {
	font-size: 14px;
	color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
	text-decoration: none;
}
.popup {
	font-size: 12px;
	color: <?php echo $GLOBALS['POPUP_FG']; ?>;
	background-color: <?php echo $GLOBALS['POPUP_BG']; ?>;
	text-decoration: none;
	position: absolute;
	z-index: 20;
	visibility: hidden;
	top: 0px;
	left: 0px;
	border: 1px solid <?php echo $GLOBALS['POPUP_FG']; ?>;
	padding: 3px;
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
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  font-size: 18px;
  padding: 0px;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
<?php //Styles for minicalendars
      //keep font-size:12px for IE6
?>.minical {
	font-size: 12px;
	border-collapse: collapse;
}
.minical caption a {
	font-weight: bold;
	color: #B04040;
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
}
.minical td#today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG']; ?>;
}
.minical td.hasevents {
	background-color: #DDDDFF;
	font-weight: bold;
}
#activitylog table,
.embactlog {
	width: 100%;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-spacing: 0px;
}
#activitylog tr,
.embactlog tr {
	background-color: #FFFFFF;
}
#activitylog .odd,
.embactlog .odd {
	background-color: #EEEEEE;
}
#activitylog th,
.embactlog th {
	width: 14%;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	padding: 1px 3px;
}
#activitylog th.usr,
.embactlog th.usr,
#activitylog th.cal,
.embactlog th.cal,
#activitylog th.action,
.embactlog th.action {
	width: 7%;
}
#activitylog td,
.embactlog td {
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	padding: 1px 3px;
}
#day div.minicalcontainer {
	text-align: right;
	border: 1px solid #000000;
	padding: 3px;
}
<?php //the really big number above the minicalendar in day.php
?>#day .minical caption {
	text-align: center;
	font-weight: bold;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	font-size: 47px;
}
#day .minical td.selectedday {
	border: 2px solid #000000;
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
.glance th.row {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	height: 40px;
	width: 14%;
	color: <?php echo $GLOBALS['THFG']; ?>;
	font-size: 13px;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	vertical-align:top;
}
.glance td {
	vertical-align: top;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
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
#viewv table,
#viewm table,
.viewt {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>; 
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	border-collapse: collapse;
}
#vieww table,
#week table {
	width: 100%;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewl .main,
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
#viewv th,
#viewl .main th,
#month .main th {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
#vieww th,
#week th {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
#viewm th {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
<?php //participants cell
?>#viewd th {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	padding: 1px;
}
#viewv th.empty,
#viewm th.empty,
#vieww th.empty,
#week th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#week th.row {
	width: 10%;
	vertical-align: top;
	height: 40px;
}
#vieww th.row,
#viewv th.row,
#viewm th.row,
#viewt th.row {
	width: 10%;
	vertical-align: top;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewd th.row {
	border-right-width: 0px;
	text-align: left;
}
#vieww th.today,
#viewm th.today,
#viewv th.today,
#viewt th.today {
	width: 10%;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#week th.today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
	width: 12%;
}
#week th a,
#weekdetails th a {
	color: <?php echo $GLOBALS['THFG']; ?>;
}
#week th a:hover,
#weekdetails th a:hover {
	color: #0000FF;
}
#year .main td {
	text-align: center;
	padding: 0px 3px;
}
#viewl .main td,
#month .main td {
	font-size: 12px;
	height: 75px;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
	vertical-align: top;
}
#vieww td,
#week td,
#viewm td,
#viewv td {
	font-size: 12px;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
	vertical-align: top;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	padding-left: 3px;
}
#vieww td,
#week td {
	width: 12%;
}
#viewl .main td.weekend,
#month .main td.weekend,
#viewm td.weekend,
#viewv td.weekend,
#vieww td.weekend,
#week td.weekend {
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
	border-bottom-width: 0px;
	border-right-width: 0px;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewt td.today {
	width: 90%;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewl .main td.today,
#month .main td.today,
#viewm td.today,
#vieww td.today,
#viewv td.today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#month #prevmonth,
#viewl #prevmonth {
	float: left;
}
#month #nextmonth,
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
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
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
#weekdetails table {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 90%;
}
#weekdetails th {
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	padding: 2px;
}
#weekdetails td {
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	vertical-align: top;
	height: 75px;
}
<?php //must appear after th & td declarations
      //optionally, you can change this to read "#weekdetails td.today" to color today's cell instead of the header
      //to color both the cell & the header with this style, simply remove the "th" below
?>#weekdetails th.today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
}
<?php //must appear after th & td declarations
      //optionally, you can change this to read "#weekdetails th.weekend" to color the weekend headers instead of the cells
      //to color both the cell & the header with this style, simply remove the "td" below
?>#weekdetails td.weekend {
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
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
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
-->
</style>
