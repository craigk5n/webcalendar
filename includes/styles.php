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


   PHP IN THIS DOCUMENT
	Many of the declarations below include PHP. The following explains how to interpret the PHP.
		EXAMPLE:
			color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
		In the declaration above, the property is "color",
			and the value is "<?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>"
	We'll tackle this is parts..
		1: <?php
			Start tag for PHP. Tells the server to process the contents.
		2: echo (
			Tells PHP to print information (as necessary) until it reaches a matching
				closing parenthesis ()), immediately followed by a semi-colon (;)
				This can be seen at the end of the line in the example above
				It reads: );
			Note: Parenthesis, brackets & curly brackets work just like they do in math.
		3: $GLOBALS['TEXTCOLOR']
			Tells PHP to find out what the setting is for 'TEXTCOLOR'
		4: = "" ?
			Asks PHP, "Is the value you found for 'TEXTCOLOR' blank?"
		5: #000000
			Tells PHP, "If the value you found for 'TEXTCOLOR' is blank, print out '#000000'."
		6: : $GLOBALS['TEXTCOLOR']
			Tells PHP, "If the value you found for 'TEXTCOLOR' IS NOT blank, the print out whatever value
				you found."
		7: );
			Tells PHP to stop printing.  See #2 above.
		8: ?>
			Tells PHP to stop processing until it finds another '<?php' tag.  See #1 above.
	Summary:
		In the example above, the server says to PHP, "Hey, wake up.. I need you to find out if 
			WebCalendar has a setting for 'TEXTCOLOR'."
		PHP tries to find the setting for 'TEXTCOLOR'
			If PHP finds the 'TEXTCOLOR' setting, it says to the server, "Ok, I found a 
				'TEXTCOLOR' setting. What do you want me to do with it?"
			If PHP CANNOT find the 'TEXTCOLOR' setting, it says to the server, "Nope, I couldn't
				find the 'TEXTCOLOR' setting."
		Then..
			If PHP found the 'TEXTCOLOR' setting, the server says, "Great! What value did you find?"
			If PHP did NOT find the 'TEXTCOLOR' setting, the server says, "Ok. Thanks. You can 
				go back to sleep now."
		Then..
			If the server asks for the value, PHP says, "red".
			Otherwise, PHP just goes back to sleep.
		Then..
			If PHP says "red", the server prints it on the page.
			If PHP didn't find the 'TEXTCOLOR' setting, the server prints out "#000000".
	...THE END


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
<?php //stuff that STAYS 
?>body {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
<?php //week number in monthview & such 
?>.weeknumber {
	font-size: 10px;
	color: #B04040;
	text-decoration: none;
}
<?php //links that don't have a specific class
//NOTE: these must appear ABOVE the 'printer' & all other 
//link-related classes for those classes to work 
?>a {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
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
.trailerform {
	float: left;
	width: 33%;
	border-top: 1px solid #000000;
	padding-top: 5px;
	margin-top: 5px;
	margin-bottom: 25px;
}
.trailerform p {
	margin: 0px;
	padding: 0px;
	font-weight: bold;
}
.trailerform p select {
	font-weight: normal;
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
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
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
<?php //formats the left arrow images
?>.prev img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-left: 3px;
	margin-top: 7px;
	float: left;
}
<?php //formats the right arrow images
?>.next img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-right: 3px;
	margin-top: 7px;
	float: right;
}
<?php //formats the left arrow image in day.php
?>#day .prev img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-top: 37px;
	float: left;
}
<?php //formats the right arrow image in day.php
?>#day .next img {
	border-width: 0px;
	width: 36px;
	height: 32px;
	margin-top:37px;
	float: right;
}
<?php //
?>.tablecell {
  font-size: 12px;
  width: 14%;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
  vertical-align: top;
}
.tablecelltoday {
  font-size: 12px;
  width: 14%;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
  vertical-align: top;
}
.tablecelldemo {
  font-size: 10px;
  width: 30px;
  height: 30px;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tablecellweekend {
  font-size: 12px;
  width: 14%;
  height: 75px;
  vertical-align: top;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tablecellweekenddemo {
  font-size: 10px;
  width: 30px;
  height: 30px;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheader {
  font-size: 14px;
  vertical-align: top;
  color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
  background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheadertoday {
  font-size: 14px;
  vertical-align: top;
  color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
  background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.monthlink {
  font-size: 13px;
  color: #B04040;
  text-decoration: none;
}
.navlinks {
  font-size: 14px;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}
.aboutinfo {
  color: #000000;
  text-decoration: none;
  font-size: 13px;
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
.popup dt {
	font-weight: bold;
	margin: 0px;
	padding: 0px;
}
.popup dl {
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
.pagetitle {
	font-size: 24px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	font-weight: bold;
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
.dailymatrix {
  cursor: pointer;
  font-size: 12px;
  text-decoration: none;
}
<?php // formats the left & right arrow images 
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
<?php // formats the left column in help sections 
?>.help {
	vertical-align: top;
	font-weight: bold;
}
<?php //question mark img linking to help sections
?>img.help {
	border-width: 0px;
	cursor: help;
}
<?php // standard table appearing mainly in prefs.php & admin.php 
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
<?php // formerly .monthlink 
?>td.month a {
	font-size: 13px;
	color: #B04040;
	text-decoration: none;
	text-align: center;
}
td.month a:hover {
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
	text-align: center;
}
#activitylog table {
	border-width: 0px;
	width: 100%;
}
#activitylog th.usr,
#activitylog th.cal,
#activitylog th.action {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 7%;
}
#activitylog th.scheduled,
#activitylog th.dsc {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 14%;
}
#activitylog td {
	vertical-align: top;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	font-size: 13px;
}
#day .title {
	margin-top: 3px;
	text-align: center;
}
#year .title {
	text-align: center;
}
<?php // contains the date (i.e. Monday, May 3, 2004) 
?>#day .title .date,
#year .title .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#day .minical {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
<?php //the really big number above the minicalendar in day.php
?>#day .minical th.date {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	font-size: 47px;
}
#day .minical tr.monthnav th {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
#day .minical tr.monthnav td {
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
#day .glance {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#day #today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG']; ?>;
}
#day .selectedday {
	border: 1px solid #OOOOOO;
}
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
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
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
<?php // contains ALL months
?>#year .main tr {
	vertical-align: top;
}
#year .main td {
	text-align: center;
	padding: 0px 3px;
}
<?php //keep font-size:12px; for IE6 rendering
?>#year .minical {
	font-size: 12px;
	border-collapse: collapse;
	margin: 5px auto;
}
#year .minical caption {
	margin: 0px auto;
}
#year .minical caption a {
	font-weight: bold;
	color: #B04040;
}
#year .minical caption a:hover {
	color: #0000FF;
}
#year .minical th, 
#year .minical td.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#year .minical td {
	padding: 0px 2px;
	border: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#year .minical td a {
	display: block;
	text-align: center;
	margin: 0px;
	padding: 3px;
}
#year .minical td.weekend {
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
}
#year .minical td#today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG']; ?>;
}
#year .minical td.hasevents {
	background-color: #DDDDFF;
	font-weight: bold;
}
#viewd table {
	border-width: 0px;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewd th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	text-align: left;
	font-weight: normal;
	font-size: 13px;
}
#viewv th,
#viewl .main th,
#month .main th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
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
<?php // contains the name of the month (i.e. January, June, December, etc) 
?>#viewl .minical td.month a,
#month .minical td.month a {
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
#viewl .minical td.month a:hover,
#month .minical td.month a:hover {
	color: #0000FF;
	font-size: 13px;
	text-decoration: none;
}
<?php // formats the day name (i.e. Sun, Mon, etc)
      // used as "tr class="day"" to format the cells WITHIN that row 
?>#viewl .minical tr.day,
#month .minical tr.day th,
#day .minical tr.day,
.dayviewminical tr.day {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-align: center;
}
<?php // cells that contain the numeric date 
?>#viewl .minical td.numdate,
#viewl .minical tr.numdate td,
#month .minical td.numdate,
#month .minical tr.numdate td {
	text-align: right;
}
#viewl .minical td.numdate a,
#month .minical td.numdate a {
	font-size: 13px;
	text-decoration: none;
}
#viewl .minical td.numdate a:hover,
#month .minical td.numdate a:hover {
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
}
#viewm th,
#viewd th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#vieww th.today,
#viewm th.today {
	font-size: 13px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	width: 10%;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-collapse: collapse;
	vertical-align: top;
}
#viewv table,
#viewm table,
#viewl table.main,
.viewt {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>; 
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#viewt th.row {
	width: 10%;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	vertical-align: top;
	border-top-width: 0px;
	border-left-width: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewt th.today {
	width: 10%;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
	font-size:13px;
}
#viewt td.reg {
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 90%;
}
#viewt td.today {
	width: 90%;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewt .weekend {
	width: 90%;
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewm th.empty,
#viewv th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-collapse: collapse;
}
#viewv th.today {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
	font-size:13px;
}
#viewv th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#vieww .title,
#viewd .title,
#viewl .title,
#viewm .title,
#viewt .title,
#viewv .title,
#month .title {
	text-align: center;
}
#vieww .title .date,
#viewd .title .date,
#viewl .title .date,
#viewm .title .date,
#viewt .title .date,
#viewv .title .date,
#month .title .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#vieww .title .viewname,
#viewd .title .viewname,
#viewl .title .viewname,
#viewm .title .viewname,
#viewt .title .viewname,
#viewv .title .viewname,
#day .title .user,
td.dayviewtitle .user,
#year .title .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
#vieww table {
	border: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#vieww th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
	vertical-align: top;
}
#vieww th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
#vieww th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 10%;
}
#viewm td,
#vieww td,
#viewv td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewm td.weekend,
#viewv td.weekend,
#vieww td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#viewm td.today,
#vieww td.today,
#viewv td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
}
#week table {
	width: 100%;
	border-width-top: 0px;
	border-width-left: 0px;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-collapse: collapse;
}
#week th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
#week th a,
#weekdetails th a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
#week th a:hover,
#weekdetails th a:hover {
	font-size: 14px;
	color: #0000FF;
}
#week th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 0px;
	border-collapse: collapse;
}
#week th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
#week th.today a,
#weekdetails th.today a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
}
#week th.today a:hover,
#weekdetails th.today a:hover {
	font-size: 14px;
	color: #0000FF;
}
#week th.row {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 0px;
	width: 12%;
	vertical-align: top;
	height: 40px;
	border-collapse: collapse;
}
#week td {
	font-size: 12px;
	width: 12%;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
	vertical-align: top;
}
#week td.weekend {
	font-size: 12px;
	width: 14%;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#week td.today {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
#week .title,
#weekdetails .title {
	text-align: center;
	border-width: 0px;
	width: 99%;
}
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
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th.weekend {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th.weekend a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
#weekdetails th.weekend a:hover {
	font-size: 14px;
	color: #0000FF;
}
#weekdetails td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	vertical-align: top;
	height: 75px;
}
#weekdetails td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	vertical-align: top;
	height: 75px;
}
#weekdetails td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	vertical-align: top;
	height: 75px;
}
-->
</style>
