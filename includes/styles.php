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
}
/* cells in year.php that contain the numeric date */
/* used as "<tr class="dayofmonthyearview">" to format the cells WITHIN that row */
/* NOTE: removing the "td" below will modify the appearance of mini calendars */
.dayofmonthyearview td {
	text-align: right;
}
/* links within cells with dayofmonthyearview class */
.dayofmonthyearview a {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 13px;
  text-decoration: none;
}
.dayofmonthweekview {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: #000000;
  text-decoration: none;
}
.tablecellweekview {
  border-right: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-bottom: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
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
.navlinks {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
  text-decoration: none;
}
.tableborder {
  border-top: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
  border-left: 1px solid <?php echo $GLOBALS['TABLEBG']; ?>;
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
  font-size: 18px;
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
/* contains the year (i.e. 2004) in year.php */
.year {
	font-size: 24px;
	font-weight: bold;
}
/* formats the day name (i.e. Sun, Mon, etc) */
/* used as "<tr class="dayname">" to format the cells WITHIN that row */
/* NOTE: removing the "td" below will modify the appearance of mini calendars */
.dayname td {
	font-size: 10px;
}
td.numericdate {
	font-size: 10px;
}
.monthyear {
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
	font-weight: bold;
	font-size: 24px;
}
.user {
	font-size: 18px;
	font-weight: bold;
	color: <?php echo $GLOBALS['H2COLOR'] ?>;
}
.help {
	vertical-align: top;
	font-weight: bold;
}
-->
</style>
