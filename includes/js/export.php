<SCRIPT LANGUAGE="JavaScript">
function selectDate ( day, month, year ) {
  // get currently selected month/year
  monthobj = eval ( 'document.exportform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.exportform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=exportform&day=" + day +
    "&month=" + month + "&year=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,resizable=yes,scrollbars=yes");
}

function form_submit(object)
{
	if (object.format.options[object.format.selectedIndex].value == "ical")
	{
	  object.action = "export_handler.php/webcalendar.ics";
	}
	else if (object.format.options[object.format.selectedIndex].value == "vcal")
	{
		object.action = "export_handler.php/webcalendar.vcs";
	}
	else if (object.format.options[object.format.selectedIndex].value == "pilot-csv")
	{
		object.action = "export_handler.php/webcalendar.txt";
	}
	else if (object.format.options[object.format.selectedIndex].value == "pilot-text")
	{
		object.action = "export_handler.php/webcalendar.txt";
	}

	object.submit();
}
</SCRIPT>