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
img.color {
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
<?php //new event icon 
?>.new {
	border-width: 0px;
	width: 10px;
	height: 10px;
	float: right;
}
<?php //event icon 
?>.bullet {
	width: 5px;
	height: 7px;
	border-width: 0px;
	margin-left: 2px;
	margin-right: 2px;
}
.entry {
	font-size: 13px;
	color: #006000;
	text-decoration: none;
}
<?php //styles numerical date links in main calendars
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
.dayofmonth:hover {
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid #0000FF;
	border-bottom: 1px solid #0000FF;
	padding: 0px 2px 0px 3px;
}
<?php //end stuff that stays 
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
  color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
  background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.unapprovedentry {
  font-size: 12px;
  color: #800000;
  text-decoration: none;
}
.layerentry {
  color: #006060;
  text-decoration: none;
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
<?php // standard table appearing mainly in prefs.php & admin.php 
?>table.standard {
  border: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG']; ?>;
  font-size: 12px;
}
table.standard th {
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
<?php // ======================== ACTIVITY_LOG.PHP 
?>#activitylog table,
table.activitylog {
	border-width: 0px;
	width: 100%;
}
#activitylog th,
.activitylog th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
#activitylog td,
.activitylog td {
	vertical-align: top;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	font-size: 13px;
}
<?php // ========================= DAY.PHP 
?>.dayview { }
#day .title,
.dayviewtitle,
#year .title {
	text-align: center;
}
<?php // contains the date (i.e. Monday, May 3, 2004) 
?>#day .title .date,
td.dayviewtitle .date,
#year .title .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#day .minical,
table.dayviewminical {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#day .minical th.date,
.dayviewminical th.date {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	font-size: 47px;
}
#day .minical tr.monthnav th,
.dayviewminical tr.monthnav th {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
#day .minical tr.monthnav td,
.dayviewminical tr.monthnav td {
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
#day .glance,
.glance{
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#day #today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG']; ?>;
}
#day .selectedday {
	border: 1px solid black;
}
<?php // ========================= MONTH.PHP 
?>#month table.main {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#month table.main td {
	font-size: 12px;
	width: 14%;
	height: 75px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo $GLOBALS['CELLBG'];?>;
	vertical-align: top;
}
#month table.main td.weekend {
	font-size: 12px;
	width: 14%;
	height: 75px;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#month table.main td.today {
	font-size: 12px;
	width: 14%;
	height: 75px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	vertical-align: top;
}
<?php // ========================= YEAR.PHP
 // contains ALL months 
?>#year table.main tr {
	vertical-align: top;
}
#year table.main td {
	text-align: center;
	padding: 0px 3px;
}
<?php //keep font-size:12px; for IE6 rendering
?>#year table.minical {
	font-size: 12px;
	border-collapse: collapse;
	margin: 5px auto;
}
#year table.minical caption {
	margin: 0px auto;
}
#year table.minical caption a {
	font-weight: bold;
	color: #B04040;
}
#year table.minical caption a:hover {
	color: #0000FF;
}
#year table.minical th, #year table.minical td.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#year table.minical td {
	padding: 0px 2px;
	border: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
}
#year table.minical td a {
	display: block;
	text-align: center;
	margin: 0px;
	padding: 3px;
}
#year table.minical td.weekend {
	background-color: <?php echo $GLOBALS['WEEKENDBG']; ?>;
}
#year table.minical td#today {
	background-color: <?php echo $GLOBALS['TODAYCELLBG']; ?>;
}
#year table.minical td.hasevents {
	background-color: #ddddff;
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
<?php // ======================= VIEW_M.PHP 
?>#viewm th,
#viewd th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
#vieww th.today,
#viewm th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	width: 10%;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-collapse: collapse;
	vertical-align: top;
}
<?php // ======================== VIEW_V.PHP 
?>#viewv table,
#viewm table,
#viewl table.main,
#viewt table,
table.viewt {
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>; 
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-collapse: collapse;
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
#viewm th.weekend,
#viewv th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	vertical-align: top;
	font-size:13px;
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
<?php // ======================= VIEW_W.PHP 
?>#vieww .title,
#viewd .title,
#viewl .title,
#viewm .title,
#viewt .title,
.viewttitle,
#viewv .title,
#month .title {
	text-align: center;
}
#vieww .title .date,
#viewd .title .date,
#viewl .title .date,
#viewm .title .date,
#viewt .title .date,
.viewttitle .date,
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
.viewttitle .viewname,
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
<?php // ========================= WEEK.PHP 
?>#week table {
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
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
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
<?php // ===================== WEEK_DETAILS.PHP 
?>#weekdetails table {
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
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
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
