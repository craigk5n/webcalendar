<!-- Note: Although we may set the font size in here, we also make heavy
     use of the HTML font tag since many browsers do not properly support style sheet font settings.
-->
<style type="text/css">
<!--
.tablecell {
  font-family: <?php echo $GLOBALS['FONTS']; ?>;
  font-size: 12px;
  width: 80px;
  height: 80px;
}
.tablecelldemo {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 10px;
  width: 30px;
  height: 30px;
}
.tablecellweekend {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  width: 80px;
  height: 80px;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
}
.tablecellweekenddemo {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 10px;
  width: 30px;
  height: 30px;
  background-color: <?php echo ( $GLOBALS['WEEKENDBG'] == "" ? "#E0E0E0" : $GLOBALS['WEEKENDBG'] );?>;
}
.tableheader {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['THFG'] == "" ? "#FFFFFF" : $GLOBALS['THFG'] );?>;
  background-color: <?php echo ( $GLOBALS['THBG'] == "" ? "#000000" : $GLOBALS['THBG'] );?>;
}
.tableheadertoday {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 14px;
  color: <?php echo ( $GLOBALS['TABLECELLFG'] == "" ? "#000000" : $GLOBALS['TABLECELLFG'] ); ?>;
  background-color: <?php echo ( $GLOBALS['TODAYCELLBG'] == "" ? "#C0C0C0" : $GLOBALS['TODAYCELLBG'] ); ?>;
}
.dayofmonth {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: #000000;
  text-decoration: none;
  background-color: #E7E7E7;
}
.dayofmonthyearview {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  text-decoration: none;
}
.dayofmonthweekview {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: #000000;
  text-decoration: none;
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
  font-size: 14px;
  color: #B04040;
  text-decoration: none;
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
}
.popup {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: <?php echo ( $GLOBALS['POPUP_FG'] == "" ? "#000000" : $GLOBALS['POPUP_FG'] ); ?>;
  text-decoration: none;
}
.tooltip {
  cursor: help;
  text-decoration: none;
  font-weight: bold;
}
.defaulttext {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
  color: <?php echo ( $GLOBALS['TEXTCOLOR'] == "" ? "#000000" : $GLOBALS['TEXTCOLOR'] ); ?>;
}
h2 {
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 20px;
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
  font-family: <?php echo $GLOBALS['FONTS'] ?>;
  font-size: 12px;
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
-->
</style>
