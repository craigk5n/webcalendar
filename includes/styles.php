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
table.activitylog,
#activitylog table {
	border-width: 0px;
	width: 100%;
}
.activitylog th,
#activitylog th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
.activitylog td,
#activitylog td {
	vertical-align: top;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	font-size: 13px;
}

<?php // ========================= DAY.PHP ?>
.dayview { }
.dayviewtitle,
#day .title,
.yearviewtitle,
#year .title {
	text-align: center;
}
<?php // contains the date (i.e. Monday, May 3, 2004) ?>
td.dayviewtitle .date,
#day .title .date,
.yearviewtitle .date,
#year .title .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
table.dayviewminical,
#day .minical {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.dayviewminical th.date,
#day .minical th.date {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	font-size: 47px;
}
.dayviewminical tr.monthnav th,
#day .minical tr.monthnav th {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
.dayviewminical tr.monthnav td,
#day .minical tr.monthnav td {
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
.glance,
#day .glance {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}

<?php // ========================= MONTH.PHP ?>
table.monthview,
#month table {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}

table.monthview td,
#month table td {
  font-size: 12px;
  width: 14%;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
  vertical-align: top;
}
table.monthview td.weekend,
#month table td.weekend {
  font-size: 12px;
  width: 14%;
  height: 75px;
  vertical-align: top;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
table.monthview td.today,
#month table td.today {
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
table.yearview,
#year table {
	border-width: 0px;
}
.yearview tr,
#year tr {
	vertical-align: top;
}
.yearview th,
#year th {
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
table.viewd,
#viewd table {
	border-width: 0px;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewd th.row,
#viewd th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	text-align: left;
	font-weight: normal;
	font-size: 13px;
}

<?php // ======================= VIEW_L.PHP ?>
.viewv th,
#viewv th,
.viewl th,
#viewl th,
.monthview th,
#month th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
table.viewlminical,
#viewl table.minical,
table.yearviewminical,
#year table.minical,
table.monthviewminical,
#month table.minical {
	border-width: 0px;
}
.viewlminical td.month,
#viewl .minical td.month,
.yearviewminical td.month,
#year .minical td.month,
.monthviewminical td.month,
#month .minical td.month {
	text-align: center;
}
<?php // contains the name of the month (i.e. January, June, December, etc) ?>
.viewlminical td.month a,
#viewl .minical td.month a,
.yearviewminical td.month a,
#year .minical td.month a,
.monthviewminical td.month a,
#month .minical td.month a {
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
.viewlminical td.month a:hover,
#viewl .minical td.month a:hover,
.yearviewminical td.month a:hover,
#year .minical td.month a:hover,
.monthviewminical td.month a:hover,
#month .minical td.month a:hover {
	color: #0000FF;
	font-size: 13px;
	text-decoration: none;
}
<?php // formats the day name (i.e. Sun, Mon, etc)
      // used as "tr class="day"" to format the cells WITHIN that row ?>
.viewlminical tr.day,
#viewl .minical,
.yearviewminical tr.day,
#year .minical tr.day,
.monthviewminical tr.day,
#month .minical tr.day,
.dayviewminical tr.day,
#day .minical tr.day {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-align: center;
}
<?php // cells that contain the numeric date ?>
.viewlminical td.numdate,
#viewl .minical td.numdate,
.yearviewminical td.numdate,
#year .minical td.numdate,
.viewlminical tr.numdate td,
#viewl .minical tr.numdate td,
.yearviewminical tr.numdate td,
#year .minical tr.numdate td,
.monthviewminical td.numdate,
#month .minical td.numdate,
.monthviewminical tr.numdate td,
#month .minical tr.numdate td {
	text-align: right;
}
.viewlminical td.numdate a,
#viewl .minical td.numdate a,
.yearviewminical td.numdate a,
#year .minical td.numdate a,
.monthviewminical td.numdate a,
#month .minical td.numdate a {
	font-size: 13px;
	text-decoration: none;
}
.viewlminical td.numdate a:hover,
#viewl .minical td.numdate a:hover,
.yearviewminical td.numdate a:hover,
#year .minical td.numdate a:hover,
.monthviewminical td.numdate a:hover,
#month .minical td.numdate a:hover {
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
}

<?php // ======================= VIEW_M.PHP ?>
.viewm th,
#viewm th,
.viewd th,
#viewd th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
.vieww th.today,
#vieww th.today,
.viewm th.today,
#viewm th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	width: 10%;
	vertical-align: top;
}

<?php // ======================== VIEW_V.PHP ?>
table.viewv,
#viewv table,
table.viewm,
#viewm table,
table.viewl,
#viewl table,
table.viewt,
#viewt table {
	border-width: 0px;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewv th.empty,
#viewv th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewv th.weekend,
#viewv th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewv th.today,
#viewv th.today {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#000000" : $GLOBALS['THFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewv th.row,
#viewv th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}

<?php // ======================= VIEW_W.PHP ?>
.viewwtitle,
#vieww .title,
.viewdtitle,
#viewd .title,
.viewltitle,
#viewl .title,
.viewmtitle,
#viewm .title,
.viewttitle,
#viewt .title,
.viewvtitle,
#viewv .title,
.monthviewtitle,
#month .title {
	text-align: center;
}
.viewwtitle .date,
#vieww .title .date,
.viewdtitle .date,
#viewd .title .date,
.viewltitle .date,
#viewl .title .date,
.viewmtitle .date,
#viewm .title .date,
.viewttitle .date,
#viewt .title .date,
.viewvtitle .date,
#viewv .title .date,
.monthviewtitle .date,
#month .title .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewwtitle .viewname,
#vieww .title .viewname,
.viewdtitle .viewname,
#viewd .title .viewname,
.viewltitle .viewname,
#viewl .title .viewname,
.viewmtitle .viewname,
#viewm .title .viewname,
.viewttitle .viewname,
#viewt .title .viewname,
.viewvtitle .viewname,
#viewv .title .viewname,
td.dayviewtitle .user,
#day .title .user,
.yearviewtitle .user,
#year .title .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.vieww,
#vieww table {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.vieww th,
#vieww th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
.vieww th.weekend,
#vieww th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
.vieww th.empty,
#vieww th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 10%;
}
.vieww td,
#vieww td,
.viewv td,
#viewv td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
}
.viewv td.weekend,
#viewv td.weekend,
.vieww td.weekend,
#vieww td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
}
.vieww td.today,
#vieww td.today,
.viewv td.today,
#viewv td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}

<?php // ========================= WEEK.PHP ?>
table.weekview,
#week table {
	border-width: 0px;
	width: 100%;
}
.weekview th,
#week th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
.weekview th a,
#week th a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
.weekview th a:hover,
#week th a:hover {
	font-size: 14px;
	color: #0000FF;
}
.weekview th.empty,
#week th.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.weekview th.today,
#week th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
.weekview th.today a,
#week th.today a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
}
.weekview th.today a:hover,
#week th.today a:hover {
	font-size: 14px;
	color: #0000FF;
}
.weekview th.row,
#week th.row {
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
.weekview td,
#week td {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
}
.weekview td.today,
#week td.today {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
.weekviewtitle,
#week .title {
	text-align: center;
}
.weekviewtitle .date,
#week .title .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.weekviewtitle .weeknumber,
#week .title .weeknumber {
	font-size: 20px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.weekviewtitle .user,
#week .title .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}

<?php // ===================== WEEK_DETAILS.PHP ?>
table.weekdetails,
#weekdetails table {
	border-width: 0px;
	width: 90%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.weekdetails th,
#weekdetails th {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.weekdetails th a,
#weekdetails th a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
.weekdetails th a:hover,
#weekdetails th a:hover {
	font-size: 14px;
	color: #0000FF;
}
.weekdetails th.today,
#weekdetails th.today {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.weekdetails th.today a,
#weekdetails th.today a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
}
.weekdetails th.today a:hover,
#weekdetails th.today a:hover {
	font-size: 14px;
	color: #0000FF;
}
.weekdetails th.weekend,
#weekdetails th.weekend {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.weekdetails th.weekend a,
#weekdetails th.weekend a {
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
.weekdetails th.weekend a:hover,
#weekdetails th.weekend a:hover {
	font-size: 14px;
	color: #0000FF;
}
.weekdetails td,
#weekdetails td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	vertical-align: top;
	height: 75px;
}
.weekdetails td.today,
#weekdetails td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	vertical-align: top;
	height: 75px;
}
.weekdetails td.weekend,
#weekdetails td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	vertical-align: top;
	height: 75px;
}
-->
</style>
