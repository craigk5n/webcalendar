<?php
global $groups_enabled,$WORK_DAY_START_HOUR;
?>

<script type="text/javascript">
var oldhour = 0, oldminute = 0, olddh = 0, olddm = 0;

// detect browser
NS4 = (document.layers) ? 1 : 0;
IE4 = (document.all) ? 1 : 0;
// W3C stands for the W3C standard, implemented in Mozilla (and Netscape 6) and IE5
W3C = (document.getElementById) ? 1 : 0;	

function makeVisible ( name ) {
  var ele;

  if ( W3C ) {
    ele = document.getElementById(name);
  } else if ( NS4 ) {
    ele = document.layers[name];
  } else { // IE4
    ele = document.all[name];
  }

  if ( NS4 ) {
    ele.visibility = "show";
  } else {  // IE4 & W3C & Mozilla
    ele.style.visibility = "visible";
  }
}

function makeInvisible ( name ) {
  if (W3C) {
    document.getElementById(name).style.visibility = "hidden";
  } else if (NS4) {
    document.layers[name].visibility = "hide";
  } else {
    document.all[name].style.visibility = "hidden";
  }
}

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

  // would be nice to also check date to not allow Feb 31, etc...
  document.forms[0].submit ();
  return true;
}


function selectDate ( day, month, year, current ) {
  url = "datesel.php?form=editentryform&amp;day=" + day +
    "&amp;month=" + month + "&amp;year=" + year;
  if ( current > 0 )
    url += '&amp;date=' + current;
  window.open( url, "DateSelection",
    "width=300,height=200,resizable=yes,scrollbars=yes" );
}


<?php if ( $groups_enabled == "Y" ) { ?>
function selectUsers () {
  // find id of user selection object
  var listid = 0;
  for ( i = 0; i < document.forms[0].elements.length; i++ ) {
    if ( document.forms[0].elements[i].name == "participants[]" )
      listid = i;
  }
  url = "usersel.php?form=editentryform&amp;listid=" + listid + "&amp;users=";
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


// This function is called wheneve someone clicks on the
// "All day event" / "Untimed Event" / "Timed Event" selection
// box.  When the enabled all day, it clears all the time of day
// and duration fields.  If they change their mind and turn it off, we
// put the original values back for them.
// This isn't necessary, but it helps show what the meaning of "all-day" is.
function timetype_handler () {
  var i = document.forms[0].timetype.selectedIndex;
  var val = document.forms[0].timetype.options[i].text;
  //alert ( "val " + i + "  = " + val );
  // i == 1 when set to timed event
  if ( i != 1 ) {
    // Untimed/All Day
    makeInvisible ( "timeentrystartprompt" );
    makeInvisible ( "timeentrystart" );
    if ( document.forms[0].duration_h ) {
      makeInvisible ( "timeentrydurationprompt" );
      makeInvisible ( "timeentryduration" );
    } else {
      makeInvisible ( "timeentryendprompt" );
      makeInvisible ( "timeentryend" );
    }
  } else {
    // Timed Event
    makeVisible ( "timeentrystartprompt" );
    makeVisible ( "timeentrystart" );
    if ( document.forms[0].duration_h ) {
      makeVisible ( "timeentrydurationprompt" );
      makeVisible ( "timeentryduration" );
    } else {
      makeVisible ( "timeentryendprompt" );
      makeVisible ( "timeentryend" );
    }
  }
}




</script>
