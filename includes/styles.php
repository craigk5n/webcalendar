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
  vertical-align: top;
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
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 10px;
  width: 30px;
  height: 30px;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tablecellweekend {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  width: 75px;
  height: 75px;
  vertical-align: top;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tablecellweekenddemo {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 10px;
  width: 30px;
  height: 30px;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheader {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 14px;
  vertical-align: top;
  color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
  background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.tableheadertoday {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 14px;
  vertical-align: top;
  color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
  background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
}
.dayofmonth {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 13px;
  color: #000000;
  text-decoration: none;
  background-color: #E7E7E7;
  padding-left: 1px;
}
.weeknumber {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 10px;
	color: #B04040;
	text-decoration: none;
}
.entry {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 13px;
  color: #006000;
  text-decoration: none;
}
.unapprovedentry {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  color: #800000;
  text-decoration: none;
}
.layerentry {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  color: #006060;
  text-decoration: none;
}
.monthlink {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 13px;
  color: #B04040;
  text-decoration: none;
}

.navlinks {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}
a {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}
a:hover {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  color: #0000FF;
}
.aboutinfo {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  color: #000000;
  text-decoration: none;
  font-size: 13px;
}
.popup {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  color: <?php echo ( $GLOBALS['POPUP_FG'] == "" ? "#000000" : $GLOBALS['POPUP_FG'] ); ?>;
  background-color: <?php echo $GLOBALS['POPUP_BG']; ?>;
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
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 20px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
h3 {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 18px;
}
.pagetitle {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 24px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	font-weight: bold;
}
td {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
}
p {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
}
input {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
}
select {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
}
textarea {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  overflow: auto;
}
.dailymatrix {
  cursor: pointer;
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
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
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
.categories {
	font-size: 18px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
/* formats the left column in help sections */
.help {
	vertical-align: top;
	font-weight: bold;
}
/* standard table appearing mainly in prefs.php & admin.php */
table.standard {
	border-width: 1px;
	border-color: #000000;
	border-style: solid;
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
}



/* ALL STYLES BELOW THIS LINE ARE NEW as of 8 July 2004 */

/* ================== */
/* MISC. */

/* formats the action (i.e. plus) icon that appears in cells */
.new {
	border-width: 0px;
	width: 10px;
	height: 10px;
}
body {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
}
.bullet {
	width: 5px;
	height: 7px;
	border-width: 0px;
}
/* formerly .monthlink */
td.month a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	color: #B04040;
	text-decoration: none;
	text-align: center;
}
td.month a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
	text-align: center;
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
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
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
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
td.dayviewtitle .user {
	font-size: 18px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	font-weight: bold;
	text-align: center;
}
table.dayviewminical {
	border-width: 1px;
	border-style: solid;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.dayviewminical th.date {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	font-size: 47px;
}
.dayviewminical tr.monthnav th {
	text-align: center;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
.dayviewminical tr.monthnav td {
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-width: 0px;
}
.dayviewminical tr.day th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
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
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	font-weight: bold;
	font-size: 24px;
}
.monthview th {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
table.monthviewminical {
	border-width: 0px;
}
.monthviewminical td.month {
	text-align: center;
}
/* contains the name of the month (i.e. January, June, December, etc) in year.php */
.monthviewminical td.month a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
.monthviewminical td.month a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	color: #0000FF;
	font-size: 13px;
	text-decoration: none;
}
/* formats the day name (i.e. Sun, Mon, etc) */
/* used as "tr class="day"" to format the cells WITHIN that row */
/* NOTE: removing the "th" below will modify the appearance of mini calendars */
.monthviewminical tr.day {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-align: center;
}
/* cells in year.php that contain the numeric date */
/* NOTE: removing the "td" below will modify the appearance of mini calendars */
.monthviewminical td.numdate {
	text-align: right;
}
.monthviewminical tr.numdate td {
	text-align: right;
}
.monthviewminical td.numdate a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	text-decoration: none;
}
.monthviewminical td.numdate a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
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
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
td.yearviewtitle .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
.yearview th {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
/*	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>; */
	width: 14%;
}
table.yearviewminical {
	border-width: 0px;
}
.yearviewminical td.month {
	text-align: center;
}
/* contains the name of the month (i.e. January, June, December, etc) in year.php */
.yearviewminical td.month a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
.yearviewminical td.month a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	color: #0000FF;
	font-size: 13px;
	text-decoration: none;
}

/* formats the day name (i.e. Sun, Mon, etc) */
/* used as "tr class="day"" to format the cells WITHIN that row */
/* NOTE: removing the "th" below will modify the appearance of mini calendars */
.yearviewminical tr.day {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-align: center;
}
/* cells in year.php that contain the numeric date */
/* NOTE: removing the "td" below will modify the appearance of mini calendars */
.yearviewminical td.numdate {
	text-align: right;
}
.yearviewminical tr.numdate td {
	text-align: right;
}
.yearviewminical td.numdate a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	text-decoration: none;
}
.yearviewminical td.numdate a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
}
.highlight {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 12px;
	background-color: <?php echo $GLOBALS['TODAYCELLBG'];?>;
	font-weight: bold;
}

/* ======================= */
/* VIEW_D.PHP */
.viewdtitle {
	text-align: center;
}
.viewdtitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewdtitle .viewname {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.viewd {
	border-width: 0px;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewd th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
.viewd th.row {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	text-align: left;
	font-weight: normal;
	font-size: 13px;
}

/* ======================= */
/* VIEW_L.PHP */
.viewltitle {
	text-align: center;
}
.viewltitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewltitle .viewname {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.viewl {
	border-width: 0px;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewl th {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 14%;
}
table.viewlminical {
	border-width: 0px;
}
.viewlminical td.month {
	text-align: center;
}
/* contains the name of the month (i.e. January, June, December, etc) in year.php */
.viewlminical td.month a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	color: #B04040;
	font-size: 13px;
	text-decoration: none;
}
.viewlminical td.month a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	color: #0000FF;
	font-size: 13px;
	text-decoration: none;
}

/* formats the day name (i.e. Sun, Mon, etc) */
/* used as "tr class="day"" to format the cells WITHIN that row */
/* NOTE: removing the "th" below will modify the appearance of mini calendars */
.viewlminical tr.day {
	color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
	text-align: center;
}
/* cells in year.php that contain the numeric date */
/* NOTE: removing the "td" below will modify the appearance of mini calendars */
.viewlminical td.numdate {
	text-align: right;
}
.viewlminical tr.numdate td {
	text-align: right;
}
.viewlminical td.numdate a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	text-decoration: none;
}
.viewlminical td.numdate a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 13px;
	color: #0000FF;
	text-decoration: none;
}


/* ======================= */
/* VIEW_M.PHP */
.viewmtitle {
	text-align: center;
}
.viewmtitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewmtitle .viewname {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.viewm {
	border-width: 0px;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.viewm th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
.viewm th.today {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	width: 10%;
	vertical-align: top;
}

/* ======================== */
/* VIEW_T.PHP */
.viewttitle {
	text-align: center;
}
.viewttitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewttitle .viewname {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.viewt {
	border-width: 0px;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}

/* ======================== */
/* VIEW_V.PHP */
.viewvtitle {
	text-align: center;
}
.viewvtitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewvtitle .viewname {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.viewv {
	border-width: 0px;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.empty {
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top-width: 0px;
	border-left-width: 0px;
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 10%;
}
.viewv th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
.viewv th.today {
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
.viewv th.weekend {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}

/* ======================= */
/* VIEW_W.PHP */
.viewwtitle {
	text-align: center;
}
.viewwtitle .date {
	font-size: 24px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
.viewwtitle .viewname {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}
table.vieww {
	border-width: 1px;
	border-style: solid;
	border-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.vieww th {
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}

/* ========================= */
/* WEEK.PHP */
table.weekview {
	border: 0px;
	width: 100%;
}
.weekview th {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 1px;
	border-top-style: solid;
	border-top-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
.weekview th a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
.weekview th a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: #0000FF;
}
.weekview th.empty {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	background-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-top-width: 1px;
	border-top-style: solid;
	border-top-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-left-width: 1px;
	border-left-style: solid;
	border-left-color: <?php echo $GLOBALS['BGCOLOR']; ?>;
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.weekview th.today {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-top-width: 1px;
	border-top-style: solid;
	border-top-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
}
.weekview th.today a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
}
.weekview th.today a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: #0000FF;
}
.weekview th.row {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-top-width: 0px;
	border-left-width: 1px;
	border-left-style: solid;
	border-left-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	vertical-align: top;
	height: 40px;
}
.weekview td {
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
}
.weekview td.today {
	border-right-width: 1px;
	border-right-style: solid;
	border-right-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 12%;
	font-size: 13px;
	vertical-align: top;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
.weekviewtitle {
	text-align: center;
}
/* contains the year (i.e. 2004) */
td.weekviewtitle .date {
	font-size: 24px;
	font-weight: bold;
	text-align: center;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
td.weekviewtitle .weeknumber {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 20px;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
}
td.weekviewtitle .user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR']; ?>;
	text-align: center;
}

/* ===================== */
/* WEEK_DETAILS.PHP */
table.weekdetails {
	border-width: 0px;
	width: 90%;
	background-color: <?php echo $GLOBALS['TABLEBG']; ?>;
}
.weekdetails th {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.weekdetails th a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
.weekdetails th a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: #0000FF;
}
.weekdetails th.today {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.weekdetails th.today a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
}
.weekdetails th.today a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: #0000FF;
}
.weekdetails th.weekend {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
	background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
	border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
	width: 100%;
}
.weekdetails th.weekend a {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
}
.weekdetails th.weekend a:hover {
	font-family: <?php echo $GLOBALS['FONTS']; ?>;
	font-size: 14px;
	color: #0000FF;
}
.weekdetails td {
	background-color: <?php echo $GLOBALS['CELLBG']; ?>;
	vertical-align: top;
	height: 75px;
}
.weekdetails td.today {
	background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
	vertical-align: top;
	height: 75px;
}
.weekdetails td.weekend {
	background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
	vertical-align: top;
	height: 75px;
}

-->
</style>