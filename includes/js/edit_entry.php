<?php
	global $groups_enabled,$WORK_DAY_START_HOUR;
?><script type="text/javascript">
<!-- <![CDATA[
// do a little form verifying
function validate_and_submit () {
  if ( document.forms[0].name.value == "" ) {
    document.forms[0].name.select ();
    document.forms[0].name.focus ();
    alert ( "<?php etranslate("You have not entered a Brief Description")?>." );
    return false;
  }
  // Leading zeros seem to confuse parseInt()
  if ( document.forms[0].hour.value.charAt ( 0 ) == '0' )
    document.forms[0].hour.value = document.forms[0].hour.value.substring ( 1, 2 );
  if ( document.forms[0].timetype.selectedIndex == 1 ) {
    h = parseInt ( document.forms[0].hour.value );
    m = parseInt ( document.forms[0].minute.value );
<?php if ($GLOBALS["TIME_FORMAT"] == "12") { ?>
    if ( document.forms[0].ampm[1].checked ) {
      // pm
      if ( h < 12 )
        h += 12;
    } else {
      // am
      if ( h == 12 )
        h = 0;
    }
<?php } ?>
    if ( h >= 24 || m > 59 ) {
      alert ( "<?php etranslate ("You have not entered a valid time of day")?>." );
      document.forms[0].hour.select ();
      document.forms[0].hour.focus ();
      return false;
    }
    // Ask for confirmation for time of day if it is before the user's
    // preference for work hours.
    <?php if ($GLOBALS["TIME_FORMAT"] == "24") {
      echo "if ( h < $WORK_DAY_START_HOUR  ) {";
    }  else {
      echo "if ( h < $WORK_DAY_START_HOUR && document.forms[0].ampm[0].checked ) {";
    }
    ?>
    if ( ! confirm ( "<?php etranslate ("The time you have entered begins before your preferred work hours.  Is this correct?")?> "))
      return false;
  }
  }
  // is there really a change?
  changed = false;
  form=document.forms[0];
  for ( i = 0; i < form.elements.length; i++ ) {
    field = form.elements[i];
    switch ( field.type ) {
      case "radio":
      case "checkbox":
        if ( field.checked != field.defaultChecked )
          changed = true;
        break;
      case "text":
//      case "textarea":
        if ( field.value != field.defaultValue )
          changed = true;
        break;
      case "select-one":
//      case "select-multiple":
        for( j = 0; j < field.length; j++ ) {
          if ( field.options[j].selected != field.options[j].defaultSelected )
            changed = true;
        }
        break;
    }
  }
  if ( changed ) {
    form.entry_changed.value = "yes";
  }
//Add code to make HTMLArea code stick in TEXTAREA
 if (typeof editor != "undefined") editor._textArea.value = editor.getHTML();
  // would be nice to also check date to not allow Feb 31, etc...
  document.forms[0].submit ();
  return true;
}

function selectDate (  day, month, year, current, evt ) {
  // get currently selected day/month/year
  monthobj = eval ( 'document.editentryform.' + month );
  curmonth = monthobj.options[monthobj.selectedIndex].value;
  yearobj = eval ( 'document.editentryform.' + year );
  curyear = yearobj.options[yearobj.selectedIndex].value;
  date = curyear;

		if (document.getElementById) {
    mX = evt.clientX   + 40;
    mY = evt.clientY  + 120;
  }
  else {
    mX = evt.pageX + 40;
    mY = evt.pageY +130;
  }
	var MyPosition = 'scrollbars=no,toolbar=no,left=' + mX + ',top=' + mY + ',screenx=' + mX + ',screeny=' + mY ;
  if ( curmonth < 10 )
    date += "0";
  date += curmonth;
  date += "01";
  url = "datesel.php?form=editentryform&fday=" + day +
    "&fmonth=" + month + "&fyear=" + year + "&date=" + date;
  var colorWindow = window.open(url,"DateSelection","width=300,height=200,"  + MyPosition);
}

<?php if ( $groups_enabled == "Y" ) { 
?>function selectUsers () {
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.forms[0].elements.length; i++ ) {
    if ( document.forms[0].elements[i].name == "participants[]" )
      listid = i;
  }
  url = "usersel.php?form=editentryform&listid=" + listid + "&users=";
  // add currently selected users
  for ( i = 0, j = 0; i < document.forms[0].elements[listid].length; i++ ) {
    if ( document.forms[0].elements[listid].options[i].selected ) {
      if ( j != 0 )
	url += ",";
      j++;
      url += document.forms[0].elements[listid].options[i].value;
    }
  }
  //alert ( "URL: " + url );
  // open window
  window.open ( url, "UserSelection",
    "width=500,height=500,resizable=yes,scrollbars=yes" );
}
<?php } ?>

<?php	// This function is called when the event type combo box 
	// is changed. If the user selectes "untimed event" or "all day event",
	// the times & duration fields are hidden.
	// If they change their mind & switch it back, the original 
	// values are restored for them
?>function timetype_handler () {
  var i = document.forms[0].timetype.selectedIndex;
  var val = document.forms[0].timetype.options[i].text;
  //alert ( "val " + i + " = " + val );
  // i == 1 when set to timed event
  if ( i != 1 ) {
    // Untimed/All Day
    makeInvisible ( "timeentrystart" );
    if ( document.forms[0].duration_h ) {
      makeInvisible ( "timeentryduration" );
    } else {
      makeInvisible ( "timeentryend" );
    }
  } else {
    // Timed Event
    makeVisible ( "timeentrystart" );
    if ( document.forms[0].duration_h ) {
      makeVisible ( "timeentryduration" );
    } else {
      makeVisible ( "timeentryend" );
    }
  }
}

function rpttype_handler () {
  var i = document.forms[0].rpttype.selectedIndex;
  var val = document.forms[0].rpttype.options[i].text;
  //alert ( "val " + i + " = " + val );
  //i == 0 when event does not repeat
  if ( i != 0 ) {
    // none (not repeating)
    makeVisible ( "rptenddate" );
    makeVisible ( "rptfreq" );
    if ( i == 2 ) {
      makeVisible ( "rptday" );
    } else {
      makeInvisible ( "rptday" );
    }
  } else {
    // Timed Event
    makeInvisible ( "rptenddate" );
    makeInvisible ( "rptfreq" );
    makeInvisible ( "rptday" );
  }
}

<?php //see the showTab function in includes/js/visible.php for common code shared by all pages
	//using the tabbed GUI.
?>var tabs = new Array();
tabs[0] = "details";
tabs[1] = "sched";
tabs[2] = "participants";
tabs[3] = "pete";
//]]> -->
</script>