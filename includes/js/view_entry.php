<script type="text/Javascript">
<!--
function form_submit(object)
{
	if (object.format.options[object.format.selectedIndex].value == "ical")
	{
	  object.action = "export_handler.php/webcal<?php echo $GLOBALS["id"] ?>.ics";
	}
	else if (object.format.options[object.format.selectedIndex].value == "vcal")
	{
		object.action = "export_handler.php/webcal<?php echo $GLOBALS["id"] ?>.vcs";
	}
	else if (object.format.options[object.format.selectedIndex].value == "pilot-csv")
	{
		object.action = "export_handler.php/webcal<?php echo $GLOBALS["id"] ?>.txt";
	}
	else if (object.format.options[object.format.selectedIndex].value == "pilot-text")
	{
		object.action = "export_handler.php/webcal<?php echo $GLOBALS["id"] ?>.txt";
	}

	object.submit();
}
//-->
</script>