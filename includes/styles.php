<!-- Note: Although we may set the font size in here, we also make heavy
     use of the HTML font tag since many browsers do not properly support style sheet font settings.
-->
<style type="text/css">
<!--
.tablecell {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  width: 75px;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['CELLBG'];?>;
}
.tablecelltoday {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  width: 75px;
  height: 75px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
}
.tablecelldemo {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 10px;
  width: 30px;
  height: 30px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tablecellweekend {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  width: 80px;
  height: 80px;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tablecellweekenddemo {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 10px;
  width: 30px;
  height: 30px;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheader {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
  background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheadertoday {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
  background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.dayofmonth {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 13px;
  color: #000000;
  text-decoration: none;
  background-color: #E7E7E7;
  padding-left: 1px;
}
.weeknumber {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 10px;
	color: #B04040;
	text-decoration: none;
}
.entry {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: #006000;
  text-decoration: none;
}
.unapprovedentry {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: #800000;
  text-decoration: none;
}
.layerentry {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  color: #006060;
  text-decoration: none;
}
.monthlink {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 13px;
  color: #B04040;
  text-decoration: none;
}
td.month a {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 13px;
	color: #B04040;
	text-decoration: none;
	align:center;
}
.navlinks {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}

a {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}
a:hover {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  color: #0000FF;
}
.aboutinfo {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  color: #000000;
  text-decoration: none;
  font-size: 13px;
}
.popup {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: <?php echo ( $GLOBALS['POPUP_FG'] == "" ? "#000000" : $GLOBALS['POPUP_FG'] ); ?>;
  background-color: <?php echo $GLOBALS[POPUP_BG] ?>;
  text-decoration: none;
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
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 20px;
  color: <?php echo $GLOBALS['H2COLOR'] ?>;
}
h3 {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 18px;
}
.pagetitle {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 24px;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	font-weight: bold;
}
body {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['BGCOLOR'] ?>;
}
td {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
}
p {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
}
input {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
}
select {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
}
textarea {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
}
.dailymatrix {
  cursor:pointer;
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  text-decoration: none;
}
/* formats the left & right arrow images */
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
td.numericdate {
	font-size: 10px;
}
.user {
	font-size: 18px;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	text-align: center;
}
.categories {
	font-size: 18px;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	text-align: center;
}
.help {
	vertical-align: top;
	font-weight: bold;
}


/* ALL STYLES BELOW THIS LINE ARE NEW as of 8 July 2004 */

/* These styles are intentionally placed above all other styles */
/* to serve as a default, which can be overridden if customized otherwise below */
th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}


/* ======================== */
/* ACTIVITY_LOG.PHP */
table.activitylog {
	border-width: 0px;
	width: 100%;
}
.activitylog th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	width: 10%;
}
.activitylog td {
	vertical-align: top;
	background-color: <?php echo $GLOBALS['CELLBG'] ?>;
	font-size: 13px;
}


/* ========================= */
/* DAY.PHP */
.dayview { }
.dayviewtitle {
	text-align: center;
}
/* contains the date (i.e. Monday, May 3, 2004) in day.php */
td.dayviewtitle .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
}
td.dayviewtitle .user {
	font-size: 18px;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	font-weight: bold;
	text-align: center;
}

/* ========================= */
/* MONTH.PHP */
table.monthview {
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.monthviewtitle {
	text-align: center;
}
td.monthviewtitle .date {
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	font-weight: bold;
	font-size: 24px;
}
.monthview th {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}

/* ========================= */
/* YEAR.PHP */
/* contains ALL months in year.php */
table.yearview {
	border-width: 0px;
}
.yearview tr {
	vertical-align: top;
}
.yearviewtitle {
	text-align: center;
}
/* contains the year (i.e. 2004) in year.php */
td.yearviewtitle .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
}
td.yearviewtitle .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	text-align: center;
}
.yearview th {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
/* ========================= */
/* MINI CALENDARS */
/* contains individual months in year.php */
table.minical {
	border-width: 0px;
}
.minical td.month {
	text-align: center;
}
/* contains the name of the month (i.e. January, June, December, etc) in year.php */
.minical td.month a {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 13px;
	color: #B04040;
	text-decoration: none;
}
/* formats the day name (i.e. Sun, Mon, etc) */
/* used as "<tr class="day">" to format the cells WITHIN that row */
/* NOTE: removing the "th" below will modify the appearance of mini calendars */
.minical tr.day th {
	font-size: 10px;
	font-weight: normal;
}
/* cells in year.php that contain the numeric date */
/* NOTE: removing the "td" below will modify the appearance of mini calendars */
.minical tr.date td {
	text-align: right;
}
.minical tr.date a {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 10px;
	text-decoration: none;
}
td.numericdate {
	font-size: 10px;
}

/* ========================= */
/* WEEK.PHP */
table.weekview {
	border: 0px;
	width: 100%;
}
.weekview th {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
.weekview th.empty {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	background-color: <?php echo $GLOBALS['BGCOLOR'] ?>;
	border-top: 1px solid <?php echo $GLOBALS['BGCOLOR'] ?>;
	border-left: 1px solid <?php echo $GLOBALS['BGCOLOR'] ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.weekview th.today {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
.weekview th.row {
	font-family: <?php echo $GLOBALS['FONTS'] ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top: 0px;
	border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	vertical-align: top;
	height: 40px;
}
.weekview td {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
}
.weekview td.today {
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
.weekviewtitle {
	text-align: center;
}
/* contains the year (i.e. 2004) in year.php */
td.weekviewtitle .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
}
td.weekviewtitle .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	text-align: center;
}


.new {
	border-width: 0px;
	width: 10px;
	height: 10px;
}


-->
</style>
