<style type="text/css">
<!--
<?php //stuff that STAYS ?>
body {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
<?php //week number in monthview & such ?>
.weeknumber {
	font-size: 10px;
	color: #B04040;
	text-decoration: none;
}
<?php //links that don't have a specific class
//NOTE: these must appear ABOVE the 'printer' & all other link-related classes for those classes to work ?>
a {
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}
a:hover {
  color: #0000FF;
}
<?php //printer-friendly links ?>
.printer {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-decoration: none;
}
<?php //new event icon ?>
.new {
	border-width: 0px;
	width: 10px;
	height: 10px;
	float: right;
}
<?php //event icon ?>
.bullet {
	width: 5px;
	height: 7px;
	border-width: 0px;
}



	
.weekview td {
  font-size: 12px;
  width: 14%;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
  vertical-align: top;
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
.weekview td.weekend {
  font-size: 12px;
  width: 14%;
  vertical-align: top;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
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
.dayofmonth {
  font-size: 13px;
  color: #000000;
  text-decoration: none;
  background-color: #E7E7E7;
  padding-left: 1px;
}
.entry {
  font-size: 13px;
  color: #006000;
  text-decoration: none;
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
  /*width: 300px;*/
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
<?php // formats the left & right arrow images ?>
.prevnext {
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
<?php // formats the left column in help sections ?>
.help {
	vertical-align: top;
	font-weight: bold;
}
<?php // standard table appearing mainly in prefs.php & admin.php ?>
table.standard {
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

<?php // 
// ALL STYLES BELOW THIS LINE ARE NEW as of 8 July 2004
// ================== MISC. 
<?php // formerly .monthlink ?>
td.month a {
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

<?php // ======================== ACTIVITY_LOG.PHP ?>
#activitylog table,
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

<?php // ========================= DAY.PHP ?>
.dayview { }
#day .title,
.dayviewtitle,
#year .title,
.yearviewtitle {
	text-align: center;
}
<?php // contains the date (i.e. Monday, May 3, 2004) ?>
#day .title .date,
td.dayviewtitle .date,
#year .title .date,
.yearviewtitle .date{
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

<?php // ========================= MONTH.PHP ?>
#month table,
table.monthview {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#month table td,
table.monthview td {
  font-size: 12px;
  width: 14%;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
  vertical-align: top;
}
#month table td.weekend,
table.monthview td.weekend {
  font-size: 12px;
  width: 14%;
  height: 75px;
  vertical-align: top;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#month table td.today,
table.monthview td.today {
  font-size: 12px;
  width: 14%;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
  vertical-align: top;
}

<?php // ========================= YEAR.PHP
// contains ALL months ?>
#year table,
table.yearview {
	border-width: 0px;
}
#year tr,
.yearview tr {
	vertical-align: top;
}
#year th,
.yearview th {
	font-size: 13px;
/*	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>; */
	width: 14%;
}
.highlight {
	font-size: 12px;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	font-weight: bold;
}

<?php // ======================= VIEW_D.PHP ?>
#viewd table,
table.viewd {
	border-width: 0px;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewd th.row,
.viewd th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	text-align: left;
	font-weight: normal;
	font-size: 13px;
}

<?php // ======================= VIEW_L.PHP ?>
#viewv th,
.viewv th,
#viewl th,
.viewl th,
#month th,
.monthview th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
#viewl table.minical,
table.viewlminical,
#year table.minical,
table.yearviewminical,
#month table.minical,
table.monthviewminical {
	border-width: 0px;
}
#viewl .minical td.month,
.viewlminical td.month,
#year .minical td.month,
.yearviewminical td.month,
#month .minical td.month,
.monthviewminical td.month {
	text-align: center;
}
<?php // contains the name of the month (i.e. January, June, December, etc) ?>
#viewl .minical td.month a,
.viewlminical td.month a,
#year .minical td.month a,
.yearviewminical td.month a,
#month .minical td.month a,
.monthviewminical td.month a {
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
#viewl .minical td.month a:hover,
.viewlminical td.month a:hover,
#year .minical td.month a:hover,
.yearviewminical td.month a:hover,
#month .minical td.month a:hover,
.monthviewminical td.month a:hover {
	color: #0000FF;
	font-size: 13px;
	text-decoration: none;
}
<?php // formats the day name (i.e. Sun, Mon, etc)
      // used as "tr class="day"" to format the cells WITHIN that row ?>
#viewl .minical,
.viewlminical tr.day,
#year .minical tr.day,
.yearviewminical tr.day,
#month .minical tr.day,
.monthviewminical tr.day,
#day .minical tr.day,
.dayviewminical tr.day {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-align: center;
}
<?php // cells that contain the numeric date ?>
#viewl .minical td.numdate,
.viewlminical td.numdate,
#year .minical td.numdate,
.yearviewminical td.numdate,
#viewl .minical tr.numdate td,
.viewlminical tr.numdate td,
#year .minical tr.numdate td,
.yearviewminical tr.numdate td,
#month .minical td.numdate,
.monthviewminical td.numdate,
#month .minical tr.numdate td,
.monthviewminical tr.numdate td {
	text-align: right;
}
#viewl .minical td.numdate a,
.viewlminical td.numdate a,
#year .minical td.numdate a,
.yearviewminical td.numdate a,
#month .minical td.numdate a,
.monthviewminical td.numdate a {
	font-size: 13px;
	text-decoration: none;
}
#viewl .minical td.numdate a:hover,
.viewlminical td.numdate a:hover,
#year .minical td.numdate a:hover,
.yearviewminical td.numdate a:hover,
#month .minical td.numdate a:hover,
.monthviewminical td.numdate a:hover {
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
}

<?php // ======================= VIEW_M.PHP ?>
#viewm th,
.viewm th,
#viewd th,
.viewd th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
#vieww th.today,
.vieww th.today,
#viewm th.today,
.viewm th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	width: 10%;
	vertical-align: top;
}

<?php // ======================== VIEW_V.PHP ?>
#viewv table,
table.viewv,
#viewm table,
table.viewm,
#viewl table,
table.viewl,
#viewt table,
table.viewt {
	border-width: 0px;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewv th.empty,
.viewv th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewv th.weekend,
.viewv th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewv th.today,
.viewv th.today {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#viewv th.row,
.viewv th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}

<?php // ======================= VIEW_W.PHP ?>
#vieww .title,
.viewwtitle,
#viewd .title,
.viewdtitle,
#viewl .title,
.viewltitle,
#viewm .title,
.viewmtitle,
#viewt .title,
.viewttitle,
#viewv .title,
.viewvtitle,
#month .title,
.monthviewtitle {
	text-align: center;
}
#vieww .title .date,
.viewwtitle .date,
#viewd .title .date,
.viewdtitle .date,
#viewl .title .date,
.viewltitle .date,
#viewm .title .date,
.viewmtitle .date,
#viewt .title .date,
.viewttitle .date,
#viewv .title .date,
.viewvtitle .date,
#month .title .date,
.monthviewtitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#vieww .title .viewname,
.viewwtitle .viewname,
#viewd .title .viewname,
.viewdtitle .viewname,
#viewl .title .viewname,
.viewltitle .viewname,
#viewm .title .viewname,
.viewmtitle .viewname,
#viewt .title .viewname,
.viewttitle .viewname,
#viewv .title .viewname,
.viewvtitle .viewname,
#day .title .user,
td.dayviewtitle .user,
#year .title .user,
.yearviewtitle .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
#vieww table,
table.vieww {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#vieww th,
.vieww th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
#vieww th.weekend,
.vieww th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
#vieww th.empty,
.vieww th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 10%;
}
#vieww td,
.vieww td,
#viewv td,
.viewv td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
}
#viewv td.weekend,
.viewv td.weekend,
#vieww td.weekend,
.vieww td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
}
#vieww td.today,
.vieww td.today,
#viewv td.today,
.viewv td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}

<?php // ========================= WEEK.PHP ?>
#week table,
table.weekview {
	border-width: 0px;
	width: 100%;
}
#week th,
.weekview th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
#week th a,
.weekview th a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
#week th a:hover,
.weekview th a:hover {
	font-size: 14px;
	color: #0000FF;
}
#week th.empty,
.weekview th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
#week th.today,
.weekview th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
#week th.today a,
.weekview th.today a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
}
#week th.today a:hover,
.weekview th.today a:hover {
	font-size: 14px;
	color: #0000FF;
}
#week th.row,
.weekview th.row {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	vertical-align: top;
	height: 40px;
}
#week td,
.weekview td {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
}
#week td.today,
.weekview td.today {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
#week .title,
.weekviewtitle {
	text-align: center;
}
#week .title .date,
.weekviewtitle .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#week .title .weeknumber,
.weekviewtitle .weeknumber {
	font-size: 20px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
#week .title .user,
.weekviewtitle .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}

<?php // ===================== WEEK_DETAILS.PHP ?>
#weekdetails table,
table.weekdetails {
	border-width: 0px;
	width: 90%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
#weekdetails th,
.weekdetails th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th a,
.weekdetails th a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
#weekdetails th a:hover,
.weekdetails th a:hover {
	font-size: 14px;
	color: #0000FF;
}
#weekdetails th.today,
.weekdetails th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th.today a,
.weekdetails th.today a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
}
#weekdetails th.today a:hover,
.weekdetails th.today a:hover {
	font-size: 14px;
	color: #0000FF;
}
#weekdetails th.weekend,
.weekdetails th.weekend {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
#weekdetails th.weekend a,
.weekdetails th.weekend a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
#weekdetails th.weekend a:hover,
.weekdetails th.weekend a:hover {
	font-size: 14px;
	color: #0000FF;
}
#weekdetails td,
.weekdetails td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	vertical-align: top;
	height: 75px;
}
#weekdetails td.today,
.weekdetails td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	vertical-align: top;
	height: 75px;
}
#weekdetails td.weekend,
.weekdetails td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	vertical-align: top;
	height: 75px;
}
-->
</style>
