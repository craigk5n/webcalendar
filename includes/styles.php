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
	TABLECELLFG - text color for normal cells (not weekends, today, or any other types of cells)
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
body {
	color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
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
?>#category {
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
	width: 10px;
	height: 10px;
	float: right;
}
<?php //links to unapproved entries/events
?>.unapprovedentry {
	font-size: 13px;
	color: #800000;
	text-decoration: none;
}
<?php //links to entries/events on layers
?>.layerentry {
	font-size: 13px;
	color: #006060;
	text-decoration: none;
}
<?php //links to entries/events
?>.entry {
	font-size: 13px;
	color: #006000;
	text-decoration: none;
}
<?php //event (or bullet) icon; NOTE: must appear AFTER the .entry, .layerentry, and .unapprovedentry classes
?>.entry img,
.layerentry img,
.unapprovedentry img {
	width: 5px;
	height: 7px;
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
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid #0000FF;
	border-bottom: 1px solid #0000FF;
	padding: 0px 2px 0px 3px;
}
<?php //left arrow images
?>.prev img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-left: 3px;
	margin-top: 7px;
	float: left;
}
<?php //right arrow images
?>.next img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-right: 3px;
	margin-top: 7px;
	float: right;
}
<?php //left arrow image in day.php
?>#day .prev img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-top: 37px;
	float: left;
}
<?php //right arrow image in day.php
?>#day .next img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-top:37px;
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
.tablecell {
  font-size: 12px;
  width: 14%;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
  vertical-align: top;
}
.tablecellweekend {
  font-size: 12px;
  width: 14%;
  height: 75px;
  vertical-align: top;
  background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheader {
  font-size: 14px;
  vertical-align: top;
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['THBG']; ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheadertoday {
  font-size: 14px;
  vertical-align: top;
  color: <?php echo $GLOBALS['THFG']; ?>;
  background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.nav {
  font-size: 14px;
  color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
  text-decoration: none;
}
.popup {
	font-size: 12px;
	color: <?php echo ( $GLOBALS['POPUP_FG'] == "" ? "#000000" : $GLOBALS['POPUP_FG'] ); ?>;
	background-color: <?php echo $GLOBALS['POPUP_BG']; ?>;
	text-decoration: none;
	position: absolute;
	z-index: 20;
	visibility: hidden;
	top: 0px;
	left: 0px;
	border: 1px solid <?php echo ( $GLOBALS['POPUP_FG'] == "" ? "#000000" : $GLOBALS['POPUP_FG'] ); ?>;
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
p {
	font-size: 12px;
}
input {
	font-size: 12px;
}
select {
	font-size: 12px;
}
textarea {
	font-size: 12px;
	overflow: auto;
}
<?php //left & right arrow images 
?>.prevnext {
	border-width: 0px;
	width: 36px;
	height: 32px;
}
.prevnextsmall {
	width: 18px;
	height: 18px;
	border-width: 0px;
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
?>table.standard {
  border: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG']; ?>;
  font-size: 12px;
}
table.standard th {
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
?>.minical thead th {
	color: <?php echo $GLOBALS['TEXTCOLOR']; ?>;
	text-align: center;
}
.minical th, 
.minical td.empty {
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
#activitylog table {
	width: 100%;
	border: 1px solid #000000;
	border-spacing: 0px;
}
#activitylog th {
	color: #000000;
	background-color: #FFFFFF;
	border-right: 1px solid #AAAAAA;
	border-bottom: 1px solid #000000;
	padding: 1px 3px;
}
#activitylog th.usr,
#activitylog th.cal,
#activitylog th.action {
	width: 7%;
}
#activitylog th.scheduled,
#activitylog th.dsc {
	width: 14%;
}
#activitylog tr {
	background-color: #FFFFFF;
}
tr.odd {
	background-color: #EEEEEE;
}
#activitylog td {
	vertical-align: top;
	border-right: 1px solid #AAAAAA;
	padding: 1px 3px;
	font-size: 13px;
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
#day .minical tr.monthnav th {
	text-align: center;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-width: 0px;
}
#day .minical tr.monthnav td {
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-width: 0px;
}
.glance {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#day .minical td.selectedday {
	border: 2px solid #000000;
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
#viewl .main,
#month .main {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#month .main td {
	font-size: 12px;
	width: 14%;
	height: 75px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
	vertical-align: top;
}
#month .main td.weekend {
	font-size: 12px;
	width: 14%;
	height: 75px;
	vertical-align: top;
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#month .main td.today {
	font-size: 12px;
	width: 14%;
	height: 75px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
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
<?php //contains ALL months
?>#year .main tr {
	vertical-align: top;
}
#year .main td {
	text-align: center;
	padding: 0px 3px;
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
#viewv th,
#viewl .main th,
#month .main th {
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
#viewl .minical,
#month .minical {
	border-width: 0px;
}
#viewl .minical td.month,
#month .minical td.month {
	text-align: center;
}
<?php //contains the name of the month (i.e. January, June, December, etc) 
?>#viewl .minical td.month a,
#month .minical td.month a {
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
#viewv table,
#viewm table,
.viewt {
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>; 
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	border-collapse: collapse;
}
#viewt td.reg {
	color: <?php echo $GLOBALS['TABLECELLFG']; ?>;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 90%;
}
#viewm th {
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	font-size: 13px;
}
<?php //participants cell
?>#viewd th {
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	font-size: 13px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	padding: 1px;
}
#viewd th.row {
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-right-width: 0px;
	text-align: left;
	font-size: 13px;
}
#viewm td,
#viewv td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
	font-size: 12px;
}
#viewt td.today {
	width: 90%;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewm td.today,
#vieww td.today,
#viewv td.today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#vieww table,
#week table {
	width: 100%;
	border-width-top: 0px;
	border-width-left: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#vieww th,
#week th {
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
	width: 12%;
}
#viewv th.empty,
#viewm th.empty,
#vieww th.empty,
#week th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-width: 0px;
}
#vieww th.today,
#viewm th.today,
#viewv th.today,
#viewt th.today {
	width: 10%;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
	vertical-align: top;
	font-size: 13px;
}
#week th.today {
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
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
#week th.row {
	width: 10%;
	vertical-align: top;
	height: 40px;
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
}
#viewv th.row,
#viewm th.row,
#viewt th.row {
	width: 10%;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	vertical-align: top;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
}
#vieww td,
#week td {
	font-size: 12px;
	width: 12%;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
	vertical-align: top;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 0px;
	border-bottom-width: 0px;
}
#viewt .weekend {
	width: 90%;
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewm td.weekend,
#viewv td.weekend,
#vieww td.weekend,
#week td.weekend {
	font-size: 12px;
	vertical-align: top;
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
	border-bottom-width: 0px;
	border-right-width: 0px;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#week .title,
#weekdetails .title,
#year .title,
#vieww .title,
#viewd .title,
#viewl .title,
#viewm .title,
#viewt .title,
#viewv .title,
#month .title {
	width: 99%;
	text-align: center;
}
#day .title {
	margin-top: 3px;
	text-align: center;
}
#day .title .date,
#year .title .date,
#vieww .title .date,
#viewd .title .date,
#viewl .title .date,
#viewm .title .date,
#viewt .title .date,
#viewv .title .date,
#month .title .date,
#week .title .date,
#weekdetails .title .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#week .title .weeknumber,
#weekdetails .title .weeknumber {
	font-size: 20px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#vieww .title .viewname,
#viewd .title .viewname,
#viewl .title .viewname,
#viewm .title .viewname,
#viewt .title .viewname,
#viewv .title .viewname,
#day .title .user,
#year .title .user,
#week .title .user,
#weekdetails .title .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
#weekdetails table {
	border-width: 0px;
	width: 90%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#weekdetails th {
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['THBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th.today {
	font-size: 13px;
	color: <?php echo $GLOBALS['THFG']; ?>;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	vertical-align: top;
	height: 75px;
}
#weekdetails td.weekend {
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
	vertical-align: top;
	height: 75px;
}
#pref #month table.main td {
	height: 30px;
}
-->
</style>