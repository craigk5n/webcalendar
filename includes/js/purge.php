<SCRIPT LANGUAGE="JavaScript">
function selectDate ( day, month, year ) {
  // get currently selected month/year
  monthobj = eval ( 'document.purgeform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.purgeform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=purgeform&day=" + day +
    "&month=" + month + "&year=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,resizable=yes,scrollbars=yes");
}
</SCRIPT>